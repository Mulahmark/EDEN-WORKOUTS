<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$goals = db_all("SELECT * FROM fitness_goals WHERE member_id=? ORDER BY created_at DESC", [$mid]);
dash_start($u, 'My goals', 'Set targets, update your current value and track progress from real records.', 'member/goals.php');
?>
<div class="stat-grid"><?= stat_card('Active goals', count(array_filter($goals, fn($g) => $g['status'] === 'active')), '', 'orange') ?>
  <?= stat_card('Completed', count(array_filter($goals, fn($g) => $g['status'] === 'completed')), '', 'ok') ?><?= stat_card('All goals', count($goals)) ?></div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Create a goal</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=goal.save')) ?>" method="post"><?= csrf_field() ?>
      <div class="field"><label>Goal title</label><input class="input" name="title" required placeholder="Reach 75 kg body weight"></div>
      <div class="row g-2">
        <div class="col-6"><div class="field"><label>Type</label><select class="input" name="goal_type"><?php foreach (['general' => 'General', 'strength' => 'Strength', 'endurance' => 'Endurance', 'weight' => 'Weight', 'consistency' => 'Consistency'] as $k => $v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?></select></div></div>
        <div class="col-6"><div class="field"><label>Status</label><select class="input" name="status"><option value="active">Active</option><option value="completed">Completed</option><option value="paused">Paused</option></select></div></div>
        <div class="col-4"><div class="field"><label>Start value</label><input class="input" type="number" step="0.1" name="start_value"></div></div>
        <div class="col-4"><div class="field"><label>Target value</label><input class="input" type="number" step="0.1" name="target_value"></div></div>
        <div class="col-4"><div class="field"><label>Unit</label><input class="input" name="unit" placeholder="kg"></div></div>
        <div class="col-6"><div class="field"><label>Start date</label><input class="input" type="date" name="start_date" value="<?= e(today()) ?>"></div></div>
        <div class="col-6"><div class="field"><label>Target date</label><input class="input" type="date" name="target_date"></div></div>
      </div>
      <div class="field"><label>Description</label><textarea class="input" name="description" rows="2"></textarea></div>
      <button class="btn btn-eden" type="submit">Create goal</button></form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>My goals</h2></div><div class="panel-body">
    <?php if (!$goals): ?><?= empty_state('No goals yet', 'Create your first goal to start tracking.') ?>
    <?php else: foreach ($goals as $g): $p = pct_of($g['current_value'], $g['start_value'], $g['target_value']); ?>
      <div class="card-eden mb-2" style="padding:14px">
        <div class="d-flex justify-content-between align-items-center"><strong><?= e($g['title']) ?></strong><?= status_badge($g['status']) ?></div>
        <?php if ($p !== null): ?><div style="height:8px;background:var(--elevated);border-radius:999px;margin:8px 0"><div style="height:8px;width:<?= $p ?>%;background:var(--orange);border-radius:999px"></div></div>
          <p class="small text-muted-eden mb-2"><?= e($g['current_value'] . ' / ' . $g['target_value'] . ' ' . $g['unit']) ?> · <?= $p ?>%</p>
        <?php else: ?><p class="small text-muted-eden">Add a start and target value to see progress.</p><?php endif; ?>
        <form action="<?= e(url('actions/handle.php?do=goal.progress')) ?>" method="post" class="d-flex flex-wrap gap-2 align-items-end"><?= csrf_field() ?>
          <input type="hidden" name="goal_id" value="<?= (int) $g['id'] ?>">
          <div class="field mb-0"><label>Current</label><input class="input" type="number" step="0.1" name="current_value" value="<?= e($g['current_value']) ?>" style="width:110px"></div>
          <div class="field mb-0"><label>Status</label><select class="input" name="status"><?php foreach (['active', 'completed', 'paused', 'abandoned'] as $st): ?><option value="<?= $st ?>" <?= $g['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select></div>
          <div class="field mb-0 flex-grow-1"><label>Note</label><input class="input" name="progress_notes" value="<?= e($g['progress_notes']) ?>"></div>
          <button class="btn btn-eden btn-sm">Save</button></form>
      </div>
    <?php endforeach; endif; ?>
  </div></div>
</div>
<?php dash_end(); ?>
