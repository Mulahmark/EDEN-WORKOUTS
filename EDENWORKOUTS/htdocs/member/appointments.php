<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$rows = db_all("SELECT a.*, t.full_name trainer FROM training_appointments a JOIN trainers t ON t.id=a.trainer_id WHERE a.member_id=? ORDER BY a.scheduled_at DESC LIMIT 40", [$mid]);
dash_start($u, 'My appointments', 'Personal training sessions booked for you. Bookings are made by your coach or reception.', 'member/appointments.php');
?>
<div class="stat-grid"><?= stat_card('Scheduled', count(array_filter($rows, fn($r) => $r['status'] === 'scheduled')), '', 'orange') ?>
  <?= stat_card('Completed', count(array_filter($rows, fn($r) => $r['status'] === 'completed')), '', 'ok') ?>
  <?= stat_card('Cancelled / no-show', count(array_filter($rows, fn($r) => in_array($r['status'], ['cancelled', 'no_show'], true))), '', 'bad') ?></div>
<div class="panel"><div class="panel-head"><h2>Appointment history</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No appointments', 'Once your coach books a session it appears here.') ?>
  <?php else: ?><table class="data"><thead><tr><th>When</th><th>Coach</th><th>Duration</th><th>Status</th><th>Notes</th></tr></thead><tbody>
    <?php foreach ($rows as $a): ?><tr><td class="small"><?= e(fmt_datetime($a['scheduled_at'])) ?></td><td class="small"><?= e($a['trainer']) ?></td><td class="small"><?= (int) $a['duration_minutes'] ?> min</td>
      <td><?= status_badge($a['status']) ?></td><td class="small"><?= e($a['notes'] ?? '—') ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
