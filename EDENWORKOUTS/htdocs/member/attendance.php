<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$rows = db_all("SELECT * FROM attendance WHERE member_id=? ORDER BY check_in_at DESC LIMIT 50", [$mid]);
$count30 = (int) db_val("SELECT COUNT(*) FROM attendance WHERE member_id=? AND attend_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)", [$mid]);
dash_start($u, 'My attendance', 'Every visit recorded by reception.', 'member/attendance.php');
?>
<div class="stat-grid"><?= stat_card('Visits (30 days)', $count30, '', 'orange') ?><?= stat_card('Visits shown', count($rows)) ?><?= stat_card('Open visits', count(array_filter($rows, fn($r) => !$r['check_out_at'])), 'Currently in the gym', 'ok') ?></div>
<div class="panel"><div class="panel-head"><h2>Visit history</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No visits yet', 'Check in at reception on your first visit.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Date</th><th>Check-in</th><th>Check-out</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td class="small"><?= e(fmt_date($r['attend_date'])) ?></td><td class="small"><?= e(date('H:i', strtotime($r['check_in_at']))) ?></td>
      <td class="small"><?= $r['check_out_at'] ? e(date('H:i', strtotime($r['check_out_at']))) : 'in gym' ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
