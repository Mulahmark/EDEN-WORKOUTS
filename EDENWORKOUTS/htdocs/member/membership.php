<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$subs = db_all("SELECT ms.*, p.name plan, p.description FROM memberships ms JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.member_id=? ORDER BY ms.end_date DESC", [$mid]);
$plans = db_all("SELECT * FROM membership_plans WHERE is_active=1 ORDER BY price");
$current = null; foreach ($subs as $s) if (in_array($s['status'], ['active', 'pending'], true)) { $current = $s; break; }
$balance = $current ? balance_of($current['total_due'], $current['paid_total']) : 0.0;
dash_start($u, 'My membership', 'Your subscription history, agreed price and outstanding balance.', 'member/membership.php');
?>
<div class="stat-grid">
  <?= stat_card('Status', $current['status'] ?? 'no subscription', '', $current && $current['status'] === 'active' ? 'ok' : 'warn') ?>
  <?= stat_card('Expires', $current ? fmt_date($current['end_date']) : '—', $current ? 'end date inclusive' : '') ?>
  <?= stat_card('Balance', money($balance), '', $balance > 0 ? 'warn' : 'ok') ?>
</div>
<?php if ($current): ?>
<div class="panel"><div class="panel-head"><h2>Current subscription</h2></div><div class="panel-body">
  <table class="data" style="min-width:0"><tbody>
    <?php foreach ([['Plan', $current['plan']], ['Agreed price', money($current['agreed_price'])], ['Discount', money($current['discount'])], ['Joining fee', money($current['joining_fee'])],
                    ['Total due', money($current['total_due'])], ['Paid to date', money($current['paid_total'])], ['Outstanding', money($balance)],
                    ['Valid', fmt_date($current['start_date']) . ' → ' . fmt_date($current['end_date']) . ' (end date inclusive)']] as [$k, $v]): ?>
      <tr><td class="small text-muted-eden" style="width:40%"><?= e($k) ?></td><td><?= e($v) ?></td></tr>
    <?php endforeach; ?>
  </tbody></table>
  <form action="<?= e(url('actions/handle.php?do=renewal.request')) ?>" method="post" class="mt-3"><?= csrf_field() ?>
    <button class="btn btn-eden" type="submit">Request renewal from reception</button></form>
  <p class="hint">Renewals are completed at reception where payment is taken. Requests appear in the reception team's inbox.</p>
</div></div>
<?php else: ?>
  <div class="panel panel-body"><?= empty_state('No subscription yet', 'Your account is pending activation or no membership has been sold yet.') ?></div>
<?php endif; ?>
<div class="panel"><div class="panel-head"><h2>Available plans</h2></div><div class="panel-body table-wrap">
  <table class="data"><thead><tr><th>Plan</th><th>Duration</th><th>Price</th></tr></thead><tbody>
    <?php foreach ($plans as $p): ?><tr><td><?= e($p['name']) ?><br><span class="small text-muted-eden"><?= e($p['description']) ?></span></td>
      <td class="small"><?= e(duration_label((int) $p['duration_value'], $p['duration_unit'])) ?></td><td><?= e(money($p['price'])) ?></td></tr><?php endforeach; ?>
  </tbody></table>
</div></div>
<div class="panel"><div class="panel-head"><h2>Subscription history</h2></div><div class="panel-body table-wrap">
  <?php if (!$subs): ?><?= empty_state('No history', 'Subscriptions sold to you appear here.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Period</th><th>Total</th><th>Paid</th><th>Balance</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($subs as $s): ?><tr><td class="small"><?= e($s['plan']) ?></td><td class="small"><?= e(fmt_date($s['start_date'])) ?> → <?= e(fmt_date($s['end_date'])) ?></td>
      <td class="small"><?= e(money($s['total_due'])) ?></td><td class="small"><?= e(money($s['paid_total'])) ?></td><td><?= e(money(balance_of($s['total_due'], $s['paid_total']))) ?></td><td><?= status_badge($s['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
</div></div>
<?php dash_end(); ?>
