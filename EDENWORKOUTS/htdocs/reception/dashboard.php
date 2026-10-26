<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['receptionist', 'admin']);
$today = today();
$inGym = db_all("SELECT a.id, a.check_in_at, m.id mid, CONCAT(m.first_name,' ',m.last_name) name, m.member_number FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.check_out_at IS NULL ORDER BY a.check_in_at DESC");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$todayRevenue = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE(paid_at)=?", [$today]);
$checkins = (int) db_val("SELECT COUNT(*) FROM attendance WHERE attend_date=?", [$today]);
$expiring = (int) db_val("SELECT COUNT(*) FROM memberships WHERE status='active' AND end_date BETWEEN ? AND DATE_ADD(?, INTERVAL 7 DAY)", [$today, $today]);
$pending = (int) db_val("SELECT COUNT(*) FROM members WHERE status='pending'");
dash_start($u, 'Reception desk', fmt_date($today) . ' · signed in as ' . $u['full_name'], 'reception/dashboard.php');
?>
<div class="stat-grid">
  <?= stat_card('Check-ins today', $checkins, '', 'orange') ?><?= stat_card('Currently in gym', count($inGym), '', 'ok') ?>
  <?= stat_card('Revenue today', money($todayRevenue)) ?><?= stat_card('Expiring in 7 days', $expiring, '', 'warn') ?>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Quick check-in</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('reception/check-in.php')) ?>">Console</a></div><div class="panel-body">
    <?php if (!$members): ?><p class="small text-muted-eden">No active members yet. <a href="<?= e(url('reception/members.php')) ?>">Register one.</a></p>
    <?php else: ?><form action="<?= e(url('actions/handle.php?do=attendance.checkin')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="back" value="reception/dashboard.php">
      <div class="field"><label>Active member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' — ' . $m['member_number']) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-eden" type="submit">Check in</button></form>
      <p class="hint">Expired or suspended memberships are refused here — an administrator override is required.</p><?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Currently in the gym</h2></div><div class="panel-body">
    <?php if (!$inGym): ?><?= empty_state('Nobody is checked in', 'Members appear here until they are checked out.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($inGym as $g): ?><li><i class="bi bi-person-check"></i><span><?= e($g['name']) ?> · <?= e(date('H:i', strtotime($g['check_in_at']))) ?>
      <form action="<?= e(url('actions/handle.php?do=attendance.checkout')) ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="member_id" value="<?= (int) $g['mid'] ?>"><input type="hidden" name="back" value="reception/dashboard.php"><button class="btn btn-eden-ghost btn-sm">Out</button></form></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Shortcuts</h2></div><div class="panel-body d-flex flex-wrap gap-2">
  <a class="btn btn-eden" href="<?= e(url('reception/payments.php')) ?>">Record payment</a>
  <a class="btn btn-eden-ghost" href="<?= e(url('reception/memberships.php')) ?>">New subscription</a>
  <a class="btn btn-eden-ghost" href="<?= e(url('reception/members.php')) ?>">Members</a>
  <a class="btn btn-eden-ghost" href="<?= e(url('reception/attendance.php')) ?>">Attendance history</a>
  <?php if ($pending): ?><span class="chip"><?= $pending ?> member registration(s) pending</span><?php endif; ?>
</div></div>
<?php dash_end(); ?>
