<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

/**
 * ONE-TIME first-administrator setup, for hosts without SSH/CLI
 * (InfinityFree has no terminal access). It refuses to run once any
 * administrator exists, requires SETUP_KEY from config/database.php,
 * and should be deleted after use.
 */
$admins = (int) db_val("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$locked = $admins > 0;
$keyOk  = isset($_GET['key']) && defined('SETUP_KEY') && hash_equals(SETUP_KEY, (string) $_GET['key']);
$message = ''; $type = 'err';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    csrf_verify();
    try {
        if (!defined('SETUP_KEY') || SETUP_KEY === 'change-this-to-a-random-string') throw new RuntimeException('Set a private SETUP_KEY in config/database.php first.');
        if (!hash_equals(SETUP_KEY, post('setup_key'))) throw new RuntimeException('Setup key is incorrect.');
        $email = strtolower(post('email')); $name = post('name'); $pass = (string) ($_POST['password'] ?? '');
        if (!valid_email($email) || $name === '') throw new RuntimeException('Provide a valid email address and full name.');
        if (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) throw new RuntimeException('Password needs 8+ characters with letters and numbers.');
        if (db_val("SELECT 1 FROM users WHERE email = ?", [$email])) throw new RuntimeException('That email already exists.');
        $uid = tx(function () use ($email, $name, $pass) {
            $id = db_insert("INSERT INTO users (email,password_hash,full_name,role,status,must_change_password) VALUES (?,?,?,'admin','active',1)",
                [$email, password_hash($pass, PASSWORD_DEFAULT), $name]);
            audit('install.first_admin_created', 'user', $id, ['via' => 'setup-admin.php'], $id, $email);
            return $id;
        });
        $message = 'Administrator created (user #' . $uid . '). DELETE setup-admin.php now, then sign in and change the password.';
        $type = 'ok'; $locked = true;
    } catch (Throwable $e) {
        $message = $e->getMessage();
    }
}
page_head('First Administrator Setup');
?>
<div class="container section" style="max-width:620px">
  <div class="card-eden">
    <h1 class="section-title" style="font-size:1.6rem">First administrator setup</h1>
    <?php if ($message !== ''): ?><div class="alert-eden <?= $type === 'ok' ? 'alert-eden-ok' : 'alert-eden-err' ?>"><span><?= $type === 'ok' ? '✓' : '!' ?></span><div><?= e($message) ?></div></div><?php endif; ?>
    <?php if ($locked): ?>
      <div class="notice">Setup is locked because an administrator account already exists. Create further staff accounts from <strong>Admin → Staff Management</strong>. Delete this file from the server.</div>
      <a class="btn btn-eden" href="<?= e(url('login.php')) ?>">Go to sign in</a>
    <?php else: ?>
      <div class="notice">This page works <strong>once</strong>. It requires the private <code>SETUP_KEY</code> from <code>config/database.php</code>. After creating the account, delete <code>setup-admin.php</code> from the server.</div>
      <form method="post">
        <?= csrf_field() ?>
        <div class="field"><label for="setup_key">Setup key</label><input class="input" id="setup_key" name="setup_key" type="password" required></div>
        <div class="field"><label for="name">Full name</label><input class="input" id="name" name="name" required maxlength="160"></div>
        <div class="field"><label for="email">Email</label><input class="input" id="email" name="email" type="email" required maxlength="190"></div>
        <div class="field"><label for="password">Password</label><input class="input" id="password" name="password" type="password" required minlength="8"><p class="hint">8+ characters with letters and numbers.</p></div>
        <button class="btn btn-eden" type="submit">Create administrator</button>
      </form>
    <?php endif; ?>
    <?php if (!$keyOk && !$locked): ?><p class="hint mt-3">Tip: you can also pass <code>?key=YOUR_SETUP_KEY</code> in the URL to pre-verify the key.</p><?php endif; ?>
  </div>
</div>
<?php public_footer(); ?>
