<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0);
if (!$mid) { err('No member profile is linked to this account.'); redirect('login.php'); }
guard_member($mid, $u);
expiry_sweep();
$m = db_one("SELECT * FROM members WHERE id = ?", [$mid]);
$sub = db_one("SELECT ms.*, p.name plan FROM memberships ms JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.member_id=? ORDER BY ms.end_date DESC LIMIT 1", [$mid]);
$visits30 = (int) db_val("SELECT COUNT(*) FROM attendance WHERE member_id=? AND attend_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", [$mid]);
$sessions = (int) db_val("SELECT COUNT(*) FROM workout_sessions WHERE member_id=?", [$mid]);
$plans = db_all("SELECT * FROM workout_plans WHERE member_id=? ORDER BY created_at DESC LIMIT 3", [$mid]);
$goals = db_all("SELECT * FROM fitness_goals WHERE member_id=? AND status='active' ORDER BY created_at DESC LIMIT 4", [$mid]);
$pays = db_all("SELECT * FROM payments WHERE member_id=? ORDER BY paid_at DESC LIMIT 4", [$mid]);
$coach = db_one("SELECT t.full_name FROM trainer_assignments a JOIN trainers t ON t.id=a.trainer_id WHERE a.member_id=? AND a.status='active' LIMIT 1", [$mid]);
$balance = $sub ? balance_of($sub['total_due'], $sub['paid_total']) : 0.0;
$daysLeft = $sub ? (int) ((strtotime($sub['end_date']) - strtotime(today())) / 86400) : null;
dash_start($u, 'Hi ' . $m['first_name'], $m['member_number'] . ($coach ? ' · Coach: ' . $coach['full_name'] : ' · No coach assigned yet'), 'member/dashboard.php');
?>
<?php if ($sub && $daysLeft !== null && $daysLeft <= 7): ?>
  <div class="notice"><strong><?= $daysLeft < 0 ? 'Your membership has expired' : 'Your membership expires in ' . $daysLeft . ' day(s)' ?>.</strong>
    <?= e($sub['plan']) ?> ends <?= e(fmt_date($sub['end_date'])) ?>. Speak to reception or send a renewal request.</div>
<?php endif; ?>
<div class="stat-grid">
  <?= stat_card('Membership', $sub['plan'] ?? 'None', $sub ? fmt_date($sub['start_date']) . ' → ' . fmt_date($sub['end_date']) : 'Visit reception to subscribe', $sub && $sub['status'] === 'active' ? 'ok' : 'warn') ?>
  <?= stat_card('Balance', money($balance), $balance > 0 ? 'Payable at reception' : 'Nothing outstanding', $balance > 0 ? 'warn' : 'ok') ?>
  <?= stat_card('Visits (30 days)', $visits30, $sessions . ' sessions logged', 'orange') ?>
  <?= stat_card('Active goals', count($goals), count($plans) . ' recent plans', '') ?>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>My workout plans</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('member/workouts.php')) ?>">All plans</a></div><div class="panel-body">
    <?php if (!$plans): ?><?= empty_state('No plans assigned yet', 'Your coach builds your programme here once you are assigned.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($plans as $p): ?><li><i class="bi bi-clipboard-check"></i><span><?= e($p['name']) ?> <?= status_badge($p['status']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>My goals</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('member/goals.php')) ?>">Manage</a></div><div class="panel-body">
    <?php if (!$goals): ?><?= empty_state('No goals set', 'Set a goal to give your training a target.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($goals as $g): ?><li><i class="bi bi-bullseye"></i><span><?= e($g['title']) ?> — <?= e(($g['current_value'] ?? '—') . ' / ' . ($g['target_value'] ?? '—') . ' ' . ($g['unit'] ?? '')) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Recent payments</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('member/payments.php')) ?>">History</a></div><div class="panel-body table-wrap">
  <?php if (!$pays): ?><?= empty_state('No payments yet', 'Receipts for your payments appear here.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th></th></tr></thead><tbody>
    <?php foreach ($pays as $p): ?><tr><td class="small"><?= e($p['reference']) ?></td><td class="small"><?= e(fmt_datetime($p['paid_at'])) ?></td><td><?= e(money($p['amount'])) ?></td>
      <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('receipts/view.php?id=' . $p['id'])) ?>">Receipt</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Quick actions</h2></div><div class="panel-body d-flex flex-wrap gap-2">
  <a class="btn btn-eden" href="<?= e(url('member/sessions.php')) ?>">Log a workout</a>
  <a class="btn btn-eden-ghost" href="<?= e(url('member/progress.php')) ?>">Record measurements</a>
  <a class="btn btn-eden-ghost" href="<?= e(url('member/attendance.php')) ?>">My attendance</a>
</div></div>
<?php dash_end(); ?>
