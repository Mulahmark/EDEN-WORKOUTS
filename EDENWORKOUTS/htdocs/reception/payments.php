<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['receptionist', 'admin']);
$from = get('from'); $to = get('to');
$where = []; $args = [];
if ($from !== '') { $where[] = 'p.paid_at >= ?'; $args[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'p.paid_at <= ?'; $args[] = $to . ' 23:59:59'; }
$sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$rows = db_all("SELECT p.*, m.first_name, m.last_name, m.member_number, m.id mid FROM payments p JOIN members m ON m.id=p.member_id" . $sql . " ORDER BY p.paid_at DESC LIMIT 100", $args);
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$subs = db_all("SELECT ms.id, ms.end_date, (ms.total_due-ms.paid_total) bal, p.name plan FROM memberships ms JOIN membership_plans p ON p.id=ms.plan_id WHERE ms.status IN ('active','pending') ORDER BY ms.end_date");
$todayTotal = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed' AND DATE(paid_at)=?", [today()]);
dash_start($u, 'Payments', 'Record payments received at the desk and print receipts.', 'reception/payments.php');
?>
<div class="stat-grid"><?= stat_card('Collected today', money($todayTotal), '', 'ok') ?><?= stat_card('Payments listed', count($rows)) ?><?= stat_card('Reversals', 'admin only') ?></div>
<?php if ($members): ?>
<div class="panel"><div class="panel-head"><h2>Record a payment</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=payment.record')) ?>" method="post"><?= csrf_field() ?>
    <input type="hidden" name="back" value="reception/payments.php">
    <div class="row g-2">
      <div class="col-md-3"><div class="field"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_number'] . ')') ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Apply to subscription</label><select class="input" name="membership_id"><option value="">Unallocated</option><?php foreach ($subs as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['plan']) ?> · ends <?= e(fmt_date($s['end_date'])) ?> · <?= e(money($s['bal'])) ?> due</option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Amount</label><input class="input" type="number" step="0.01" min="0.01" name="amount" required></div></div>
      <div class="col-md-3"><div class="field"><label>Method</label><select class="input" name="method"><option value="cash">Cash</option><option value="mpesa">M-Pesa (manual)</option><option value="bank">Bank transfer</option><option value="card">Card (manual)</option><option value="other">Other</option></select></div></div>
      <div class="col-md-3"><div class="field"><label>Date &amp; time</label><input class="input" type="datetime-local" name="paid_at" value="<?= e(now_local()) ?>"></div></div>
      <div class="col-md-3"><div class="field"><label>Transaction reference</label><input class="input" name="tx_reference"></div></div>
      <div class="col-md-6"><div class="field"><label>Notes</label><input class="input" name="notes"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Record payment</button></form>
</div></div>
<?php endif; ?>
<div class="panel"><div class="panel-head"><h2>Payment ledger</h2></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3"><div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
    <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div><button class="btn btn-eden-ghost btn-sm">Filter</button></form>
  <div class="table-wrap"><?php if (!$rows): ?><?= empty_state('No payments in range', 'Record a payment to see it listed here.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Member</th><th>Method</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $p): ?><tr><td class="small"><?= e($p['reference']) ?></td><td class="small"><?= e($p['first_name'] . ' ' . $p['last_name']) ?><br><span class="text-muted-eden"><?= e($p['member_number']) ?></span></td>
        <td class="small"><?= e(strtoupper($p['method'])) ?></td><td class="small"><?= e(fmt_datetime($p['paid_at'])) ?></td><td><?= e(money($p['amount'])) ?></td><td><?= status_badge($p['status']) ?></td>
        <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('receipts/view.php?id=' . $p['id'])) ?>">Receipt</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?></div>
</div></div>
<?php dash_end(); ?>
