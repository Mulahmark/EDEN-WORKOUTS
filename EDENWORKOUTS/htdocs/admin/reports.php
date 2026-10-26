<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$type = get('type') ?: 'collections'; $from = get('from') ?: date('Y-m-01'); $to = get('to') ?: today();
$types = ['collections' => 'Revenue collections', 'outstanding' => 'Outstanding balances', 'expiring' => 'Expiring soon', 'expired' => 'Expired memberships', 'attendance' => 'Attendance', 'trainers' => 'Trainer assignments'];
dash_start($u, 'Reports', 'Every figure is calculated live from the database for the selected date range.', 'admin/reports.php');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach ($types as $k => $lbl): ?><a class="btn <?= $type === $k ? 'btn-eden' : 'btn-eden-quiet' ?> btn-sm" href="?type=<?= $k ?>&from=<?= e($from) ?>&to=<?= e($to) ?>"><?= e($lbl) ?></a><?php endforeach; ?>
</div>
<form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
  <input type="hidden" name="type" value="<?= e($type) ?>">
  <div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
  <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div>
  <button class="btn btn-eden-ghost btn-sm">Run report</button>
</form>
<?php if ($type === 'collections'):
  $byMethod = db_all("SELECT method, COUNT(*) n, SUM(amount) t FROM payments WHERE status='completed' AND DATE(paid_at) BETWEEN ? AND ? GROUP BY method ORDER BY t DESC", [$from, $to]);
  $byPlan = db_all("SELECT p.name plan, COUNT(pay.id) n, SUM(pay.amount) t FROM payments pay JOIN memberships ms ON ms.id=pay.membership_id JOIN membership_plans p ON p.id=ms.plan_id WHERE pay.status='completed' AND DATE(pay.paid_at) BETWEEN ? AND ? GROUP BY p.name ORDER BY t DESC", [$from, $to]);
  $rev = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE(paid_at) BETWEEN ? AND ?", [$from, $to]);
  $exp = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?", [$from, $to]);
?>
<div class="stat-grid"><?= stat_card('Collected', money($rev), '', 'ok') ?><?= stat_card('Expenses', money($exp), '', 'bad') ?><?= stat_card('Net cash-flow', money($rev - $exp), 'Collected minus recorded expenses', $rev - $exp >= 0 ? 'ok' : 'bad') ?></div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Collections by method</h2></div><div class="panel-body table-wrap"><?php if (!$byMethod): ?><?= empty_state('No payments in this range', 'Change the date range or record payments.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Method</th><th>Transactions</th><th>Total</th></tr></thead><tbody><?php foreach ($byMethod as $m): ?><tr><td><?= e(strtoupper($m['method'])) ?></td><td class="small"><?= (int) $m['n'] ?></td><td><?= e(money($m['t'])) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
  <div class="panel"><div class="panel-head"><h2>Revenue by membership plan</h2></div><div class="panel-body table-wrap"><?php if (!$byPlan): ?><?= empty_state('No plan revenue', 'Payments must be linked to a subscription to appear here.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Plan</th><th>Payments</th><th>Total</th></tr></thead><tbody><?php foreach ($byPlan as $m): ?><tr><td><?= e($m['plan']) ?></td><td class="small"><?= (int) $m['n'] ?></td><td><?= e(money($m['t'])) ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
</div>
<p class="hint">Unpaid membership fees are outstanding balances, not collected cash — they are reported separately under “Outstanding balances”.</p>
<?php elseif ($type === 'outstanding'):
  $rows = db_all("SELECT ms.id, (ms.total_due - ms.paid_total) bal, ms.end_date, p.name plan, CONCAT(m.first_name,' ',m.last_name) name, m.member_number, m.id mid FROM memberships ms JOIN members m ON m.id=ms.member_id JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.status IN ('active','pending') AND ms.total_due > ms.paid_total ORDER BY bal DESC LIMIT 100");
  $total = 0.0; foreach ($rows as $r) $total += (float) $r['bal'];
?>
<div class="stat-grid"><?= stat_card('Members owing', count($rows), '', 'warn') ?><?= stat_card('Total outstanding', money($total), '', 'bad') ?></div>
<div class="panel"><div class="panel-head"><h2>Outstanding balances</h2></div><div class="panel-body table-wrap"><?php if (!$rows): ?><?= empty_state('Nothing outstanding', 'All active subscriptions are fully paid.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Plan</th><th>Expiry</th><th>Balance</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><a href="<?= e(url('admin/member.php?id=' . $r['mid'])) ?>"><?= e($r['name']) ?></a><br><span class="small text-muted-eden"><?= e($r['member_number']) ?></span></td><td class="small"><?= e($r['plan']) ?></td><td class="small"><?= e(fmt_date($r['end_date'])) ?></td><td><?= e(money($r['bal'])) ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div></div>
<?php elseif ($type === 'expiring' || $type === 'expired'):
  $rows = $type === 'expiring'
    ? db_all("SELECT ms.*, p.name plan, CONCAT(m.first_name,' ',m.last_name) name, m.member_number, m.id mid FROM memberships ms JOIN members m ON m.id=ms.member_id JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.status='active' AND ms.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY ms.end_date LIMIT 200")
    : db_all("SELECT ms.*, p.name plan, CONCAT(m.first_name,' ',m.last_name) name, m.member_number, m.id mid FROM memberships ms JOIN members m ON m.id=ms.member_id JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.status='expired' ORDER BY ms.end_date DESC LIMIT 200");
?>
<div class="panel"><div class="panel-head"><h2><?= $type === 'expiring' ? 'Expiring within 30 days' : 'Expired memberships' ?> (<?= count($rows) ?>)</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('Nothing to show', 'No memberships match this report.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Plan</th><th>Period</th><th>Balance</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><a href="<?= e(url('admin/member.php?id=' . $r['mid'])) ?>"><?= e($r['name']) ?></a><br><span class="small text-muted-eden"><?= e($r['member_number']) ?></span></td>
      <td class="small"><?= e($r['plan']) ?></td><td class="small"><?= e(fmt_date($r['start_date'])) ?> → <?= e(fmt_date($r['end_date'])) ?></td>
      <td><?= e(money(balance_of($r['total_due'], $r['paid_total']))) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div></div>
<?php elseif ($type === 'attendance'):
  $rows = db_all("SELECT attend_date d, COUNT(*) n, SUM(is_override) o FROM attendance WHERE attend_date BETWEEN ? AND ? GROUP BY attend_date ORDER BY attend_date DESC", [$from, $to]);
  $total = 0; foreach ($rows as $r) $total += (int) $r['n'];
?>
<div class="stat-grid"><?= stat_card('Total visits in range', $total, '', 'orange') ?></div>
<div class="panel"><div class="panel-head"><h2>Daily attendance</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=attendance&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">Export CSV</a></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No attendance recorded', 'Check-ins appear here grouped by day.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Date</th><th>Visits</th><th>Overrides</th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td><?= e(fmt_date($r['d'])) ?></td><td class="small"><?= (int) $r['n'] ?></td><td class="small"><?= (int) $r['o'] ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div></div>
<?php else:
  $rows = db_all("SELECT t.full_name, t.employment_status, COUNT(DISTINCT a.member_id) clients, COUNT(DISTINCT w.id) plans FROM trainers t LEFT JOIN trainer_assignments a ON a.trainer_id=t.id AND a.status='active' LEFT JOIN workout_plans w ON w.trainer_id=t.id AND w.status='active' GROUP BY t.id, t.full_name, t.employment_status ORDER BY t.full_name");
?>
<div class="panel"><div class="panel-head"><h2>Trainer assignments</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=trainers')) ?>">Export CSV</a></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No trainers', 'Register trainers to see assignment reports.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Trainer</th><th>Employment</th><th>Active clients</th><th>Active plans</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['full_name']) ?></td><td class="small"><?= e($r['employment_status']) ?></td><td class="small"><?= (int) $r['clients'] ?></td><td class="small"><?= (int) $r['plans'] ?></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div></div>
<?php endif; ?>
<?php dash_end(); ?>
