<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin', 'receptionist']);
$id = (int) get('id');
$m = db_one("SELECT * FROM members WHERE id = ?", [$id]);
if (!$m) { err('Member not found.'); redirect($u['role'] === 'admin' ? 'admin/members.php' : 'reception/members.php'); }
$subs = db_all("SELECT ms.*, p.name plan FROM memberships ms JOIN membership_plans p ON p.id = ms.plan_id WHERE ms.member_id = ? ORDER BY ms.end_date DESC", [$id]);
$pays = db_all("SELECT * FROM payments WHERE member_id = ? ORDER BY paid_at DESC LIMIT 10", [$id]);
$visits = db_all("SELECT * FROM attendance WHERE member_id = ? ORDER BY check_in_at DESC LIMIT 8", [$id]);
$plans = db_all("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY price");
$active = null; foreach ($subs as $s) { if (in_array($s['status'], ['active', 'pending'], true)) { $active = $s; break; } }
$outstanding = 0.0; foreach ($subs as $s) if (in_array($s['status'], ['active', 'pending'], true)) $outstanding += balance_of($s['total_due'], $s['paid_total']);
dash_start($u, $m['first_name'] . ' ' . $m['last_name'], $m['member_number'] . ' · joined ' . fmt_date($m['joined_at']) . ' · ' . $m['email']);
?>
<div class="stat-grid">
  <?= stat_card('Current plan', $active['plan'] ?? 'None', $active ? 'Ends ' . fmt_date($active['end_date']) : 'No subscription yet', 'orange') ?>
  <?= stat_card('Outstanding', money($outstanding), 'Active + pending', $outstanding > 0 ? 'warn' : 'ok') ?>
  <?= stat_card('Account status', $m['status']) ?>
  <?= stat_card('Visits logged', count($visits)) ?>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Member details</h2><span><?= status_badge($m['status']) ?></span></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=member.update')) ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="member_id" value="<?= $id ?>">
      <input type="hidden" name="back" value="<?= $u['role'] === 'admin' ? 'admin/member.php?id=' . $id : 'reception/members.php' ?>">
      <div class="row g-3">
        <div class="col-sm-6"><div class="field"><label>First name</label><input class="input" name="first_name" value="<?= e($m['first_name']) ?>" required></div></div>
        <div class="col-sm-6"><div class="field"><label>Last name</label><input class="input" name="last_name" value="<?= e($m['last_name']) ?>" required></div></div>
        <div class="col-sm-6"><div class="field"><label>Email</label><input class="input" type="email" name="email" value="<?= e($m['email']) ?>" required></div></div>
        <div class="col-sm-6"><div class="field"><label>Phone</label><input class="input" name="phone" value="<?= e($m['phone']) ?>" required></div></div>
        <div class="col-sm-6"><div class="field"><label>Date of birth</label><input class="input" type="date" name="date_of_birth" value="<?= e($m['date_of_birth']) ?>"></div></div>
        <div class="col-sm-6"><div class="field"><label>Gender</label><select class="input" name="gender"><option value="">Not stated</option>
          <?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $v): ?><option value="<?= $k ?>" <?= $m['gender'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div></div>
        <div class="col-sm-6"><div class="field"><label>Emergency contact</label><input class="input" name="emergency_contact_name" value="<?= e($m['emergency_contact_name']) ?>"></div></div>
        <div class="col-sm-6"><div class="field"><label>Emergency phone</label><input class="input" name="emergency_contact_phone" value="<?= e($m['emergency_contact_phone']) ?>"></div></div>
        <div class="col-12"><div class="field"><label>Staff notes (restricted)</label><textarea class="input" name="notes" rows="2"><?= e($m['notes']) ?></textarea></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Save member</button>
    </form>
    <?php if ($u['role'] === 'admin'): ?>
      <hr style="border-color:var(--line)">
      <p class="small text-muted-eden">Change account status</p>
      <div class="d-flex flex-wrap gap-2">
        <?php foreach (['active', 'pending', 'inactive', 'suspended'] as $st): if ($st === $m['status']) continue; ?>
          <form action="<?= e(url('actions/handle.php?do=member.status')) ?>" method="post" data-confirm="Mark this member as <?= $st ?>?">
            <?= csrf_field() ?><input type="hidden" name="member_id" value="<?= $id ?>"><input type="hidden" name="status" value="<?= $st ?>">
            <button class="btn btn-eden-ghost btn-sm" type="submit">Mark <?= $st ?></button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Create subscription</h2></div><div class="panel-body">
    <?php if (!$plans): ?><p class="small text-muted-eden">No active plans yet. <a href="<?= e(url('admin/plans.php')) ?>">Create one first.</a></p>
    <?php else: ?>
    <form action="<?= e(url('actions/handle.php?do=subscription.create')) ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="member_id" value="<?= $id ?>"><input type="hidden" name="back" value="admin/member.php?id=<?= $id ?>">
      <div class="field"><label>Plan</label><select class="input" name="plan_id" required><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> — <?= e(money($p['price'])) ?> / <?= e(duration_label((int) $p['duration_value'], $p['duration_unit'])) ?></option><?php endforeach; ?></select></div>
      <div class="row g-2">
        <div class="col-6"><div class="field"><label>Start date</label><input class="input" type="date" name="start_date" value="<?= e(today()) ?>" required></div></div>
        <div class="col-6"><div class="field"><label>Discount</label><input class="input" type="number" step="0.01" min="0" name="discount" value="0"></div></div>
      </div>
      <div class="field"><label>Notes</label><input class="input" name="notes"></div>
      <label class="small text-muted-eden"><input type="checkbox" name="allow_duplicate" value="1"> Allow a second active subscription</label>
      <button class="btn btn-eden mt-2" type="submit">Create subscription</button>
    </form>
    <?php endif; ?>
  </div></div>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Subscription history</h2></div><div class="panel-body table-wrap">
    <?php if (!$subs): ?><?= empty_state('No subscriptions', 'Create the first subscription to track expiry and payments.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Period</th><th>Due</th><th>Paid</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($subs as $s): ?><tr><td><?= e($s['plan']) ?></td><td class="small"><?= e(fmt_date($s['start_date'])) ?> → <?= e(fmt_date($s['end_date'])) ?></td>
        <td class="small"><?= e(money($s['total_due'])) ?></td><td class="small"><?= e(money($s['paid_total'])) ?><br><span class="text-muted-eden"><?= e(money(balance_of($s['total_due'], $s['paid_total']))) ?> due</span></td>
        <td><?= status_badge($s['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Payment history</h2></div><div class="panel-body table-wrap">
    <?php if (!$pays): ?><?= empty_state('No payments recorded', 'Record a payment from the Payments screen to issue a receipt.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Date</th><th>Amount</th><th></th></tr></thead><tbody>
      <?php foreach ($pays as $p): ?><tr><td class="small"><?= e($p['reference']) ?></td><td class="small"><?= e(fmt_datetime($p['paid_at'])) ?></td>
        <td><?= e(money($p['amount'])) ?><br><?= status_badge($p['status']) ?></td>
        <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('receipts/view.php?id=' . $p['id'])) ?>">Receipt</a></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Recent attendance</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/attendance.php')) ?>">Attendance desk</a></div><div class="panel-body">
  <?php if (!$visits): ?><?= empty_state('No visits recorded', 'Check-ins recorded at reception appear here.') ?>
  <?php else: ?><ul class="plan-benefits"><?php foreach ($visits as $v): ?><li><i class="bi bi-door-open"></i><span><?= e(fmt_date($v['attend_date'])) ?> · <?= e(date('H:i', strtotime($v['check_in_at']))) ?> → <?= $v['check_out_at'] ? e(date('H:i', strtotime($v['check_out_at']))) : 'still in' ?><?= $v['is_override'] ? ' · override' : '' ?></span></li><?php endforeach; ?></ul><?php endif; ?>
</div></div>
<?php dash_end(); ?>
