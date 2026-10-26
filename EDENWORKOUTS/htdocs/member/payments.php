<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$rows = db_all("SELECT * FROM payments WHERE member_id=? ORDER BY paid_at DESC", [$mid]);
$total = 0.0; foreach ($rows as $r) if ($r['status'] === 'completed') $total += (float) $r['amount'];
dash_start($u, 'My payments', 'Every payment recorded against your account, with printable receipts.', 'member/payments.php');
?>
<div class="stat-grid"><?= stat_card('Total paid', money($total), '', 'ok') ?><?= stat_card('Payments', count($rows)) ?></div>
<div class="panel"><div class="panel-head"><h2>Payment history</h2></div><div class="panel-body table-wrap">
  <?php if (!$rows): ?><?= empty_state('No payments yet', 'Once reception records a payment it appears here with a receipt.') ?>
  <?php else: ?><table class="data"><thead><tr><th>Reference</th><th>Date</th><th>Method</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $p): ?><tr><td class="small"><?= e($p['reference']) ?><?= $p['tx_reference'] ? '<br><span class="text-muted-eden">' . e($p['tx_reference']) . '</span>' : '' ?></td>
      <td class="small"><?= e(fmt_datetime($p['paid_at'])) ?></td><td class="small"><?= e(strtoupper($p['method'])) ?></td><td><?= e(money($p['amount'])) ?></td><td><?= status_badge($p['status']) ?></td>
      <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('receipts/view.php?id=' . $p['id'])) ?>">Receipt</a></td></tr><?php endforeach; ?>
  </tbody></table><?php endif; ?>
  <p class="hint mt-2">M-Pesa entries are recorded manually by staff and show the Safaricom code. A reversed payment no longer counts towards your balance.</p>
</div></div>
<?php dash_end(); ?>
