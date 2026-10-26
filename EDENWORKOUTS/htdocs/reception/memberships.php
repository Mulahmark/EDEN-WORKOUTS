<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['receptionist', 'admin']);
expiry_sweep();
$plans = db_all("SELECT * FROM membership_plans WHERE is_active=1 ORDER BY name");
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$rows = db_all("SELECT ms.*, p.name plan, CONCAT(m.first_name,' ',m.last_name) name, m.member_number, m.id mid FROM memberships ms JOIN members m ON m.id=ms.member_id JOIN membership_plans p ON p.id=ms.plan_id ORDER BY ms.end_date DESC LIMIT 60");
dash_start($u, 'Subscriptions', 'Sell new memberships and record renewals. Expiry dates are calculated from the plan duration.', 'reception/memberships.php');
?>
<div class="panel"><div class="panel-head"><h2>New subscription</h2></div><div class="panel-body">
  <?php if (!$plans || !$members): ?><p class="small text-muted-eden">An administrator must publish membership plans first.</p>
  <?php else: ?><form action="<?= e(url('actions/handle.php?do=subscription.create')) ?>" method="post"><?= csrf_field() ?>
    <input type="hidden" name="back" value="reception/memberships.php">
    <div class="row g-2">
      <div class="col-md-4"><div class="field"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_number'] . ')') ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Plan</label><select class="input" name="plan_id" required><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?> — <?= e(money($p['price'])) ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Start date</label><input class="input" type="date" name="start_date" value="<?= e(today()) ?>" required></div></div>
      <div class="col-md-2"><div class="field"><label>Discount</label><input class="input" type="number" step="0.01" name="discount" value="0"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Create subscription</button></form><?php endif; ?>
</div></div>
<div class="panel"><div class="panel-head"><h2>Recent subscriptions</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No subscriptions yet', 'Create the first subscription above.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Plan</th><th>Period</th><th>Balance</th><th>Status</th><th>Renew</th></tr></thead><tbody>
    <?php foreach ($rows as $s): ?><tr><td class="small"><?= e($s['name']) ?><br><span class="text-muted-eden"><?= e($s['member_number']) ?></span></td><td class="small"><?= e($s['plan']) ?></td>
      <td class="small"><?= e(fmt_date($s['start_date'])) ?> → <?= e(fmt_date($s['end_date'])) ?></td><td><?= e(money(balance_of($s['total_due'], $s['paid_total']))) ?></td><td><?= status_badge($s['status']) ?></td>
      <td><form action="<?= e(url('actions/handle.php?do=subscription.renew')) ?>" method="post" class="d-flex gap-1"><?= csrf_field() ?>
        <input type="hidden" name="membership_id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="member_id" value="<?= (int) $s['mid'] ?>"><input type="hidden" name="start_date" value="<?= e(today()) ?>">
        <select class="input" name="plan_id" style="min-width:130px"><?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $s['plan_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select>
        <button class="btn btn-eden btn-sm">Renew</button></form></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
  <p class="hint mt-2">Suspending or cancelling a subscription, changing plan prices and reversing payments are administrator-only actions.</p>
</div></div>
<?php dash_end(); ?>
