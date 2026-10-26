<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$action = get('action'); $from = get('from'); $to = get('to'); $page = page_param(); $per = 25;
$where = []; $args = [];
if ($action !== '') { $where[] = 'action LIKE ?'; $args[] = "%$action%"; }
if ($from !== '') { $where[] = 'created_at >= ?'; $args[] = $from . ' 00:00:00'; }
if ($to !== '') { $where[] = 'created_at <= ?'; $args[] = $to . ' 23:59:59'; }
$sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int) db_val("SELECT COUNT(*) FROM audit_logs" . $sql, $args);
$rows = db_all("SELECT * FROM audit_logs" . $sql . " ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
dash_start($u, 'Audit logs', 'Append-only trail of administrative and financial actions. No passwords, tokens or payment credentials are stored here.', 'admin/audit-logs.php');
?>
<div class="panel"><div class="panel-head"><h2><?= $total ?> entries</h2></div><div class="panel-body">
  <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
    <div><label class="small text-muted-eden">Action contains</label><input class="input" name="action" value="<?= e($action) ?>"></div>
    <div><label class="small text-muted-eden">From</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
    <div><label class="small text-muted-eden">To</label><input class="input" type="date" name="to" value="<?= e($to) ?>"></div>
    <button class="btn btn-eden-ghost btn-sm">Filter</button></form>
  <div class="table-wrap"><?php if (!$rows): ?><?= empty_state('No audit entries', 'Actions such as logins, payments and status changes are recorded here.') ?>
    <?php else: ?><table class="data"><thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Record</th><th>Metadata</th><th>IP</th></tr></thead><tbody>
      <?php foreach ($rows as $r): ?><tr><td class="small"><?= e(fmt_datetime($r['created_at'])) ?></td><td class="small"><?= e($r['actor_label'] ?? ($r['actor_id'] ? '#' . $r['actor_id'] : 'system')) ?></td>
        <td class="small text-eden"><?= e($r['action']) ?></td><td class="small"><?= e($r['entity_type'] ?? '—') ?><?= $r['entity_id'] ? ' #' . (int) $r['entity_id'] : '' ?></td>
        <td class="small"><?= e(mb_substr((string) $r['metadata'], 0, 80)) ?></td><td class="small"><?= e($r['ip_address'] ?? '—') ?></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></div>
  <?php if ($total > $per): ?><p class="small text-muted-eden mt-2">Page <?= $page ?> of <?= (int) ceil($total / $per) ?></p><?php endif; ?>
</div></div>
<?php dash_end(); ?>
