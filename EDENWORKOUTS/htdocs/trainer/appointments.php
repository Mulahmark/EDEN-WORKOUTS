<?php
declare(strict_types=1);
require_once __DIR__ . '/_clients_helper.php';
$u = require_role(['trainer', 'admin']);
if (empty($u['trainer_id'])) { err('No trainer profile linked.'); redirect('login.php'); }
$tid = (int) $u['trainer_id'];
$clients = assigned_clients($tid);
$diary = db_all("SELECT a.*, COALESCE(CONCAT(m.first_name,' ',m.last_name),'Availability block') who FROM training_appointments a LEFT JOIN members m ON m.id=a.member_id WHERE a.trainer_id=? ORDER BY a.scheduled_at LIMIT 60", [$tid]);
$avail = db_all("SELECT * FROM trainer_availability WHERE trainer_id=? ORDER BY day_of_week", [$tid]);
$days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
dash_start($u, 'My diary', 'Training appointments and recurring availability. Overlapping bookings are rejected.', 'trainer/appointments.php');
?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Book an appointment</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=appointment.save')) ?>" method="post"><?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-12"><div class="field"><label>Client (leave blank for an availability block)</label><select class="input" name="member_id"><option value="">Trainer block</option><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option><?php endforeach; ?></select></div></div>
        <div class="col-7"><div class="field"><label>Date &amp; time</label><input class="input" type="datetime-local" name="scheduled_at" value="<?= e(now_local()) ?>" required></div></div>
        <div class="col-5"><div class="field"><label>Minutes</label><input class="input" type="number" name="duration_minutes" value="60" min="15" max="240"></div></div>
        <div class="col-12"><div class="field"><label>Notes</label><input class="input" name="notes"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Book</button></form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Weekly availability</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=availability.save')) ?>" method="post" class="d-flex flex-wrap gap-2 align-items-end mb-3"><?= csrf_field() ?>
      <div class="field mb-0"><label>Day</label><select class="input" name="day_of_week"><?php foreach ($days as $i => $d): ?><option value="<?= $i ?>"><?= $d ?></option><?php endforeach; ?></select></div>
      <div class="field mb-0"><label>From</label><input class="input" type="time" name="start_time" value="06:00"></div>
      <div class="field mb-0"><label>To</label><input class="input" type="time" name="end_time" value="20:00"></div>
      <button class="btn btn-eden-ghost btn-sm">Add</button></form>
    <?php if (!$avail): ?><p class="small text-muted-eden">No availability slots yet.</p>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($avail as $a): ?><li><i class="bi bi-clock"></i><span><?= e($days[(int) $a['day_of_week']]) ?> <?= e(substr($a['start_time'], 0, 5)) ?>–<?= e(substr($a['end_time'], 0, 5)) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Diary (<?= count($diary) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$diary): ?><?= empty_state('Nothing booked', 'Book a session using the form above.') ?>
  <?php else: ?><table class="data"><thead><tr><th>When</th><th>Client</th><th>Duration</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($diary as $a): ?><tr><td class="small"><?= e(fmt_datetime($a['scheduled_at'])) ?></td><td class="small"><?= e($a['who']) ?></td><td class="small"><?= (int) $a['duration_minutes'] ?> min</td><td><?= status_badge($a['status']) ?></td>
      <td class="d-flex gap-1"><?php foreach (['completed', 'cancelled', 'no_show'] as $st): if ($st === $a['status']) continue; ?>
        <form action="<?= e(url('actions/handle.php?do=appointment.status')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="appointment_id" value="<?= (int) $a['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>"><button class="btn btn-eden-quiet btn-sm"><?= str_replace('_', ' ', $st) ?></button></form>
      <?php endforeach; ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
