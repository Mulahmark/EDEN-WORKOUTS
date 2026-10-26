<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin', 'receptionist']);
$method = get('method'); $from = get('from'); $to = get('to'); $search = get('search'); $page = page_param(); $per = 15;
$where = []; $args = [];
if ($method !== '') { $where[] = 'p.method = ?'; $args[] = $method; }
if ($from !== '') { $where[] = 'p.paid_at >= ?'; $args[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'p.paid_at <= ?'; $args[] = $to . ' 23:59:59'; }
if ($search !== '') { $where[] = '(p.reference LIKE ? OR p.tx_reference LIKE ? OR m.first_name LIKE ? OR m.last_name LIKE ?)'; array_push($args, "%$search%", "%$search%", "%$search%", "%$search%"); }
$sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int) db_val("SELECT COUNT(*) FROM payments p JOIN members m ON m.id = p.member_id" . $sql, $args);
$rows = db_all("SELECT p.*, m.first_name, m.last_name, m.member_number, m.id mid FROM payments p JOIN members m ON m.id = p.member_id" . $sql . " ORDER BY p.paid_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
$members = db_all("SELECT id, first_name, last_name, member_number FROM members WHERE status='active' ORDER BY first_name");
$subs = db_all("SELECT ms.id, ms.member_id, ms.end_date, (ms.total_due - ms.paid_total) bal, p.name plan FROM memberships ms JOIN membership_plans p ON p.id = ms.plan_id WHERE ms.status IN ('active','pending') ORDER BY ms.end_date");
$collected = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='completed'");
$reversed = (float) db_val("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='reversed'");
$base = $u['role'] === 'admin' ? 'admin/payments.php' : 'reception/payments.php';
dash_start($u, 'Payments', 'Manual recording for cash, M-Pesa, bank and card. Online M-Pesa (Daraja) is not connected — see the documentation.', $base);
?>
<div class="stat-grid">
  <?= stat_card('Collected (all time)', money($collected), '', 'ok') ?>
  <?= stat_card('Reversed / refunded', money($reversed), '', 'bad') ?>
  <?= stat_card('Payments listed', $total) ?>
</div>
<?php if ($members): ?>
<div class="panel"><div class="panel-head"><h2>Record a payment</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=payment.record')) ?>" method="post">
    <?= csrf_field() ?><input type="hidden" name="back" value="<?= e($base) ?>">
    <div class="row g-3">
      <div class="col-md-3"><div class="field"><label>Member</label><select class="input" name="member_id" required><?php foreach ($members as $m): ?><option value="<?= (int) $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name'] . ' (' . $m['member_number'] . ')') ?></option><?php endforeach; ?></select></div></div>
      <div class="col-md-4"><div class="field"><label>Apply to subscription</label><select class="input" name="membership_id"><option value="">Unallocated / general payment</option>
        <?php foreach ($subs as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['plan']) ?> · <?= e($s['member_number'] ?? '') ?> · ends <?= e(fmt_date($s['end_date'])) ?> · <?= e(money($s['bal'])) ?> due</option><?php endforeach; ?></select></div></div>
      <div class="col-md-2"><div class="field"><label>Amount *</label><input class="input" type="number" step="0.01" min="0.01" name="amount" required></div></div>
      <div class="col-md-3"><div class="field"><label>Method</label><select class="input" name="method"><option value="cash">Cash</option><option value="mpesa">M-Pesa (manual)</option><option value="bank">Bank transfer</option><option value="card">Card (manual)</option><option value="other">Other</option></select></div></div>
      <div class="col-md-3"><div class="field"><label>Payment date &amp; time</label><input class="input" type="datetime-local" name="paid_at" value="<?= e(now_local()) ?>"></div></div>
      <div class="col-md-3"><div class="field"><label>Transaction reference</label><input class="input" name="tx_reference"><p class="hint">Required for M-Pesa. Duplicates rejected.</p></div></div>
      <div class="col-md-6"><div class="field"><label>Notes</label><input class="input" name="notes"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Record payment</button>
  </form>
  <p class="hint mt-2">M-Pesa entries are recorded manually by staff; the system never claims an online payment was verified by a provider.</p>
</div></div>
<?php endif; ?>
<?php if ($u['role'] === 'admin' && (int) get('reverse') > 0): ?>
<div class="panel" style="border-color:rgba(239,68,68,.5)"><div class="panel-head"><h2>Reverse payment #<?= (int) get('reverse') ?></h2><a class="btn btn-eden-quiet btn-sm" href="<?= e(url('admin/payments.php')) ?>">Cancel</a></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=payment.reverse')) ?>" method="post" data-confirm="Reverse this payment? The record is kept and the balance recalculated.">
    <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= (int) get('reverse') ?>">
    <div class="row g-3"><div class="col-md-3"><div class="field"><label>Type</label><select class="input" name="type"><option value="reversal">Reversal</option><option value="refund">Refund</option><option value="adjustment">Adjustment</option></select></div></div>
    <div class="col-md-9"><div class="field"><label>Reason (required)</label><input class="input" name="reason" required minlength="5"></div></div></div>
    <button class="btn btn-eden-danger" type="submit">Reverse payment</button>
  </form>
</div></div>
<?php endif; ?>
<div class="panel"><div class="panel-head"><h2>Payment ledger</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=payments&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">Export CSV</a></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div><label class="small text-muted-eden">Search</label><input class="input" name="search" value="<?= e($search) ?>"></div>
    <div><label class="small text-muted-eden">Method</label><select class="input" name="method"><option value="">All</option><?php foreach (['cash', 'mpesa', 'bank', 'card', 'other'] as $m): ?><option value="<?= $m ?>" <?= $method === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option><?php endforeach; ?></select></div>
    <div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
    <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-eden-ghost btn-sm">Apply</button>
  </form>
  <div class="table-wrap">
    <?php if (!$rows): ?><?= empty_state('No payments found', 'Record a payment above or adjust the filters.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Member</th><th>Method</th><th>Date</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $p): ?><tr>
        <td class="small"><?= e($p['reference']) ?><br><span class="text-muted-eden"><?= e($p['tx_reference'] ?? '') ?></span></td>
        <td><a href="<?= e(url('admin/member.php?id=' . $p['mid'])) ?>"><?= e($p['first_name'] . ' ' . $p['last_name']) ?></a><br><span class="small text-muted-eden"><?= e($p['member_number']) ?></span></td>
        <td class="small"><?= e(strtoupper($p['method'])) ?></td><td class="small"><?= e(fmt_datetime($p['paid_at'])) ?></td>
        <td><?= e(money($p['amount'])) ?></td><td><?= status_badge($p['status']) ?></td>
        <td class="d-flex gap-1"><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('receipts/view.php?id=' . $p['id'])) ?>">Receipt</a>
          <?php if ($u['role'] === 'admin' && $p['status'] === 'completed'): ?><a class="btn btn-eden-danger btn-sm" href="?reverse=<?= (int) $p['id'] ?>">Reverse</a><?php endif; ?></td>
      </tr><?php endforeach; ?></tbody></table><?php endif; ?>
  </div>
</div></div>
<?php dash_end(); ?>
