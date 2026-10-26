<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$rows = db_all("SELECT * FROM exercises ORDER BY name");
$edit = (int) get('edit'); $e = $edit ? db_one("SELECT * FROM exercises WHERE id = ?", [$edit]) : null;
dash_start($u, 'Exercise library', 'Shared catalogue used by every coach when building workout plans.', 'admin/exercises.php');
?>
<div class="panel"><div class="panel-head"><h2><?= $e ? 'Edit: ' . e($e['name']) : 'Add exercise' ?></h2><?php if ($e): ?><a class="btn btn-eden-quiet btn-sm" href="<?= e(url('admin/exercises.php')) ?>">Cancel</a><?php endif; ?></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=exercise.save')) ?>" method="post"><?= csrf_field() ?>
    <?php if ($e): ?><input type="hidden" name="exercise_id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
    <div class="row g-3">
      <div class="col-md-4"><div class="field"><label>Name *</label><input class="input" name="name" value="<?= e($e['name'] ?? '') ?>" required></div></div>
      <div class="col-md-2"><div class="field"><label>Difficulty</label><select class="input" name="difficulty"><?php foreach (['beginner', 'intermediate', 'advanced'] as $d): ?><option value="<?= $d ?>" <?= ($e['difficulty'] ?? '') === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Muscle group</label><input class="input" name="muscle_group" value="<?= e($e['muscle_group'] ?? '') ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>Category</label><input class="input" name="category" value="<?= e($e['category'] ?? '') ?>"></div></div>
      <div class="col-md-2"><div class="field"><label>Equipment</label><input class="input" name="equipment" value="<?= e($e['equipment'] ?? '') ?>"></div></div>
      <div class="col-md-6"><div class="field"><label>Description</label><textarea class="input" name="description" rows="2"><?= e($e['description'] ?? '') ?></textarea></div></div>
      <div class="col-md-6"><div class="field"><label>Instructions</label><textarea class="input" name="instructions" rows="2"><?= e($e['instructions'] ?? '') ?></textarea></div></div>
      <div class="col-md-6"><div class="field"><label>Image or video URL</label><input class="input" name="media_url" value="<?= e($e['media_url'] ?? '') ?>"></div></div>
    </div>
    <button class="btn btn-eden" type="submit"><?= $e ? 'Save exercise' : 'Add exercise' ?></button>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2><?= count($rows) ?> exercises</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No exercises found', 'Add exercises to build workout plans.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Exercise</th><th>Muscle</th><th>Equipment</th><th>Difficulty</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $x): ?><tr><td><strong><?= e($x['name']) ?></strong><br><span class="small text-muted-eden"><?= e(mb_substr((string) $x['description'], 0, 60)) ?></span></td>
      <td class="small"><?= e($x['muscle_group'] ?? '—') ?></td><td class="small"><?= e($x['equipment'] ?? '—') ?></td><td class="small"><?= e($x['difficulty']) ?></td>
      <td><?= status_badge((int) $x['is_active'] ? 'active' : 'inactive') ?></td>
      <td class="d-flex gap-1"><a class="btn btn-eden-quiet btn-sm" href="?edit=<?= (int) $x['id'] ?>">Edit</a>
        <form action="<?= e(url('actions/handle.php?do=exercise.toggle')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="exercise_id" value="<?= (int) $x['id'] ?>"><button class="btn btn-eden-quiet btn-sm"><?= (int) $x['is_active'] ? 'Hide' : 'Restore' ?></button></form></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
