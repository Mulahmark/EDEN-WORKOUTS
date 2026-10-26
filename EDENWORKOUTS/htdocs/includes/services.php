<?php
declare(strict_types=1);

function audit(string $action, ?string $entityType = null, ?int $entityId = null, ?array $meta = null, ?int $actorId = null, ?string $actorLabel = null): void
{
    try {
        if ($actorId === null && !empty($_SESSION['uid'])) { $actorId = (int) $_SESSION['uid']; }
        if ($actorLabel === null && $actorId) { $actorLabel = db_val("SELECT email FROM users WHERE id = ?", [$actorId]); }
        db_run("INSERT INTO audit_logs (actor_id, actor_label, action, entity_type, entity_id, metadata, ip_address) VALUES (?,?,?,?,?,?,?)",
            [$actorId, $actorLabel, $action, $entityType, $entityId, $meta ? json_encode($meta, JSON_UNESCAPED_SLASHES) : null, client_ip()]);
    } catch (Throwable $e) { error_log('[EDEN] audit failed: ' . $e->getMessage()); }
}

function notify(int $userId, string $title, string $message, string $type = 'general', ?string $relType = null, ?int $relId = null, ?string $dedupe = null): void
{
    db_run("INSERT INTO notifications (user_id, title, message, type, related_type, related_id, dedupe_key)
            VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title = title",
        [$userId, $title, $message, $type, $relType, $relId, $dedupe]);
}

function notify_member(int $memberId, string $title, string $message, string $type = 'general', ?string $relType = null, ?int $relId = null, ?string $dedupe = null): void
{
    $uid = db_val("SELECT user_id FROM members WHERE id = ?", [$memberId]);
    if ($uid) notify((int) $uid, $title, $message, $type, $relType, $relId, $dedupe);
}

/** Recalculates paid_total from authoritative payment rows and derives status from dates. */
function membership_recalc(int $membershipId): void
{
    $m = db_one("SELECT * FROM memberships WHERE id = ?", [$membershipId]);
    if (!$m) return;
    $paid = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE membership_id = ? AND status = 'completed'", [$membershipId]);
    $status = $m['status'];
    if (in_array($status, ['pending', 'active', 'expired'], true)) {
        $today = today();
        $status = $m['end_date'] < $today ? 'expired' : ($m['start_date'] > $today ? 'pending' : 'active');
    }
    db_run("UPDATE memberships SET paid_total = ?, status = ? WHERE id = ?", [number_format($paid, 2, '.', ''), $status, $membershipId]);
}

function expiry_sweep(): int
{
    return db_run("UPDATE memberships SET status = 'expired' WHERE status IN ('pending','active') AND end_date < ?", [today()]);
}

const SUBSCRIPTION_OPEN = ['pending', 'active'];

/**
 * Creates a subscription. Agreed price is frozen at sale time.
 * @throws RuntimeException on validation failure
 */
function subscription_create(array $in): array
{
    $plan = db_one("SELECT * FROM membership_plans WHERE id = ?", [(int) $in['plan_id']]);
    if (!$plan) throw new RuntimeException('Selected plan no longer exists.');

    if (empty($in['allow_duplicate'])) {
        $open = db_all("SELECT id FROM memberships WHERE member_id = ? AND status IN ('pending','active') LIMIT 1", [(int) $in['member_id']]);
        if ($open) throw new RuntimeException('This member already has an active or pending subscription. Tick the override to continue.');
    }

    $price = (float) $plan['price'];
    $discount = round((float) ($in['discount'] ?? 0), 2);
    $joining = array_key_exists('joining_fee', $in) && $in['joining_fee'] !== null ? round((float) $in['joining_fee'], 2) : (float) $plan['joining_fee'];
    if ($discount < 0 || $discount > $price) throw new RuntimeException('Discount cannot exceed the plan price.');
    if ($joining < 0) throw new RuntimeException('Joining fee cannot be negative.');
    $total = round($price - $discount + $joining, 2);
    $end = compute_end_date((string) $in['start_date'], (int) $plan['duration_value'], (string) $plan['duration_unit']);

    return tx(function () use ($in, $plan, $price, $discount, $joining, $total, $end) {
        $id = db_insert("INSERT INTO memberships (member_id, plan_id, start_date, end_date, agreed_price, discount, joining_fee, total_due, paid_total, status, notes, created_by, renewed_from_id)
                         VALUES (?,?,?,?,?,?,?,?,'0.00','active',?,?,?)",
            [$in['member_id'], $plan['id'], $in['start_date'], $end, $price, $discount, $joining, $total,
             $in['notes'] ?? null, $in['created_by'] ?? null, $in['renewed_from_id'] ?? null]);
        membership_recalc($id);
        return db_one("SELECT * FROM memberships WHERE id = ?", [$id]);
    });
}

/** @throws RuntimeException */
function payment_record(array $in): array
{
    if (!db_val("SELECT 1 FROM members WHERE id = ?", [(int) $in['member_id']])) throw new RuntimeException('Member not found.');
    if (!empty($in['membership_id'])) {
        $owner = db_val("SELECT member_id FROM memberships WHERE id = ?", [(int) $in['membership_id']]);
        if ($owner === null || (int) $owner !== (int) $in['member_id']) throw new RuntimeException('That subscription does not belong to the selected member.');
    }
    if (!empty($in['tx_reference'])) {
        $dupe = db_one("SELECT reference FROM payments WHERE tx_reference = ? LIMIT 1", [$in['tx_reference']]);
        if ($dupe) throw new RuntimeException('Transaction reference already recorded on ' . $dupe['reference'] . '.');
    }
    if (($in['method'] ?? 'cash') === 'mpesa' && empty($in['tx_reference'])) {
        throw new RuntimeException('Manually recorded M-Pesa payments need the M-Pesa transaction code.');
    }
    $amount = round((float) $in['amount'], 2);
    if ($amount < 0.01 || $amount > 10000000) throw new RuntimeException('Invalid amount.');

    return tx(function () use ($in, $amount) {
        $id = db_insert("INSERT INTO payments (reference, member_id, membership_id, amount, method, tx_reference, paid_at, received_by, notes, channel, status)
                         VALUES (?,?,?,?,?,?,?,?,?,'manual','completed')",
            [ref_code('PAY'), $in['member_id'], $in['membership_id'] ?: null, $amount, $in['method'] ?? 'cash',
             $in['tx_reference'] ?: null, $in['paid_at'] ?? date('Y-m-d H:i:s'), $in['received_by'] ?? null, $in['notes'] ?? null]);
        if (!empty($in['membership_id'])) membership_recalc((int) $in['membership_id']);
        return db_one("SELECT * FROM payments WHERE id = ?", [$id]);
    });
}

/** Auditable reversal — financial rows are never deleted. */
function payment_reverse(int $paymentId, string $reason, string $type, int $actorId): array
{
    $reason = trim($reason);
    if (mb_strlen($reason) < 5) throw new RuntimeException('Give a clear reason (at least 5 characters).');
    return tx(function () use ($paymentId, $reason, $type, $actorId) {
        $p = db_one("SELECT * FROM payments WHERE id = ?", [$paymentId]);
        if (!$p) throw new RuntimeException('Payment not found.');
        if ($p['status'] === 'reversed') throw new RuntimeException('This payment has already been reversed.');
        db_run("UPDATE payments SET status = 'reversed', reversed_at = NOW(), reversed_by = ?, reversal_reason = ? WHERE id = ?", [$actorId, $reason, $paymentId]);
        db_insert("INSERT INTO payment_adjustments (payment_id, member_id, membership_id, type, amount, reason, created_by) VALUES (?,?,?,?,?,?,?)",
            [$paymentId, $p['member_id'], $p['membership_id'], $type, $p['amount'], $reason, $actorId]);
        if ($p['membership_id']) membership_recalc((int) $p['membership_id']);
        return $p;
    });
}

/** @throws RuntimeException */
function attendance_checkin(int $memberId, int $staffId, ?string $overrideReason = null): array
{
    $m = db_one("SELECT * FROM members WHERE id = ?", [$memberId]);
    if (!$m) throw new RuntimeException('Member not found.');
    if ($m['status'] === 'suspended') throw new RuntimeException($m['first_name'] . ' is suspended.');
    if ($m['status'] === 'inactive') throw new RuntimeException($m['first_name'] . ' is deactivated.');

    if (db_val("SELECT 1 FROM attendance WHERE member_id = ? AND check_out_at IS NULL LIMIT 1", [$memberId])) {
        throw new RuntimeException($m['first_name'] . ' ' . $m['last_name'] . ' is already checked in.');
    }

    $sub = db_one("SELECT * FROM memberships WHERE member_id = ? AND end_date >= ? ORDER BY end_date DESC LIMIT 1", [$memberId, today()]);
    $override = $overrideReason !== null && mb_strlen(trim($overrideReason)) >= 5;
    if (!$sub && !$override) {
        throw new RuntimeException($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_number'] . ') has no valid subscription. An administrator may override with a reason.');
    }
    if ($sub && in_array($sub['status'], ['suspended', 'cancelled'], true) && !$override) {
        throw new RuntimeException('Subscription is ' . $sub['status'] . '. Administrator override required.');
    }
    $isOverride = !$sub || !empty($override);
    $id = db_insert("INSERT INTO attendance (member_id, attend_date, check_in_at, recorded_by, notes, is_override, override_reason) VALUES (?,?,NOW(),?,?,?,?)",
        [$memberId, today(), $staffId, $override ? trim($overrideReason) : null, $isOverride ? 1 : 0,
         (!$sub || ($sub && in_array($sub['status'], ['suspended', 'cancelled'], true))) && $override ? trim($overrideReason) : null]);
    return ['attendance' => db_one("SELECT * FROM attendance WHERE id = ?", [$id]), 'member' => $m];
}

function attendance_checkout(int $memberId): bool
{
    return db_run("UPDATE attendance SET check_out_at = NOW() WHERE member_id = ? AND check_out_at IS NULL", [$memberId]) > 0;
}

function unread_notifications(int $userId, int $limit = 8): array
{
    return db_all("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT " . (int) $limit, [$userId]);
}

function admin_stats(): array
{
    $t = today();
    return [
        'total_members'   => (int) db_val("SELECT COUNT(*) FROM members"),
        'active_members'  => (int) db_val("SELECT COUNT(*) FROM members WHERE status = 'active'"),
        'pending_members' => (int) db_val("SELECT COUNT(*) FROM members WHERE status = 'pending'"),
        'expired_subs'    => (int) db_val("SELECT COUNT(*) FROM memberships WHERE status = 'expired'"),
        'expiring_week'   => (int) db_val("SELECT COUNT(*) FROM memberships WHERE status = 'active' AND end_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)", [$t, $t]),
        'expiring_month'  => (int) db_val("SELECT COUNT(*) FROM memberships WHERE status = 'active' AND end_date BETWEEN ? AND DATE_ADD(?, INTERVAL 30 DAY)", [$t, $t]),
        'today_checkins'  => (int) db_val("SELECT COUNT(*) FROM attendance WHERE attend_date = ?", [$t]),
        'month_revenue'   => (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE_FORMAT(paid_at,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"),
        'outstanding'     => (float) db_val("SELECT COALESCE(SUM(GREATEST(total_due - paid_total,0)),0) FROM memberships WHERE status IN ('active','pending')"),
        'active_trainers' => (int) db_val("SELECT COUNT(*) FROM trainers WHERE employment_status = 'active'"),
        'month_expenses'  => (float) db_val("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m') = DATE_FORMAT(NOW(),'%Y-%m')"),
        'new_enquiries'   => (int) db_val("SELECT COUNT(*) FROM contact_enquiries WHERE status = 'new'"),
    ];
}

function last_months(int $count): array
{
    $out = [];
    for ($i = $count - 1; $i >= 0; $i--) $out[] = date('Y-m', strtotime("first day of -$i month"));
    return $out;
}
