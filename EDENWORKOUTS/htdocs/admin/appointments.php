<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$trainers = db_all("SELECT id, full_name FROM trainers WHERE employment_status='active' ORDER BY full_name");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$rows = db_all("SELECT a.*, t.full_name trainer, CONCAT(m.first_name,' ',m.last_name) name FROM training_appointments a JOIN trainers t ON t.id=a.trainer_id LEFT JOIN members m ON m.id=a.member_id ORDER BY a.scheduled_at DESC LIMIT 60");
dash_start($u, 'Training appointments', 'Schedules are checked for overlaps per trainer before anything is saved.', 'admin/appointments.php');
?>
<div class="panel"><div class="panel-head"><h2>Book an appointment</h2></div><div class="panel-body">
  <?php if (!$trainers): ?><p class="small text-muted-eden">Register a trainer first.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=appointment.save')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-3"><div class="field"><label>Trainer</label><select class="input" name="trainer_id" required><?php foreach ($trainers as $t): ?><option value="<?= (int) $t['id'] ?>"><?= e($t['full_name']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-3"><div class="field"><label>Member</label><select class="input" name="member_id"><option value="">Trainer block</option><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-3"><div class="field"><label>Date &amp; time</label><input class="input" type="datetime-local" name="scheduled_at" value="<?= e(now_local()) ?>" required></div></div>
      <div class="col-md-1"><div class="field"><label>Mins</label><input class="input" type="number" name="duration_minutes" value="60" min="15" max="240"></div></div>
      <div class="col-md-2"><div class="field"><label>Notes</label><input class="input" name="notes"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Book appointment</button>
  </form><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Diary (<?= count($rows) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('Nothing booked', 'Appointments appear here once scheduled.') ?>
  <?php else: ?><table class="data"><thead><tr><th>When</th><th>Trainer</th><th>Member</th><th>Duration</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $a): ?><tr><td class="small"><?= e(fmt_datetime($a['scheduled_at'])) ?></td><td class="small"><?= e($a['trainer']) ?></td>
      <td class="small"><?= e($a['name'] ?? 'Availability block') ?></td><td class="small"><?= (int) $a['duration_minutes'] ?> min</td><td><?= status_badge($a['status']) ?></td>
      <td class="d-flex gap-1"><?php foreach (['completed', 'cancelled', 'no_show'] as $st): if ($st === $a['status']) continue; ?>
        <form action="<?= e(url('actions/handle.php?do=appointment.status')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>"><button class="btn btn-eden-quiet btn-sm"><?= str_replace('_', ' ', $st) ?></button></form>
      <?php endforeach; ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
