<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin', 'receptionist']);
$from = get('from') ?: today(); $to = get('to') ?: today();
$rows = db_all("SELECT a.*, CONCAT(m.first_name,' ',m.last_name) name, m.member_number, m.id mid FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.attend_date BETWEEN ? AND ? ORDER BY a.check_in_at DESC LIMIT 100", [$from, $to]);
$inGym = db_all("SELECT a.id, a.check_in_at, m.id mid, CONCAT(m.first_name,' ',m.last_name) name, m.member_number FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.check_out_at IS NULL ORDER BY a.check_in_at DESC");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
dash_start($u, 'Attendance', 'Check members in and out. Expired or suspended memberships require an administrator override with a reason.', 'admin/attendance.php');
?>
<div class="stat-grid"><?= stat_card("Today's check-ins", count($inGym), 'Currently in the gym', 'orange') ?><?= stat_card('Records in range', count($rows), fmt_date($from) . ' → ' . fmt_date($to)) ?></div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Quick check-in</h2></div><div class="panel-body">
    <?php if (!$members): ?><p class="small text-muted-eden">No active members yet.</p><?php else: ?>
    <form action="<?= e(url('actions/handle.php?do=attendance.checkin')) ?>" method="post">
      <?= csrf_field() ?><input type="hidden" name="back" value="admin/attendance.php">
      <div class="field"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' — ' . $m['member_number']) ?></option><?php endforeach; ?></select></div>
      <?php if ($u['role'] === 'admin'): ?><div class="field"><label>Administrator override reason</label><input class="input" name="override_reason" placeholder="Only used when the subscription has lapsed"><p class="hint">Stored on the record and written to the audit log.</p></div><?php endif; ?>
      <button class="btn btn-eden" type="submit">Check in</button>
    </form>
    <?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Currently in the gym</h2></div><div class="panel-body">
    <?php if (!$inGym): ?><?= empty_state('Gym is empty', 'Members appear here from check-in until they are checked out.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($inGym as $g): ?><li><i class="bi bi-person-check"></i><span><?= e($g['name']) ?> <span class="small text-muted-eden"><?= e($g['member_number']) ?> · in at <?= e(date('H:i', strtotime($g['check_in_at']))) ?></span>
      <form action="<?= e(url('actions/handle.php?do=attendance.checkout')) ?>" method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="member_id" value="<?= (int) $g['mid'] ?>"><input type="hidden" name="back" value="admin/attendance.php"><button class="btn btn-eden-ghost btn-sm">Check out</button></form></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Attendance history</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=attendance&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">Export CSV</a></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3"><div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
    <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div><button class="btn btn-eden-ghost btn-sm">Apply</button></form>
  <div class="table-wrap"><?php if (!$rows): ?><?= empty_state('No attendance in this range', 'Check-ins recorded at reception appear here.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Date</th><th>Member</th><th>In</th><th>Out</th><th>Notes</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td class="small"><?= e(fmt_date($r['attend_date'])) ?></td>
        <td><a href="<?= e(url('admin/member.php?id=' . $r['mid'])) ?>"><?= e($r['name']) ?></a><br><span class="small text-muted-eden"><?= e($r['member_number']) ?></span></td>
        <td class="small"><?= e(date('H:i', strtotime($r['check_in_at']))) ?></td><td class="small"><?= $r['check_out_at'] ? e(date('H:i', strtotime($r['check_out_at']))) : 'still in' ?></td>
        <td class="small"><?= $r['is_override'] ? 'override · ' . e($r['override_reason']) : '—' ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></div>
</div></div>
<?php dash_end(); ?>
