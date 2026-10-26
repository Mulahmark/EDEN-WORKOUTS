<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$exercises = db_all("SELECT id, name FROM exercises WHERE is_active=1 ORDER BY name");
$plans = db_all("SELECT id, name FROM workout_plans WHERE member_id=? ORDER BY created_at DESC", [$mid]);
$sessions = db_all("SELECT s.*, (SELECT COUNT(*) FROM workout_session_exercises se WHERE se.session_id=s.id) line_count FROM workout_sessions s WHERE s.member_id=? ORDER BY s.performed_at DESC LIMIT 30", [$mid]);
dash_start($u, 'Log a workout', 'Record what you actually did — sets, reps and load — so your coach can progress the plan.', 'member/sessions.php');
?>
<div class="stat-grid"><?= stat_card('Sessions logged', count($sessions), '', 'orange') ?><?= stat_card('Exercises available', count($exercises)) ?></div>
<div class="panel"><div class="panel-head"><h2>New session</h2></div><div class="panel-body">
  <?php if (!$exercises): ?><p class="small text-muted-eden">The exercise library is empty — ask your coach to add exercises.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=session.log')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-4"><div class="field"><label>Workout plan</label><select class="input" name="plan_id"><option value="">Ad-hoc session</option><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Date &amp; time</label><input class="input" type="datetime-local" name="performed_at" value="<?= e(now_local()) ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>Duration</label><select class="input" name="duration_minutes"><option value="">—</option><?php foreach ([30, 45, 60, 75, 90] as $m): ?><option value="<?= $m ?>"><?= $m ?> min</option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Status</label><select class="input" name="completion"><option value="completed">Completed</option><option value="partial">Partial</option></select></div></div>
      <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="col-md-3"><div class="field"><label>Exercise <?= $i + 1 ?></label><select class="input" name="exercise_id[]"><option value="">—</option><?php foreach ($exercises as $x): ?><option value="<?= (int) $x['id'] ?>"><?= e($x['name']) ?></option><?php endforeach; ?></select></div></div>
        <div class="col-md-2"><div class="field"><label>Sets</label><input class="input" type="number" name="sets[]" min="0" max="50"></div></div>
        <div class="col-md-2"><div class="field"><label>Reps</label><input class="input" type="number" name="reps[]" min="0" max="1000"></div></div>
        <div class="col-md-2"><div class="field"><label>Weight kg</label><input class="input" type="number" step="0.5" name="weight[]"></div></div>
        <div class="col-md-3"><div class="field"><label>Note</label><input class="input" name="line_notes[]"></div></div>
      <?php endfor; ?>
      <div class="col-12"><div class="field"><label>Session notes</label><input class="input" name="notes"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Log session</button></form><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Session history</h2></div><div class="panel-body table-wrap">
  <?php if (!$sessions): ?><?= empty_state('No sessions yet', 'Log your first workout using the form above.') ?>
  <?php else: ?><table class="data"><thead><tr><th>When</th><th>Exercises</th><th>Duration</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($sessions as $s): ?><tr><td class="small"><?= e(fmt_datetime($s['performed_at'])) ?></td><td class="small"><?= (int) $s['line_count'] ?></td><td class="small"><?= $s['duration_minutes'] ? (int) $s['duration_minutes'] . ' min' : '—' ?></td><td><?= status_badge($s['completion']) ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
  <p class="hint mt-2">Workout tracking supports your training programme. It is not medical advice.</p>
</div></div>
<?php dash_end(); ?>
