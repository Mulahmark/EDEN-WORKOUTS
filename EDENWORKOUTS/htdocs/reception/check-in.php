<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['receptionist', 'admin']);
$inGym = db_all("SELECT a.id, a.check_in_at, m.id mid, CONCAT(m.first_name,' ',m.last_name) name, m.member_number FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.check_out_at IS NULL ORDER BY a.check_in_at DESC");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$todayRows = db_all("SELECT a.*, CONCAT(m.first_name,' ',m.last_name) name FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.attend_date=? ORDER BY a.check_in_at DESC LIMIT 15", [today()]);
dash_start($u, 'Check-in console', 'Fast member check-in for the front desk.', 'reception/check-in.php');
?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>1 · Select member</h2></div><div class="panel-body">
    <?php if (!$members): ?><p class="small text-muted-eden">No active members yet.</p><?php else: ?>
    <form action="<?= e(url('actions/handle.php?do=attendance.checkin')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="back" value="reception/check-in.php">
      <div class="field"><label>Active member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' — ' . $m['member_number']) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-eden" type="submit">Check in member</button></form>
      <p class="hint">Duplicate check-ins are blocked. Lapsed memberships require an administrator override.</p><?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>2 · Currently in the gym</h2></div><div class="panel-body">
    <?php if (!$inGym): ?><?= empty_state('Nobody checked in', 'Check someone in to get started.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($inGym as $g): ?><li><i class="bi bi-person-check"></i><span><?= e($g['name']) ?> · <?= e(date('H:i', strtotime($g['check_in_at']))) ?>
      <form action="<?= e(url('actions/handle.php?do=attendance.checkout')) ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="member_id" value="<?= (int) $g['mid'] ?>"><input type="hidden" name="back" value="reception/check-in.php"><button class="btn btn-eden btn-sm">Check out</button></form></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Today</h2></div><div class="panel-body table-wrap">
  <?php if (!$todayRows): ?><?= empty_state('No visits recorded today', 'Check-ins appear here as members arrive.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Member</th><th>In</th><th>Out</th></tr></thead><tbody>
    <?php foreach ($todayRows as $r): ?><tr><td class="small"><?= e($r['name']) ?></td><td class="small"><?= e(date('H:i', strtotime($r['check_in_at']))) ?></td><td class="small"><?= $r['check_out_at'] ? e(date('H:i', strtotime($r['check_out_at']))) : 'in gym' ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div></div>
<?php dash_end(); ?>
