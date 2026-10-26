<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['receptionist', 'admin']);
$q = get('search');
$rows = $q
  ? db_all("SELECT * FROM members WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR member_number LIKE ? ORDER BY joined_at DESC LIMIT 50", ["%$q%", "%$q%", "%$q%", "%$q%"])
  : db_all("SELECT * FROM members ORDER BY joined_at DESC LIMIT 50");
dash_start($u, 'Members', 'Register new members at the desk and keep their contact details current.', 'reception/members.php');
?>
<div class="panel"><div class="panel-head"><h2>Register new member</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=member.create')) ?>" method="post"><?= csrf_field() ?>
    <input type="hidden" name="back" value="reception/members.php">
    <div class="row g-2">
      <div class="col-md-3"><div class="field"><label>First name</label><input class="input" name="first_name" required></div></div>
      <div class="col-md-3"><div class="field"><label>Last name</label><input class="input" name="last_name" required></div></div>
      <div class="col-md-3"><div class="field"><label>Email</label><input class="input" type="email" name="email" required></div></div>
      <div class="col-md-3"><div class="field"><label>Phone</label><input class="input" name="phone" required placeholder="0712 345 678"></div></div>
      <div class="col-md-3"><div class="field"><label>Date of birth</label><input class="input" type="date" name="date_of_birth"></div></div>
      <div class="col-md-3"><div class="field"><label>Login password (optional)</label><input class="input" type="password" name="password"></div></div>
      <div class="col-md-6"><div class="field"><label>Notes</label><input class="input" name="notes"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Create member</button>
  </form>
</div></div>
<div class="panel"><div class="panel-head"><h2>Directory</h2></div><div class="panel-body">
  <form method="get" class="d-flex gap-2 align-items-end mb-3"><div class="field mb-0"><label>Search</label><input class="input" name="search" value="<?= e($q) ?>" placeholder="Name, email or member number"></div><button class="btn btn-eden-ghost btn-sm">Search</button></form>
  <div class="table-wrap"><?php if (!$rows): ?><?= empty_state('No members found', 'Register the member above.') ?>
    <?php else: ?><table class="data"><thead><tr><th>Member</th><th>Contact</th><th>Joined</th><th>Status</th><th></th></tr></thead><tbody>
      <?php foreach ($rows as $m): ?><tr><td><?= e($m['first_name'] . ' ' . $m['last_name']) ?><br><span class="small text-muted-eden"><?= e($m['member_number']) ?></span></td>
        <td class="small"><?= e($m['email']) ?><br><?= e($m['phone'] ?? '—') ?></td><td class="small"><?= e(fmt_date($m['joined_at'])) ?></td><td><?= status_badge($m['status']) ?></td>
        <td><a class="btn btn-eden-ghost btn-sm" href="<?= e(url('admin/member.php?id=' . $m['id'])) ?>">Open profile</a></td></tr><?php endforeach; ?>
    </tbody></table><?php endif; ?></div>
  <p class="hint mt-2">Reception shares the member record screens with administrators. Changing subscription status, creating administrators or editing gym settings is restricted.</p>
</div></div>
<?php dash_end(); ?>
