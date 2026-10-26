<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_role(['member', 'admin']);
$mid = (int) ($u['member_id'] ?? 0); guard_member($mid, $u);
$m = db_one("SELECT * FROM members WHERE id=?", [$mid]);
dash_start($u, 'My profile', 'Update your contact and emergency details. Membership number, email and status are managed by the gym.', 'member/profile.php');
?>
<div class="grid-2">
  <div class="panel"><div class="panel-head"><h2>My details</h2></div><div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=profile.update')) ?>" method="post"><?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-12"><div class="field"><label>Phone</label><input class="input" name="phone" value="<?= e($m['phone']) ?>" required><p class="hint">07XX XXX XXX or +2547XXXXXXXX</p></div></div>
        <div class="col-6"><div class="field"><label>Date of birth</label><input class="input" type="date" name="date_of_birth" value="<?= e($m['date_of_birth']) ?>"></div></div>
        <div class="col-6"><div class="field"><label>Gender</label><select class="input" name="gender"><option value="">Not stated</option><?php foreach (['female' => 'Female', 'male' => 'Male', 'other' => 'Other'] as $k => $v): ?><option value="<?= $k ?>" <?= $m['gender'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div></div>
        <div class="col-6"><div class="field"><label>Emergency contact</label><input class="input" name="emergency_contact_name" value="<?= e($m['emergency_contact_name']) ?>"></div></div>
        <div class="col-6"><div class="field"><label>Emergency phone</label><input class="input" name="emergency_contact_phone" value="<?= e($m['emergency_contact_phone']) ?>"></div></div>
      </div>
      <button class="btn btn-eden" type="submit">Save profile</button></form>
  </div></div>
  <div class="panel"><div class="panel-head"><h2>Account summary</h2></div><div class="panel-body">
    <table class="data" style="min-width:0"><tbody>
      <?php foreach ([['Member number', $m['member_number']], ['Status', $m['status']], ['Joined', fmt_date($m['joined_at'])], ['Email', $m['email']], ['Phone', $m['phone'] ?? '—']] as [$k, $v]): ?>
        <tr><td class="small text-muted-eden" style="width:45%"><?= e($k) ?></td><td><?= e($v) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
    <div class="d-flex flex-wrap gap-2 mt-3">
      <a class="btn btn-eden-ghost btn-sm" href="<?= e(url('change-password.php')) ?>">Change password</a>
      <a class="btn btn-eden-quiet btn-sm" href="<?= e(url('member/payments.php')) ?>">My payments</a>
    </div>
    <p class="hint mt-2">Staff notes about your membership are restricted to authorised staff and are written to the audit log when changed.</p>
  </div></div>
</div>
<?php dash_end(); ?>
