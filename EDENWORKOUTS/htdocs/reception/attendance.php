<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['receptionist', 'admin']);
$from = get('from') ?: today(); $to = get('to') ?: today();
$rows = db_all("SELECT a.*, CONCAT(m.first_name,' ',m.last_name) name, m.member_number FROM attendance a JOIN members m ON m.id=a.member_id WHERE a.attend_date BETWEEN ? AND ? ORDER BY a.check_in_at DESC LIMIT 100", [$from, $to]);
dash_start($u, 'Attendance history', 'Read-only view. Overrides are recorded by administrators.', 'reception/attendance.php');
?>
<div class="panel"><div class="panel-head"><h2><?= count($rows) ?> visits</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=attendance&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">Export CSV</a></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3"><div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
    <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div><button class="btn btn-eden-ghost btn-sm">Apply</button></form>
  <div class="table-wrap"><?php if (!$rows): ?><?= empty_state('No attendance in range', 'Try another date range.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Date</th><th>Member</th><th>In</th><th>Out</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td class="small"><?= e(fmt_date($r['attend_date'])) ?></td><td class="small"><?= e($r['name']) ?><br><span class="text-muted-eden"><?= e($r['member_number']) ?></span></td>
        <td class="small"><?= e(date('H:i', strtotime($r['check_in_at']))) ?></td><td class="small"><?= $r['check_out_at'] ? e(date('H:i', strtotime($r['check_out_at']))) : 'in gym' ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div>
</div></div>
<?php dash_end(); ?>
