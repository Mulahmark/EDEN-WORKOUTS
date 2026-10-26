<?php
declare(strict_types=1);
require_once __DIR__ . '/_clients_helper.php';
$u = require_role(['trainer', 'admin']);
if (empty($u['trainer_id'])) { err('No trainer profile linked.'); redirect('login.php'); }
$clients = assigned_clients((int) $u['trainer_id']);
$ids = array_column($clients, 'id');
$exercises = db_all("SELECT id, name FROM exercises WHERE is_active=1 ORDER BY name");
$sessions = $ids ? db_all("SELECT s.*, CONCAT(m.first_name,' ',m.last_name) name FROM workout_sessions s JOIN members m ON m.id=s.member_id WHERE s.member_id IN (" . implode(',', array_map('intval', $ids)) . ") ORDER BY s.performed_at DESC LIMIT 40") : [];
dash_start($u, 'Training sessions', 'Record sessions for your assigned clients and review their logged history.', 'trainer/sessions.php');
?>
<div class="panel"><div class="panel-head"><h2>Log a session</h2></div><div class="panel-body">
  <?php if (!$clients): ?><p class="small text-muted-eden">No clients assigned yet.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=session.log')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-4"><div class="field"><label>Client</label><select class="input" name="member_id" required><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Date &amp; time</label><input class="input" type="datetime-local" name="performed_at" value="<?= e(now_local()) ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>Duration</label><select class="input" name="duration_minutes"><option value="">—</option><?php foreach ([30, 45, 60, 75, 90] as $m): ?><option value="<?= $m ?>"><?= $m ?> min</option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Status</label><select class="input" name="completion"><option value="completed">Completed</option><option value="partial">Partial</option><option value="missed">Missed</option></select></div></div>
      <?php for ($i = 0; $i < 3; $i++): ?>
        <div class="col-md-2"><div class="field"><label>Exercise <?= $i + 1 ?></label><select class="input" name="exercise_id[]"><option value="">—</option><?php foreach ($exercises as $x): ?><option value="<?= (int) $x['id'] ?>"><?= e($x['name']) ?></option><?php endforeach; ?></select></div></div>
        <div class="col-md-1"><div class="field"><label>Sets</label><input class="input" type="number" name="sets[]" min="0"></div></div>
        <div class="col-md-1"><div class="field"><label>Reps</label><input class="input" type="number" name="reps[]" min="0"></div></div>
        <div class="col-md-1"><div class="field"><label>kg</label><input class="input" type="number" step="0.5" name="weight[]"></div></div>
        <div class="col-md-3"><div class="field"><label>Note</label><input class="input" name="line_notes[]"></div></div>
      <?php endfor; ?>
      <div class="col-12"><div class="field"><label>Session notes</label><input class="input" name="notes"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Log session</button></form><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Session history</h2></div><div class="panel-body table-wrap">
  <?php if (!$sessions): ?><?= empty_state('No sessions recorded', 'Sessions you or your clients log appear here.') ?>
  <?php else: ?><table class="data"><thead><tr><th>When</th><th>Client</th><th>Duration</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($sessions as $s): ?><tr><td class="small"><?= e(fmt_datetime($s['performed_at'])) ?></td><td class="small"><?= e($s['name']) ?></td><td class="small"><?= (int) $s['duration_minutes'] ?> min</td><td><?= status_badge($s['completion']) ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
