<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
expiry_sweep();
$s = admin_stats();
$months = last_months(6);
$revByMonth = [];
foreach (db_all("SELECT DATE_FORMAT(paid_at,'%Y-%m') ym, SUM(amount) t FROM payments WHERE status='completed' GROUP BY ym") as $r) $revByMonth[$r['ym']] = (float) $r['t'];
$regByMonth = [];
foreach (db_all("SELECT DATE_FORMAT(joined_at,'%Y-%m') ym, COUNT(*) t FROM members GROUP BY ym") as $r) $regByMonth[$r['ym']] = (int) $r['t'];
$attendance = db_all("SELECT attend_date d, COUNT(*) t FROM attendance WHERE attend_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY d ORDER BY d");
$planMix = db_all("SELECT p.name, COUNT(m.id) t FROM membership_plans p LEFT JOIN memberships m ON m.plan_id = p.id AND m.status IN ('active','pending') GROUP BY p.id, p.name ORDER BY t DESC");
$recentPayments = db_all("SELECT p.reference, p.amount, p.status, p.paid_at, CONCAT(m.first_name,' ',m.last_name) name FROM payments p JOIN members m ON m.id = p.member_id ORDER BY p.paid_at DESC LIMIT 5");
$recentMembers = db_all("SELECT id, first_name, last_name, member_number, status, joined_at FROM members ORDER BY joined_at DESC LIMIT 5");
$expiring = db_all("SELECT ms.id, ms.end_date, CONCAT(m.first_name,' ',m.last_name) name, m.member_number, (ms.total_due - ms.paid_total) bal FROM memberships ms JOIN members m ON m.id = ms.member_id WHERE ms.status='active' AND ms.end_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) ORDER BY ms.end_date LIMIT 6");
$pendingUsers = db_all("SELECT id, full_name, email FROM users WHERE status='pending' ORDER BY created_at DESC LIMIT 5");
$audit = db_all("SELECT action, actor_label, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 6");
dash_start($u, 'Welcome back, ' . explode(' ', $u['full_name'])[0], fmt_date(today()) . ' · ' . setting('timezone', 'Africa/Nairobi'), 'admin/dashboard.php');
?>
<div class="stat-grid">
  <?= stat_card('Registered members', $s['total_members'], $s['active_members'] . ' active · ' . $s['pending_members'] . ' pending', 'orange') ?>
  <?= stat_card("Today's check-ins", $s['today_checkins']) ?>
  <?= stat_card('Revenue this month', money($s['month_revenue']), 'Completed payments only', 'ok') ?>
  <?= stat_card('Outstanding balances', money($s['outstanding']), 'Active + pending subscriptions', 'warn') ?>
  <?= stat_card('Expiring in 7 days', $s['expiring_week'], $s['expiring_month'] . ' within 30 days', 'warn') ?>
  <?= stat_card('Expired subscriptions', $s['expired_subs'], 'Awaiting renewal', 'bad') ?>
  <?= stat_card('Active trainers', $s['active_trainers']) ?>
  <?= stat_card('Expenses this month', money($s['month_expenses']), 'Recorded gym costs', 'bad') ?>
</div>
<?php if ($s['total_members'] === 0): ?>
  <div class="notice"><strong>No gym data yet.</strong> Charts and statistics fill in as you add membership plans, members, subscriptions, payments and check-ins. Start with <a href="<?= e(url('admin/plans.php')) ?>">Membership Plans</a>.</div>
<?php endif; ?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Monthly revenue</h2></div><div class="panel-body"><div class="chart-box"><canvas id="chartRevenue"></canvas></div></div></div>
  <div class="panel"><div class="panel-head"><h2>New registrations per month</h2></div><div class="panel-body"><div class="chart-box"><canvas id="chartRegs"></canvas></div></div></div>
  <div class="panel"><div class="panel-head"><h2>Attendance (14 days)</h2></div><div class="panel-body"><div class="chart-box"><canvas id="chartAtt"></canvas></div></div></div>
  <div class="panel"><div class="panel-head"><h2>Active subscriptions by plan</h2></div><div class="panel-body"><div class="chart-box"><canvas id="chartPlans"></canvas></div></div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Subscriptions expiring soon</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/memberships.php')) ?>">All subscriptions</a></div>
  <div class="panel-body table-wrap">
    <?php if (!$expiring): ?><?= empty_state('Nothing expiring in the next 14 days', 'Memberships approaching their end date appear here.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Ends</th><th>Balance</th></tr></thead><tbody>
      <?php foreach ($expiring as $e): ?><tr><td><?= e($e['name']) ?><br><span class="small text-muted-eden"><?= e($e['member_number']) ?></span></td><td><?= e(fmt_date($e['end_date'])) ?></td><td><?= e(money($e['bal'])) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?>
  </div>
</div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Recent payments</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/payments.php')) ?>">All</a></div><div class="panel-body table-wrap">
    <?php if (!$recentPayments): ?><?= empty_state('No payments recorded', 'Payments recorded by reception appear here with receipts.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Member</th><th>Amount</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($recentPayments as $p): ?><tr><td class="small"><?= e($p['reference']) ?></td><td><?= e($p['name']) ?></td><td><?= e(money($p['amount'])) ?></td><td><?= status_badge($p['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></div></div>
  <div class="panel"><div class="panel-head"><h2>Recent registrations</h2></div><div class="panel-body table-wrap">
    <?php if (!$recentMembers): ?><?= empty_state('No members yet', 'Add your first member to start tracking subscriptions.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Joined</th><th>Status</th></tr></thead><tbody>
      <?php foreach ($recentMembers as $m): ?><tr><td><a href="<?= e(url('admin/member.php?id=' . $m['id'])) ?>"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></a><br><span class="small text-muted-eden"><?= e($m['member_number']) ?></span></td><td class="small"><?= e(fmt_date($m['joined_at'])) ?></td><td><?= status_badge($m['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></div></div>
  <div class="panel"><div class="panel-head"><h2>Pending account activations</h2></div><div class="panel-body">
    <?php if (!$pendingUsers): ?><?= empty_state('No pending accounts', 'Public registrations stay pending until you activate them.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($pendingUsers as $p): ?><li><i class="bi bi-person"></i><span><?= e($p['full_name']) ?> — <?= e($p['email']) ?></span></li><?php endforeach; ?></ul>
      <a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/members.php?status=pending')) ?>">Review pending members</a><?php endif; ?>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Latest system activity</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/audit-logs.php')) ?>">Audit log</a></div><div class="panel-body">
    <?php if (!$audit): ?><?= empty_state('No activity yet', 'Administrative and financial actions are logged here.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($audit as $a): ?><li><i class="bi bi-dot"></i><span><strong><?= e($a['action']) ?></strong> — <?= e($a['actor_label'] ?? 'system') ?><br><span class="small text-muted-eden"><?= e(fmt_datetime($a['created_at'])) ?></span></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<script>
window.EDEN_CHARTS = [
 {id:'chartRevenue',config:{type:'line',data:{labels:<?= json_encode($months) ?>,datasets:[{label:'Revenue (KES)',data:<?= json_encode(array_map(fn($m) => $revByMonth[$m] ?? 0, $months)) ?>,borderColor:'#FF6B35',backgroundColor:'rgba(255,107,53,.2)',fill:true,tension:.35}]},options:{responsive:true,maintainAspectRatio:false}}},
 {id:'chartRegs',config:{type:'bar',data:{labels:<?= json_encode($months) ?>,datasets:[{label:'New members',data:<?= json_encode(array_map(fn($m) => $regByMonth[$m] ?? 0, $months)) ?>,backgroundColor:'#60A5FA',borderRadius:6}]},options:{responsive:true,maintainAspectRatio:false}}},
 {id:'chartAtt',config:{type:'line',data:{labels:<?= json_encode(array_column($attendance, 'd')) ?>,datasets:[{label:'Check-ins',data:<?= json_encode(array_map('intval', array_column($attendance, 't'))) ?>,borderColor:'#22C55E',backgroundColor:'rgba(34,197,94,.2)',fill:true,tension:.35}]},options:{responsive:true,maintainAspectRatio:false}}},
 {id:'chartPlans',config:{type:'doughnut',data:{labels:<?= json_encode(array_column($planMix, 'name')) ?>,datasets:[{data:<?= json_encode(array_map('intval', array_column($planMix, 't'))) ?>,backgroundColor:['#FF6B35','#FF914D','#60A5FA','#22C55E','#FBBF24']}]},options:{responsive:true,maintainAspectRatio:false,cutout:'62%'}}}
];
</script>
<?php dash_end(); ?>
