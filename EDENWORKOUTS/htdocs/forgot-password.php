<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
page_head('Forgot Password');
public_nav('');
?>
<div class="container section" style="max-width:560px">
  <div class="card-eden">
    <h1 class="section-title" style="font-size:1.5rem">Reset your password</h1>
    <div class="notice mt-3"><strong>Email delivery is not configured on this hosting.</strong><br>
      InfinityFree free hosting does not provide reliable outbound email, so password resets are handled by staff. Ask reception or an administrator to reset your password from <em>Staff Management</em>; they will give you a temporary password and you can change it here after signing in.</div>
    <p class="small text-muted-eden">For security, this page does not reveal whether an email address is registered.</p>
    <a href="<?= e(url('login.php')) ?>" class="btn btn-eden-ghost">Back to sign in</a>
    <a href="<?= e(url('change-password.php')) ?>" class="btn btn-eden">Change my password</a>
  </div>
</div>
<?php public_footer(); ?>
