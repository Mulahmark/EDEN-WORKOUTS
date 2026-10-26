<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$mid = (int) get('member_id');
$members = db_all("SELECT id, first_name, last_name, member_number FROM members ORDER BY first_name");
$rows = $mid
  ? db_all("SELECT * FROM fitness_measurements WHERE member_id = ? ORDER BY measured_at DESC LIMIT 30", [$mid])
  : db_all("SELECT f.*, CONCAT(m.first_name,' ',m.last_name) name, m.id mid FROM fitness_measurements f JOIN members m ON m.id=f.member_id ORDER BY f.measured_at DESC LIMIT 25");
$goals = $mid ? db_all("SELECT * FROM fitness_goals WHERE member_id = ? ORDER BY created_at DESC", [$mid]) : [];
$member = $mid ? db_one("SELECT first_name, last_name FROM members WHERE id = ?", [$mid]) : null;
dash_start($u, 'Fitness progress', 'Body measurements and goals. Records are private to the member and authorised staff.', 'admin/progress.php');
?>
<form method="get" class="panel panel-body d-flex flex-wrap gap-2 align-items-end">
  <div class="field mb-0"><label>Member</label><select class="input" name="member_id"><option value="">All members (latest records)</option>
    <?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>" <?= $mid === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_number'] . ')') ?></option><?php endforeach; ?></select></div>
  <button class="btn btn-eden-ghost btn-sm">Load</button>
</form>
<?php if ($mid): ?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Record measurement — <?= e($member['first_name'] ?? '') ?></h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=measurement.save')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="member_id" value="<?= $mid ?>">
      <div class="row g-2">
        <div class="col-6"><div class="field"><label>Date</label><input class="input" type="date" name="measured_at" value="<?= e(today()) ?>" required></div></div>
        <div class="col-6"><div class="field"><label>Weight (kg)</label><input class="input" type="number" step="0.1" name="weight_kg"></div></div>
        <div class="col-6"><div class="field"><label>Body fat (%)</label><input class="input" type="number" step="0.1" name="body_fat_pct"></div></div>
        <div class="col-6"><div class="field"><label>Waist (cm)</label><input class="input" type="number" step="0.1" name="waist_cm"></div></div>
        <div class="col-6"><div class="field"><label>Chest (cm)</label><input class="input" type="number" step="0.1" name="chest_cm"></div></div>
        <div class="col-6"><div class="field"><label>Arms (cm)</label><input class="input" type="number" step="0.1" name="arms_cm"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Save measurement</button>
    </form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Add goal</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=goal.save')) ?>" method="post"><?= csrf_field() ?>
      <input type="hidden" name="member_id" value="<?= $mid ?>">
      <div class="row g-2">
        <div class="col-12"><div class="field"><label>Title</label><input class="input" name="title" required></div></div>
        <div class="col-6"><div class="field"><label>Start value</label><input class="input" type="number" step="0.1" name="start_value"></div></div>
        <div class="col-6"><div class="field"><label>Target value</label><input class="input" type="number" step="0.1" name="target_value"></div></div>
        <div class="col-6"><div class="field"><label>Unit</label><input class="input" name="unit" placeholder="kg"></div></div>
        <div class="col-6"><div class="field"><label>Target date</label><input class="input" type="date" name="target_date"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Create goal</button>
    </form>
  </div></div>
</div>
<?php endif; ?>
<div class="panel"><div class="panel-head"><h2>Measurements (<?= count($rows) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No measurements recorded', 'Select a member above and record their first measurement.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Date</th><?php if (!$mid): ?><th>Member</th><?php endif; ?><th>Weight</th><th>Body fat</th><th>Waist</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td class="small"><?= e(fmt_date($r['measured_at'])) ?></td>
      <?php if (!$mid): ?><td><a href="?member_id=<?= (int) $r['mid'] ?>"><?= e($r['name']) ?></a></td><?php endif; ?>
      <td class="small"><?= $r['weight_kg'] ? e($r['weight_kg'] . ' kg') : '—' ?></td><td class="small"><?= $r['body_fat_pct'] ? e($r['body_fat_pct'] . '%') : '—' ?></td><td class="small"><?= $r['waist_cm'] ? e($r['waist_cm'] . ' cm') : '—' ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php if ($mid && $goals): ?>
<div class="panel"><div class="panel-head"><h2>Goals</h2></div><div class="panel-body"><ul class="plan-benefits">
  <?php foreach ($goals as $g): ?><li><i class="bi bi-bullseye"></i><span><?= e($g['title']) ?> — <?= e(($g['current_value'] ?? '—') . ' / ' . ($g['target_value'] ?? '—') . ' ' . ($g['unit'] ?? '')) ?> <?= status_badge($g['status']) ?></span></li><?php endforeach; ?>
</ul></div></div>
<?php endif; ?>
<?php dash_end(); ?>
