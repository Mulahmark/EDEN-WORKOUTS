<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$cat = (int) get('category'); $from = get('from'); $to = get('to');
$where = []; $args = [];
if ($cat) { $where[] = 'e.category_id = ?'; $args[] = $cat; }
if ($from !== '') { $where[] = 'e.expense_date >= ?'; $args[] = $from; }
if ($to !== '') { $where[] = 'e.expense_date <= ?'; $args[] = $to; }
$sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$rows = db_all("SELECT e.*, c.name category, u.full_name by_name FROM expenses e JOIN expense_categories c ON c.id=e.category_id LEFT JOIN users u ON u.id=e.recorded_by" . $sql . " ORDER BY e.expense_date DESC LIMIT 200", $args);
$total = 0.0; foreach ($rows as $r) $total += (float) $r['amount'];
$cats = db_all("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY name");
$byCat = db_all("SELECT c.name, SUM(e.amount) t FROM expenses e JOIN expense_categories c ON c.id=e.category_id GROUP BY c.name ORDER BY t DESC LIMIT 8");
dash_start($u, 'Expenses', 'Gym operating costs, kept separate from membership income so cash-flow reporting stays honest.', 'admin/expenses.php');
?>
<div class="stat-grid"><?= stat_card('Filtered total', money($total), '', 'bad') ?><?= stat_card('Records', count($rows)) ?><?= stat_card('Categories', count($cats)) ?></div>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>Record an expense</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=expense.save')) ?>" method="post"><?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-6"><div class="field"><label>Category</label><select class="input" name="category_id" required><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div></div>
        <div class="col-6"><div class="field"><label>Amount</label><input class="input" type="number" step="0.01" min="0.01" name="amount" required></div></div>
        <div class="col-12"><div class="field"><label>Description</label><input class="input" name="description" required></div></div>
        <div class="col-6"><div class="field"><label>Date</label><input class="input" type="date" name="expense_date" value="<?= e(today()) ?>" required></div></div>
        <div class="col-6"><div class="field"><label>Method</label><select class="input" name="method"><option value="cash">Cash</option><option value="mpesa">M-Pesa</option><option value="bank">Bank</option><option value="card">Card</option><option value="other">Other</option></select></div></div>
        <div class="col-12"><div class="field"><label>Notes</label><input class="input" name="notes"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Record expense</button>
    </form>
    <hr style="border-color:var(--line)">
    <form action="<?= e(url('actions/handle.php?do=category.save')) ?>" method="post" class="d-flex gap-2 align-items-end"><?= csrf_field() ?>
      <div class="field mb-0 flex-grow-1"><label>New category</label><input class="input" name="name" required></div><button class="btn btn-eden-ghost btn-sm">Add</button></form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Top categories</h2></div><div class="panel-body">
    <?php if (!$byCat): ?><?= empty_state('No expenses yet', 'Record expenses to see category summaries.') ?>
    <?php else: ?><ul class="plan-benefits"><?php foreach ($byCat as $c): ?><li><i class="bi bi-cash"></i><span><?= e($c['name']) ?> — <strong><?= e(money($c['t'])) ?></strong></span></li><?php endforeach; ?></ul><?php endif; ?>
  </div></div>
</div>
<div class="panel"><div class="panel-head"><h2>Expense ledger</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=expenses&from=' . urlencode($from) . '&to=' . urlencode($to))) ?>">Export CSV</a></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div><label class="small text-muted-eden">Category</label><select class="input" name="category"><option value="">All</option><?php foreach ($cats as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
    <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-eden-ghost btn-sm">Apply</button></form>
  <div class="table-wrap"><?php if (!$rows): ?><?= empty_state('No expenses recorded', 'Record rent, utilities, equipment and other costs here.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Category</th><th>Description</th><th>Date</th><th>Amount</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td class="small"><?= e($r['reference']) ?></td><td class="small"><?= e($r['category']) ?></td><td class="small"><?= e($r['description']) ?></td>
        <td class="small"><?= e(fmt_date($r['expense_date'])) ?></td><td><?= e(money($r['amount'])) ?></td>
        <td><form action="<?= e(url('actions/handle.php?do=expense.delete')) ?>" method="post" data-confirm="Delete this expense? The audit log keeps the entry."><?= csrf_field() ?><input type="hidden" name="expense_id" value="<?= (int) $r['id'] ?>"><button class="btn btn-eden-danger btn-sm">Delete</button></form></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></div>
</div></div>
<?php dash_end(); ?>
