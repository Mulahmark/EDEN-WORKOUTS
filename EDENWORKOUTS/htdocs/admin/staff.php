<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['admin']);
$rows = db_all("SELECT * FROM users ORDER BY role, full_name");
$reset = (int) get('reset');
dash_start($u, 'Staff management', 'Administrators, receptionists and trainer accounts. Privilege changes are audit-logged and the last active administrator is protected.', 'admin/staff.php');
?>
<div class="panel"><div class="panel-head"><h2>Create staff account</h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=staff.create')) ?>" method="post"><?= csrf_field() ?>
    <div class="row g-2">
      <div class="col-md-3"><div class="field"><label>Full name</label><input class="input" name="full_name" required></div></div>
      <div class="col-md-3"><div class="field"><label>Email</label><input class="input" type="email" name="email" required></div></div>
      <div class="col-md-2"><div class="field"><label>Phone</label><input class="input" name="phone"></div></div>
      <div class="col-md-2"><div class="field"><label>Role</label><select class="input" name="role"><option value="receptionist">Receptionist</option><option value="trainer">Trainer</option><option value="admin">Administrator</option></select></div></div>
      <div class="col-md-2"><div class="field"><label>Password</label><input class="input" type="password" name="password" required minlength="8"></div></div>
    </div>
    <button class="btn btn-eden" type="submit">Create account</button>
    <p class="hint">Administrator accounts can only be created here, by an existing administrator.</p>
  </form>
</div></div>
<?php if ($reset): ?>
<div class="panel" style="border-color:rgba(251,191,36,.5)"><div class="panel-head"><h2>Reset password for user #<?= $reset ?></h2></div><div class="panel-body">
  <form action="<?= e(url('actions/handle.php?do=staff.reset')) ?>" method="post" class="d-flex flex-wrap gap-2 align-items-end"><?= csrf_field() ?>
    <input type="hidden" name="user_id" value="<?= $reset ?>">
    <div class="field mb-0"><label>New password</label><input class="input" type="password" name="password" required minlength="8"><p class="hint">Share privately; the user is flagged to change it.</p></div>
    <button class="btn btn-eden btn-sm">Reset password</button></form>
</div></div>
<?php endif; ?>
<div class="panel"><div class="panel-head"><h2>Accounts (<?= count($rows) ?>)</h2></div><div class="panel-body table-wrap">
  <table class="data"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><strong><?= e($r['full_name']) ?></strong><br><span class="small text-muted-eden"><?= e($r['email']) ?></span><?= (int) $r['must_change_password'] ? '<br><span class="small text-eden">must change password</span>' : '' ?></td>
      <td><form action="<?= e(url('actions/handle.php?do=staff.role')) ?>" method="post" class="d-flex gap-1"><?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
        <select class="input" name="role" style="min-width:130px"><?php foreach (['admin', 'receptionist', 'trainer', 'member'] as $role): ?><option value="<?= $role ?>" <?= $r['role'] === $role ? 'selected' : '' ?>><?= e(ROLE_LABEL[$role]) ?></option><?php endforeach; ?></select>
        <button class="btn btn-eden-quiet btn-sm">Save</button></form></td>
      <td><?= status_badge($r['status']) ?></td><td class="small"><?= $r['last_login_at'] ? e(fmt_datetime($r['last_login_at'])) : 'never' ?></td>
      <td class="d-flex gap-1">
        <form action="<?= e(url('actions/handle.php?do=staff.status')) ?>" method="post" data-confirm="Change this account status?"><?= csrf_field() ?>
          <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="status" value="<?= $r['status'] === 'active' ? 'suspended' : 'active' ?>">
          <button class="btn btn-eden-ghost btn-sm"><?= $r['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form>
        <a class="btn btn-eden-quiet btn-sm" href="?reset=<?= (int) $r['id'] ?>">Reset password</a>
      </td></tr><?php endforeach; ?>
  </tbody></table>
</div></div>
<div class="panel"><div class="panel-head"><h2>Permission matrix</h2></div><div class="panel-body table-wrap"><table class="data">
  <thead><tr><th>Capability</th><th>Admin</th><th>Reception</th><th>Trainer</th><th>Member</th></tr></thead><tbody>
  <?php foreach ([
    ['Manage members', 'yes', 'yes', 'view assigned', 'self'],
    ['Subscriptions & renewals', 'yes', 'yes', 'no', 'request'],
    ['Record payments / receipts', 'yes', 'yes', 'no', 'own history'],
    ['Reverse payments', 'yes', 'no', 'no', 'no'],
    ['Attendance / overrides', 'yes', 'yes (no override)', 'no', 'own history'],
    ['Workout plans & exercises', 'yes', 'no', 'assigned clients', 'read own'],
    ['Measurements & goals', 'yes', 'no', 'assigned clients', 'own'],
    ['Expenses & financial reports', 'yes', 'no', 'no', 'no'],
    ['Staff, settings, audit log', 'yes', 'no', 'no', 'no'],
  ] as $row): ?><tr><td class="small"><?= e($row[0]) ?></td><?php for ($i = 1; $i <= 4; $i++): ?><td class="small text-muted-eden"><?= e($row[$i]) ?></td><?php endfor; ?></tr><?php endforeach; ?>
  </tbody></table></div></div>
<?php dash_end(); ?>
