<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
dash_start($u, 'Change password', 'Signed in as ' . $u['full_name'] . ' (' . ROLE_LABEL[$u['role']] . ').');
?>
<div class="panel" style="max-width:560px">
  <div class="panel-head"><h2>Account security</h2></div>
  <div class="panel-body">
    <form action="<?= e(url('actions/handle.php?do=password.change')) ?>" method="post">
      <?= csrf_field() ?>
      <div class="field"><label for="current_password">Current password</label><input class="input" id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
      <div class="field"><label for="password">New password</label><input class="input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password"><p class="hint">8+ characters with letters and numbers.</p></div>
      <div class="field"><label for="confirm_password">Confirm new password</label><input class="input" id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"></div>
      <button class="btn btn-eden" type="submit">Update password</button>
    </form>
    <?php if (!empty($u['must_change_password'])): ?><p class="hint">Your account is flagged to change the password set by an administrator.</p><?php endif; ?>
  </div>
</div>
<?php dash_end(); ?>
