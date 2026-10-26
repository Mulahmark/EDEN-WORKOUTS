<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/auth_ui.php';
$u = current_user(); if ($u) redirect(ROLE_HOME[$u['role']]);
$planId = (int) get('plan');
$plan = $planId ? db_one("SELECT * FROM membership_plans WHERE id = ? AND is_active = 1", [$planId]) : null;
// On a validation error the handler sends people back here (not to the home page).
$backTo = 'register.php' . ($plan ? '?plan=' . (int) $plan['id'] : '');
page_head('Become a Member');
auth_styles();
?>
<main class="auth">
  <?php auth_visual('Your strength.', 'Your Eden.', 'Join the community, train with purpose and track every step of your progress.', [
      ['bi-person-badge', 'Your member number is generated automatically'],
      ['bi-clipboard2-pulse', 'Workouts, measurements and goals once activated'],
      ['bi-shield-lock', 'Passwords are hashed, so nobody can read them'],
  ]); ?>

  <section class="auth-panel">
    <div class="auth-box wide">
      <a class="auth-back" href="<?= e(url('index.php')) ?>"><i class="bi bi-arrow-left"></i> Back to website</a>
      <h1>Become a member</h1>
      <p class="auth-sub">Create your account. The gym reviews every registration, then you choose a plan and pay at reception.</p>

      <?= flash_markup() ?>

      <?php if ($plan): ?>
        <div class="auth-plan">
          <div><small>Plan you selected</small><strong><?= e($plan['name']) ?></strong></div>
          <div class="price"><?= e(money($plan['price'])) ?> / <?= e(duration_label((int) $plan['duration_value'], $plan['duration_unit'])) ?></div>
        </div>
      <?php endif; ?>

      <form action="<?= e(url('actions/handle.php?do=register.submit')) ?>" method="post" autocomplete="on">
        <?= csrf_field() ?>
        <input type="hidden" name="back" value="<?= e($backTo) ?>">
        <div class="row g-3">
          <div class="col-sm-6"><div class="field">
            <label for="first_name">First name *</label>
            <div class="auth-input no-toggle"><i class="bi bi-person lead-icon"></i><input class="input" id="first_name" name="first_name" required maxlength="80" autocomplete="given-name" autofocus></div>
          </div></div>
          <div class="col-sm-6"><div class="field">
            <label for="last_name">Last name *</label>
            <div class="auth-input no-toggle"><i class="bi bi-person lead-icon"></i><input class="input" id="last_name" name="last_name" required maxlength="80" autocomplete="family-name"></div>
          </div></div>
          <div class="col-12"><div class="field">
            <label for="email">Email *</label>
            <div class="auth-input no-toggle"><i class="bi bi-envelope lead-icon"></i><input class="input" id="email" name="email" type="email" required maxlength="190" autocomplete="email" placeholder="you@example.com"></div>
          </div></div>
          <div class="col-12"><div class="field">
            <label for="phone">Phone *</label>
            <div class="auth-input no-toggle"><i class="bi bi-telephone lead-icon"></i><input class="input" id="phone" name="phone" type="tel" required autocomplete="tel" placeholder="0712 345 678"></div>
            <p class="hint">Kenyan format: 07XX XXX XXX or +2547XXXXXXXX.</p>
          </div></div>
          <div class="col-sm-6"><div class="field">
            <label for="password">Password *</label>
            <div class="auth-input"><i class="bi bi-lock lead-icon"></i><input class="input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password"><button class="auth-toggle" type="button" data-for="password" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
            <div class="auth-meter"><span id="pwMeter"></span></div>
            <p class="hint">8+ characters, letters and numbers.</p>
          </div></div>
          <div class="col-sm-6"><div class="field">
            <label for="confirm_password">Confirm password *</label>
            <div class="auth-input"><i class="bi bi-lock-fill lead-icon"></i><input class="input" id="confirm_password" name="confirm_password" type="password" required minlength="8" autocomplete="new-password"><button class="auth-toggle" type="button" data-for="confirm_password" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
            <div class="auth-match" id="pwMatch"></div>
          </div></div>
        </div>
        <button class="btn btn-eden auth-submit" type="submit">Create my account <i class="bi bi-arrow-right"></i></button>
        <p class="auth-note"><i class="bi bi-info-circle"></i><span>Your account stays <strong>pending</strong> until reception activates it. Cash, M-Pesa and bank payments are recorded by staff.</span></p>
      </form>

      <div class="auth-foot">Already registered? <a href="<?= e(url('login.php')) ?>">Sign in</a></div>
    </div>
  </section>
</main>
<?php auth_scripts(); ?>
<script>
  (function () {
    var pw = document.getElementById('password'), cf = document.getElementById('confirm_password'),
        meter = document.getElementById('pwMeter'), match = document.getElementById('pwMatch');
    function strength(v) {
      var s = 0;
      if (v.length >= 8) s++;
      if (/[A-Za-z]/.test(v) && /\d/.test(v)) s++;
      if (v.length >= 12) s++;
      if (/[^A-Za-z0-9]/.test(v)) s++;
      return s;
    }
    function update() {
      var s = strength(pw.value), colors = ['#EF4444', '#EF4444', '#FBBF24', '#22C55E', '#22C55E'];
      meter.style.width = (pw.value ? Math.max(15, s * 25) : 0) + '%';
      meter.style.background = colors[s];
      if (!cf.value) { match.textContent = ''; return; }
      var ok = pw.value === cf.value;
      match.textContent = ok ? 'Passwords match' : 'Passwords do not match yet';
      match.style.color = ok ? '#7ee0a1' : '#ff9b9b';
    }
    pw.addEventListener('input', update); cf.addEventListener('input', update);
  })();
</script>
</body></html>
