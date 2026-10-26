<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$edit = (int) get('edit');
$rows = db_all("SELECT p.*, (SELECT COUNT(*) FROM memberships m WHERE m.plan_id = p.id) subs FROM membership_plans p ORDER BY p.price");
$e = $edit ? db_one("SELECT * FROM membership_plans WHERE id = ?", [$edit]) : null;
dash_start($u, 'Membership plans', 'Plans published here appear on the public website. Editing a price never changes an existing subscription\'s agreed price.', 'admin/plans.php');
?>
<div class="panel"><div class="panel-head"><h2><?= $e ? 'Edit: ' . e($e['name']) : 'Create membership plan' ?></h2><?php if ($e): ?><a class="btn btn-eden-quiet btn-sm" href="<?= e(url('admin/plans.php')) ?>">Cancel edit</a><?php endif; ?></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=plan.save')) ?>" method="post">
    <?= csrf_field() ?><?php if ($e): ?><input type="hidden" name="plan_id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
    <div class="row g-3">
      <div class="col-md-4"><div class="field"><label>Plan name *</label><input class="input" name="name" value="<?= e($e['name'] ?? '') ?>" required></div></div>
      <div class="col-md-2"><div class="field"><label>Duration</label><input class="input" type="number" min="1" name="duration_value" value="<?= e($e['duration_value'] ?? 1) ?>" required></div></div>
      <div class="col-md-3"><div class="field"><label>Unit</label><select class="input" name="duration_unit">
        <?php foreach (['day' => 'Day(s)', 'week' => 'Week(s)', 'month' => 'Month(s)', 'quarter' => 'Quarter(s)', 'year' => 'Year(s)'] as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($e['duration_unit'] ?? 'month') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
      </select></div></div>
      <div class="col-md-3"><div class="field"><label>Price (<?= e(setting('currency', 'KES')) ?>) *</label><input class="input" type="number" step="0.01" min="0" name="price" value="<?= e($e['price'] ?? '') ?>" required></div></div>
      <div class="col-md-3"><div class="field"><label>Joining fee</label><input class="input" type="number" step="0.01" min="0" name="joining_fee" value="<?= e($e['joining_fee'] ?? '0') ?>"></div></div>
      <div class="col-md-9"><div class="field"><label>Access restrictions (optional)</label><input class="input" name="access_notes" value="<?= e($e['access_notes'] ?? '') ?>"></div></div>
      <div class="col-md-6"><div class="field"><label>Description</label><textarea class="input" name="description" rows="3"><?= e($e['description'] ?? '') ?></textarea></div></div>
      <div class="col-md-6"><div class="field"><label>Benefits (one per line)</label><textarea class="input" name="benefits" rows="4"><?= e($e['benefits'] ?? '') ?></textarea></div></div>
    </div>
    <button class="btn btn-eden" type="submit"><?= $e ? 'Save changes' : 'Create plan' ?></button>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2>Plan catalogue (<?= count($rows) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No plans yet', 'Create your first plan — it publishes to the website immediately.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Duration</th><th>Price</th><th>Joining fee</th><th>Subs</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $p): ?><tr>
      <td><strong><?= e($p['name']) ?></strong><br><span class="small text-muted-eden"><?= e(mb_substr((string) $p['description'], 0, 70)) ?></span></td>
      <td class="small"><?= e(duration_label((int) $p['duration_value'], $p['duration_unit'])) ?></td>
      <td><?= e(money($p['price'])) ?></td><td class="small"><?= e(money($p['joining_fee'])) ?></td><td class="small"><?= (int) $p['subs'] ?></td>
      <td><?= status_badge((int) $p['is_active'] ? 'active' : 'inactive') ?></td>
      <td class="d-flex gap-1"><a class="btn btn-eden-quiet btn-sm" href="?edit=<?= (int) $p['id'] ?>">Edit</a>
        <form action="<?= e(url('actions/handle.php?do=plan.toggle')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $p['id'] ?>"><button class="btn btn-eden-quiet btn-sm"><?= (int) $p['is_active'] ? 'Hide' : 'Publish' ?></button></form></td>
    </tr><?php endforeach; ?></tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
