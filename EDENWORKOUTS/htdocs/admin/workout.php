<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$id = (int) get('id');
$p = db_one("SELECT w.*, CONCAT(m.first_name,' ',m.last_name) member_name, m.member_number FROM workout_plans w JOIN members m ON m.id=w.member_id WHERE w.id = ?", [$id]);
if (!$p) { err('Plan not found.'); redirect('admin/workouts.php'); }
$lines = db_all("SELECT pe.*, e.name, e.muscle_group FROM workout_plan_exercises pe JOIN exercises e ON e.id=pe.exercise_id WHERE pe.plan_id = ? ORDER BY pe.training_day, pe.order_index", [$id]);
$exercises = db_all("SELECT id, name, muscle_group FROM exercises WHERE is_active=1 ORDER BY name");
dash_start($u, $p['name'], 'Member: ' . $p['member_name'] . ' (' . $p['member_number'] . ') · status ' . $p['status'], 'admin/workouts.php');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-eden-quiet btn-sm" href="<?= e(url('admin/workouts.php')) ?>">← All plans</a>
  <?php foreach (['draft', 'active', 'completed', 'archived'] as $st): if ($st === $p['status']) continue; ?>
    <form action="<?= e(url('actions/handle.php?do=workout.status')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="status" value="<?= $st ?>"><button class="btn btn-eden-ghost btn-sm">Mark <?= $st ?></button></form>
  <?php endforeach; ?>
  <form action="<?= e(url('actions/handle.php?do=workout.duplicate')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>"><button class="btn btn-eden-ghost btn-sm">Duplicate</button></form>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Plan details</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=workout.save')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="member_id" value="<?= (int) $p['member_id'] ?>">
      <div class="row g-2">
        <div class="col-12"><div class="field"><label>Name</label><input class="input" name="name" value="<?= e($p['name']) ?>" required></div></div>
        <div class="col-6"><div class="field"><label>Start</label><input class="input" type="date" name="start_date" value="<?= e($p['start_date']) ?>"></div></div>
        <div class="col-6"><div class="field"><label>End</label><input class="input" type="date" name="end_date" value="<?= e($p['end_date']) ?>"></div></div>
        <div class="col-12"><div class="field"><label>Status</label><select class="input" name="status"><?php foreach (['draft', 'active', 'completed', 'archived'] as $st): ?><option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select></div></div>
        <div class="col-12"><div class="field"><label>Weekly schedule</label><input class="input" name="weekly_schedule" value="<?= e($p['weekly_schedule']) ?>"></div></div>
        <div class="col-12"><div class="field"><label>Goals</label><textarea class="input" name="goals" rows="2"><?= e($p['goals']) ?></textarea></div></div>
        <div class="col-12"><div class="field"><label>Coach notes</label><textarea class="input" name="notes" rows="2"><?= e($p['notes']) ?></textarea></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Save plan</button>
    </form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Add exercise</h2></div><div class="panel-body">
    <?php if (!$exercises): ?><p class="small text-muted-eden">Add exercises to the library first.</p>
    <?php else: ?><form action="<?= e(url('actions/handle.php?do=workout.exercise_add')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>">
      <div class="row g-2">
        <div class="col-12"><div class="field"><label>Exercise</label><select class="input" name="exercise_id" required><?php foreach ($exercises as $x): ?><option value="<?= (int) $x['id'] ?>"><?= e($x['name'] . ($x['muscle_group'] ? ' — ' . $x['muscle_group'] : '')) ?></option><?php endforeach; ?></select></div></div>
        <div class="col-6"><div class="field"><label>Training day</label><select class="input" name="training_day"><?php for ($d = 1; $d <= 7; $d++): ?><option value="Day <?= $d ?>">Day <?= $d ?></option><?php endfor; ?></select></div></div>
        <div class="col-3"><div class="field"><label>Sets</label><input class="input" type="number" name="sets" value="3" min="1" max="20"></div></div>
        <div class="col-3"><div class="field"><label>Reps</label><input class="input" name="reps" value="10"></div></div>
        <div class="col-4"><div class="field"><label>Target kg</label><input class="input" type="number" step="0.5" name="target_weight"></div></div>
        <div class="col-4"><div class="field"><label>Rest (sec)</label><input class="input" type="number" name="rest_seconds" value="60"></div></div>
        <div class="col-4"><div class="field"><label>Instructions</label><input class="input" name="instructions"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Add to plan</button>
    </form><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Plan exercises (<?= count($lines) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$lines): ?><?= empty_state('No exercises yet', 'Use the form above to build the training days.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Day</th><th>Exercise</th><th>Sets × Reps</th><th>Target</th><th>Rest</th><th></th></tr></thead><tbody>
    <?php foreach ($lines as $l): ?><tr><td class="small text-eden"><?= e($l['training_day']) ?></td><td><?= e($l['name']) ?><br><span class="small text-muted-eden"><?= e($l['muscle_group'] ?? '') ?></span></td>
      <td class="small"><?= (int) $l['sets'] ?> × <?= e($l['reps']) ?></td><td class="small"><?= $l['target_weight'] ? e($l['target_weight'] . ' kg') : '—' ?></td>
      <td class="small"><?= (int) $l['rest_seconds'] ?>s</td>
      <td><form action="<?= e(url('actions/handle.php?do=workout.exercise_remove')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="line_id" value="<?= (int) $l['id'] ?>"><button class="btn btn-eden-danger btn-sm">Remove</button></form></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
