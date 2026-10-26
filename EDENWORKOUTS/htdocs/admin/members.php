<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$q = get('search'); $status = get('status'); $from = get('from'); $page = page_param(); $per = 12;
$where = []; $args = [];
if ($q !== '') { $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR member_number LIKE ?)'; array_push($args, "%$q%", "%$q%", "%$q%", "%$q%"); }
if (in_array($status, ['active', 'pending', 'inactive', 'suspended'], true)) { $where[] = 'status = ?'; $args[] = $status; }
if ($from !== '') { $where[] = 'joined_at >= ?'; $args[] = $from . ' 00:00:00'; }
$sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$total = (int) db_val("SELECT COUNT(*) FROM members" . $sql, $args);
$rows = db_all("SELECT * FROM members" . $sql . " ORDER BY joined_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);
$pages = max(1, (int) ceil($total / $per));
dash_start($u, 'Members', 'Search, register and manage gym members. Deactivation keeps payment and attendance history intact.', 'admin/members.php');
?>
<div class="panel"><div class="panel-head"><h2>Register new member</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=member.create')) ?>" method="post">
    <?= csrf_field() ?><input type="hidden" name="back" value="admin/members.php">
    <div class="row g-3">
      <div class="col-md-3"><div class="field"><label>First name *</label><input class="input" name="first_name" required></div></div>
      <div class="col-md-3"><div class="field"><label>Last name *</label><input class="input" name="last_name" required></div></div>
      <div class="col-md-3"><div class="field"><label>Email *</label><input class="input" type="email" name="email" required></div></div>
      <div class="col-md-3"><div class="field"><label>Phone *</label><input class="input" name="phone" required placeholder="0712 345 678"></div></div>
      <div class="col-md-3"><div class="field"><label>Date of birth</label><input class="input" type="date" name="date_of_birth"></div></div>
      <div class="col-md-3"><div class="field"><label>Initial password (optional)</label><input class="input" type="password" name="password"><p class="hint">Leave blank for no login. 8+ chars.</p></div></div>
      <div class="col-md-3"><div class="field"><label>Emergency contact</label><input class="input" name="emergency_contact_name"></div></div>
      <div class="col-md-3"><div class="field"><label>Emergency phone</label><input class="input" name="emergency_contact_phone"></div></div>
      <div class="col-12"><div class="field"><label>Staff notes (restricted)</label><textarea class="input" name="notes" rows="2"></textarea></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Create member</button>
  </form>
</div></div>
<div class="panel">
  <div class="panel-head"><h2>Member directory (<?= $total ?>)</h2><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('api/export.php?type=members&search=' . urlencode($q) . '&status=' . urlencode($status))) ?>">Export CSV</a></div>
  <div class="panel-body">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-end mb-3">
      <div><label class="small text-muted-eden">Search</label><input class="input" name="search" value="<?= e($q) ?>" placeholder="Name, email or member number"></div>
      <div><label class="small text-muted-eden">Status</label><select class="input" name="status">
        <option value="">All</option><?php foreach (['active','pending','inactive','suspended'] as $st): ?><option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?>
      </select></div>
      <div><label class="small text-muted-eden">Registered from</label><input class="input" type="date" name="from" value="<?= e($from) ?>"></div>
      <button class="btn btn-eden-ghost btn-sm" type="submit">Apply filters</button>
      <a class="btn btn-eden-quiet btn-sm" href="<?= e(url('admin/members.php')) ?>">Reset</a>
    </form>
    <div class="table-wrap">
      <?php if (!$rows): ?><?= empty_state('No members found', 'Adjust the filters or register a member above.') ?>
      <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Contact</th><th>Joined</th><th>Status</th><th></th></tr></thead><tbody>
        <?php foreach ($rows as $m): ?><tr>
          <td><a href="<?= e(url('admin/member.php?id=' . $m['id'])) ?>"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></a><br><span class="small text-muted-eden"><?= e($m['member_number']) ?></span></td>
          <td class="small"><?= e($m['email']) ?><br><?= e($m['phone'] ?? '—') ?></td>
          <td class="small"><?= e(fmt_date($m['joined_at'])) ?></td>
          <td><?= status_badge($m['status']) ?></td>
          <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/member.php?id=' . $m['id'])) ?>">Open</a></td>
        </tr><?php endforeach; ?>
      </tbody></table><?php endif; ?>
    </div>
    <?php if ($pages > 1): ?><p class="small text-muted-eden mt-3">Page <?= $page ?> of <?= $pages ?> —
      <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>&search=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>">← Previous</a><?php endif; ?>
      <?php if ($page < $pages): ?> <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>">Next →</a><?php endif; ?></p><?php endif; ?>
  </div>
</div>
<?php dash_end(); ?>
