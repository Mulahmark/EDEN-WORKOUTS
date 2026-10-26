<?php
declare(strict_types=1);
require_once __DIR__ . '/_clients_helper.php';
$u = require_role(['trainer', 'admin']);
if (empty($u['trainer_id'])) { err('No trainer profile linked.'); redirect('login.php'); }
$clients = assigned_clients((int) $u['trainer_id']);
$mid = (int) get('member_id');
if (!$mid && $clients) $mid = (int) $clients[0]['id'];
if ($mid) guard_client($mid, $u);
$measurements = $mid ? db_all("SELECT * FROM fitness_measurements WHERE member_id=? ORDER BY measured_at DESC LIMIT 30", [$mid]) : [];
$goals = $mid ? db_all("SELECT * FROM fitness_goals WHERE member_id=? ORDER BY created_at DESC", [$mid]) : [];
dash_start($u, 'Client progress', 'Measurements and goals for your assigned clients only.', 'trainer/progress.php');
?>
<form method="get" class="panel panel-body d-flex flex-wrap gap-2 align-items-end">
  <div class="field mb-0"><label>Client</label><select class="input" name="member_id"><?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $mid === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['first_name'] . ' ' . $c['last_name']) ?></option><?php endforeach; ?></select></div>
  <button class="btn btn-eden-ghost btn-sm">Load</button></form>
<?php if (!$clients): ?><div class="panel panel-body"><?= empty_state('No clients assigned', 'Client progress appears once an administrator assigns members to you.') ?></div>
<?php elseif ($mid): ?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Record measurement</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=measurement.save')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="member_id" value="<?= $mid ?>">
      <div class="row g-2">
        <div class="col-6"><div class="field"><label>Date</label><input class="input" type="date" name="measured_at" value="<?= e(today()) ?>" required></div></div>
        <div class="col-6"><div class="field"><label>Weight (kg)</label><input class="input" type="number" step="0.1" name="weight_kg"></div></div>
        <div class="col-6"><div class="field"><label>Body fat (%)</label><input class="input" type="number" step="0.1" name="body_fat_pct"></div></div>
        <div class="col-6"><div class="field"><label>Waist (cm)</label><input class="input" type="number" step="0.1" name="waist_cm"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Save measurement</button></form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Add goal</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=goal.save')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="member_id" value="<?= $mid ?>">
      <div class="field"><label>Title</label><input class="input" name="title" required></div>
      <div class="row g-2"><div class="col-4"><div class="field"><label>Start</label><input class="input" type="number" step="0.1" name="start_value"></div></div>
        <div class="col-4"><div class="field"><label>Target</label><input class="input" type="number" step="0.1" name="target_value"></div></div>
        <div class="col-4"><div class="field"><label>Unit</label><input class="input" name="unit" placeholder="kg"></div></div></div>
      <button class="btn btn-eden" type="submit">Create goal</button></form>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Measurements (<?= count($measurements) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$measurements): ?><?= empty_state('No measurements', 'Record the first measurement above.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Date</th><th>Weight</th><th>Waist</th><th>Body fat</th></tr></thead><tbody>
    <?php foreach ($measurements as $m): ?><tr><td class="small"><?= e(fmt_date($m['measured_at'])) ?></td><td class="small"><?= $m['weight_kg'] ? e($m['weight_kg'] . ' kg') : '—' ?></td>
      <td class="small"><?= $m['waist_cm'] ? e($m['waist_cm'] . ' cm') : '—' ?></td><td class="small"><?= $m['body_fat_pct'] ? e($m['body_fat_pct'] . '%') : '—' ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div></div>
<?php if ($goals): ?><div class="panel"><div class="panel-head"><h2>Goals</h2></div><div class="panel-body"><ul class="plan-benefits">
  <?php foreach ($goals as $g): ?><li><i class="bi bi-bullseye"></i><span><?= e($g['title']) ?> — <?= e(($g['current_value'] ?? '—') . ' / ' . ($g['target_value'] ?? '—')) ?> <?= status_badge($g['status']) ?></span></li><?php endforeach; ?>
</ul></div></div><?php endif; ?>
<?php endif; ?>
<?php dash_end(); ?>
