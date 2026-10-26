<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
expiry_sweep();
$status = get('status'); $search = get('search'); $page = page_param(); $per = 15;
$where = []; $args = [];
if ($status !== '') { $where[] = 'ms.status = ?'; $args[] = $status; }
if ($search !== '') { $where[] = '(m.first_name LIKE ? OR m.last_name LIKE ? OR m.member_number LIKE ?)'; array_push($args, "%$search%", "%$search%", "%$search%"); }
$sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int) db_val("SELECT COUNT(*) FROM memberships ms JOIN members m ON m.id = ms.member_id" . $sql, $args);
$rows = db_all("SELECT ms.*, p.name plan, m.first_name, m.last_name, m.member_number, m.id mid FROM memberships ms JOIN members m ON m.id = ms.member_id JOIN membership_plans p ON p.id = ms.plan_id" . $sql . " ORDER BY ms.end_date DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
$plans = db_all("SELECT * FROM membership_plans WHERE is_active = 1 ORDER BY name");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members ORDER BY first_name");
dash_start($u, 'Membership subscriptions', 'Each subscription stores its own agreed price, discount, joining fee, paid total and expiry date.', 'admin/memberships.php');
?>
<div class="panel"><div class="panel-head"><h2>New subscription</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=subscriptions')) ?>">Export CSV</a></div><div class="panel-body">
  <?php if (!$plans || !$members): ?><p class="small text-muted-eden">You need at least one active plan and one member before selling memberships.</p>
  <?php else: ?>
  <form action="<?= e(url('actions/handle.php?do=subscription.create')) ?>" method="post">
    <?= csrf_field() ?><input type="hidden" name="back" value="admin/memberships.php">
    <div class="row g-3">
      <div class="col-md-4"><div class="field"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_number'] . ')') ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Plan</label><select class="input" name="plan_id" required><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> — <?= e(money($p['price'])) ?> / <?= e(duration_label((int) $p['duration_value'], $p['duration_unit'])) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Start date</label><input class="input" type="date" name="start_date" value="<?= e(today()) ?>" required></div></div>
      <div class="col-md-2"><div class="field"><label>Discount</label><input class="input" type="number" step="0.01" min="0" name="discount" value="0"></div></div>
    </div>
    <label class="small text-muted-eden"><input type="checkbox" name="allow_duplicate" value="1"> Allow a second active subscription (override)</label>
    <button class="btn btn-eden mt-2" type="submit">Create subscription</button>
  </form>
  <?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Subscriptions (<?= $total ?>)</h2></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div><label class="small text-muted-eden">Member</label><input class="input" name="search" value="<?= e($search) ?>"></div>
    <div><label class="small text-muted-eden">Status</label><select class="input" name="status"><option value="">All</option>
      <?php foreach (['active', 'pending', 'expired', 'suspended', 'cancelled'] as $s): ?><option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-eden-ghost btn-sm">Apply</button><a class="btn btn-eden-quiet btn-sm" href="<?= e(url('admin/memberships.php')) ?>">Reset</a>
  </form>
  <div class="table-wrap">
    <?php if (!$rows): ?><?= empty_state('No subscriptions found', 'Create a subscription or relax the filters.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Plan</th><th>Period</th><th>Balance</th><th>Status</th><th>Renew / change</th></tr></thead><tbody>
      <?php foreach ($rows as $s): $bal = balance_of($s['total_due'], $s['paid_total']); ?>
        <tr><td><a href="<?= e(url('admin/member.php?id=' . $s['mid'])) ?>"><?= e($s['first_name'] . ' ' . $s['last_name']) ?></a><br><span class="small text-muted-eden"><?= e($s['member_number']) ?></span></td>
        <td class="small"><?= e($s['plan']) ?></td>
        <td class="small"><?= e(fmt_date($s['start_date'])) ?> → <?= e(fmt_date($s['end_date'])) ?></td>
        <td class="small"><?= e(money($bal)) ?><br><span class="text-muted-eden">of <?= e(money($s['total_due'])) ?></span></td>
        <td><?= status_badge($s['status']) ?></td>
        <td>
          <form action="<?= e(url('actions/handle.php?do=subscription.renew')) ?>" method="post" class="d-flex gap-1"><?= csrf_field() ?>
            <input type="hidden" name="membership_id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="member_id" value="<?= (int) $s['mid'] ?>">
            <input type="hidden" name="start_date" value="<?= e(today()) ?>">
            <select class="input" name="plan_id" style="min-width:140px"><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $s['plan_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
            <button class="btn btn-eden btn-sm">Renew</button>
          </form>
          <?php if ($s['status'] === 'active'): ?>
            <form action="<?= e(url('actions/handle.php?do=subscription.status')) ?>" method="post" class="mt-1" data-confirm="Suspend this subscription?"><?= csrf_field() ?>
              <input type="hidden" name="membership_id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="status" value="suspended"><button class="btn btn-eden-ghost btn-sm">Suspend</button></form>
          <?php elseif (in_array($s['status'], ['suspended', 'cancelled'], true)): ?>
            <form action="<?= e(url('actions/handle.php?do=subscription.status')) ?>" method="post" class="mt-1"><?= csrf_field() ?>
              <input type="hidden" name="membership_id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="status" value="active"><button class="btn btn-eden-ghost btn-sm">Reactivate</button></form>
          <?php endif; ?>
        </td></tr>
      <?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
  <p class="hint mt-3">Expiry rules: the start date is the first valid day and the end date is the last valid day (inclusive). Monthly plans clamp to month end (31 Jan → 28 Feb). A subscription is expired only when its end date has passed; payment of the balance is tracked separately.</p>
</div></div>
<?php dash_end(); ?>
