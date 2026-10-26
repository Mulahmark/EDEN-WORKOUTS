<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$rows = db_all("SELECT * FROM fitness_measurements WHERE member_id=? ORDER BY measured_at DESC LIMIT 40", [$mid]);
$latest = $rows[0] ?? null;
dash_start($u, 'My progress', 'Body measurements are private to you and the staff you authorise.', 'member/progress.php');
?>
<div class="stat-grid">
  <?= stat_card('Latest weight', $latest && $latest['weight_kg'] ? $latest['weight_kg'] . ' kg' : '—', $latest ? fmt_date($latest['measured_at']) : 'No data', 'orange') ?>
  <?= stat_card('Body fat', $latest && $latest['body_fat_pct'] ? $latest['body_fat_pct'] . '%' : '—') ?>
  <?= stat_card('Records', count($rows)) ?>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Record a measurement</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=measurement.save')) ?>" method="post"><?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-6"><div class="field"><label>Date</label><input class="input" type="date" name="measured_at" value="<?= e(today()) ?>" required></div></div>
        <div class="col-6"><div class="field"><label>Weight (kg)</label><input class="input" type="number" step="0.1" name="weight_kg" value="<?= e($latest['weight_kg'] ?? '') ?>"></div></div>
        <div class="col-6"><div class="field"><label>Height (cm)</label><input class="input" type="number" step="0.1" name="height_cm" value="<?= e($latest['height_cm'] ?? '') ?>"></div></div>
        <div class="col-6"><div class="field"><label>Body fat (%)</label><input class="input" type="number" step="0.1" name="body_fat_pct" value="<?= e($latest['body_fat_pct'] ?? '') ?>"></div></div>
        <div class="col-6"><div class="field"><label>Waist (cm)</label><input class="input" type="number" step="0.1" name="waist_cm" value="<?= e($latest['waist_cm'] ?? '') ?>"></div></div>
        <div class="col-6"><div class="field"><label>Chest (cm)</label><input class="input" type="number" step="0.1" name="chest_cm" value="<?= e($latest['chest_cm'] ?? '') ?>"></div></div>
        <div class="col-6"><div class="field"><label>Arms (cm)</label><input class="input" type="number" step="0.1" name="arms_cm" value="<?= e($latest['arms_cm'] ?? '') ?>"></div></div>
        <div class="col-6"><div class="field"><label>Thighs (cm)</label><input class="input" type="number" step="0.1" name="thighs_cm" value="<?= e($latest['thighs_cm'] ?? '') ?>"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Save measurement</button></form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Measurement history</h2></div><div class="panel-body table-wrap">
    <?php if (!$rows): ?><?= empty_state('No measurements recorded', 'Record your weight and measurements to build a history.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Date</th><th>Weight</th><th>Waist</th><th>Body fat</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td class="small"><?= e(fmt_date($r['measured_at'])) ?></td><td class="small"><?= $r['weight_kg'] ? e($r['weight_kg'] . ' kg') : '—' ?></td>
        <td class="small"><?= $r['waist_cm'] ? e($r['waist_cm'] . ' cm') : '—' ?></td><td class="small"><?= $r['body_fat_pct'] ? e($r['body_fat_pct'] . '%') : '—' ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </div></div>
</div>
<?php dash_end(); ?>
