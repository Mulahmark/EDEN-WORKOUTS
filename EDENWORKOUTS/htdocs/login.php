<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_ui.php';
$u = current_user();
if ($u) redirect(ROLE_HOME[$u['role']]);
$emailValue = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailValue = post('email');
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        // Page was left open long enough for the session to expire: show a fresh form instead of a dead end.
        err('This page had expired. Please sign in again.');
    } else {
        $r = attempt_login($emailValue, (string) ($_POST['password'] ?? ''));
        if ($r['ok']) { ok($r['message']); $u = current_user(); redirect(ROLE_HOME[$u['role']]); }
        err($r['message']);
    }
}
page_head('Sign in');
auth_styles();
?>
<main class="auth">
  <?php auth_visual('Build strength.', 'Become more.', 'Your plans, payments, attendance and progress, all in one place.', [
      ['bi-calendar-check', 'Track your membership and attendance'],
      ['bi-clipboard2-pulse', 'Follow workout plans from your coach'],
      ['bi-graph-up-arrow', 'Watch your progress improve over time'],
  ]); ?>

  <section class="auth-panel">
    <div class="auth-box">
      <a class="auth-back" href="<?= e(url('index.php')) ?>"><i class="bi bi-arrow-left"></i> Back to website</a>
      <h1>Welcome back</h1>
      <p class="auth-sub">Sign in to your EDEN WORKOUTS dashboard.</p>

      <?= flash_markup() ?>

      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field">
          <label for="email">Email address</label>
          <div class="auth-input no-toggle">
            <i class="bi bi-envelope lead-icon"></i>
            <input class="input" id="email" name="email" type="email" required autofocus
                   autocomplete="email" placeholder="you@example.com" value="<?= e($emailValue) ?>">
          </div>
        </div>
        <div class="field">
          <label for="password">Password <a href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a></label>
          <div class="auth-input">
            <i class="bi bi-lock lead-icon"></i>
            <input class="input" id="password" name="password" type="password" required
                   autocomplete="current-password" placeholder="Enter your password">
            <button class="auth-toggle" type="button" data-for="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
          </div>
        </div>
        <button class="btn btn-eden auth-submit" type="submit">Sign in <i class="bi bi-arrow-right"></i></button>
      </form>

      <div class="auth-foot">New to EDEN WORKOUTS? <a href="<?= e(url('register.php')) ?>">Create an account</a></div>
    </div>
  </section>
</main>
<?php auth_scripts(); ?>
</body></html>