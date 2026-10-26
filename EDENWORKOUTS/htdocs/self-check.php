<?php
declare(strict_types=1);
/**
 * EDEN WORKOUTS — hosting self-check.
 * Open this page once after uploading to confirm the server meets requirements,
 * then DELETE it. It never prints passwords or connection strings.
 */
$checks = [];
$checks[] = ['PHP 8.0 or newer', PHP_VERSION_ID >= 80000, PHP_VERSION];
foreach (['pdo_mysql', 'mbstring', 'json', 'openssl'] as $ext) {
    $checks[] = ['Extension: ' . $ext, extension_loaded($ext), extension_loaded($ext) ? 'loaded' : 'missing'];
}
$checks[] = ['config/database.php present', is_file(__DIR__ . '/config/database.php'), is_file(__DIR__ . '/config/database.php') ? 'yes' : 'missing — copy the example file'];
$dbOk = false; $tables = 0;
if (is_file(__DIR__ . '/config/database.php')) {
    try {
        require_once __DIR__ . '/includes/bootstrap.php';
        $dbOk = (bool) db_val('SELECT 1');
        $tables = (int) db_val("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()");
    } catch (Throwable $e) { $dbOk = false; }
}
$checks[] = ['Database connection', $dbOk, $dbOk ? 'connected' : 'failed — check config/database.php'];
$checks[] = ['Tables created (expect 25)', $tables >= 25, $tables . ' found'];
$adminCount = ($dbOk && $tables >= 25) ? (int) db_val("SELECT COUNT(*) FROM users WHERE role='admin'") : 0;
$checks[] = ['First administrator created', $adminCount > 0, $adminCount > 0 ? 'yes' : 'run setup-admin.php once'];
$checks[] = ['setup-admin.php removed', !is_file(__DIR__ . '/setup-admin.php'), is_file(__DIR__ . '/setup-admin.php') ? 'still present — delete after use' : 'removed'];
$allOk = !in_array(false, array_column($checks, 1), true);
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>EDEN WORKOUTS — hosting self-check</title><link href="<?= 'assets/css/style.css' ?>" rel="stylesheet"></head>
<body><div class="container section" style="max-width:760px">
<h1 class="section-title">Hosting self-check</h1>
<p class="text-muted-eden">This page confirms your InfinityFree account can run EDEN WORKOUTS. Delete it when you are done.</p>
<div class="panel"><div class="panel-body table-wrap"><table class="data"><thead><tr><th>Check</th><th>Result</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($checks as [$name, $pass, $detail]): ?>
<tr><td><?= htmlspecialchars($name) ?></td><td><span class="badge-eden <?= $pass ? 'b-ok' : 'b-bad' ?>"><?= $pass ? 'PASS' : 'FAIL' ?></span></td>
<td class="small text-muted-eden"><?= htmlspecialchars((string) $detail) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<p class="alert-eden <?= $allOk ? 'alert-eden-ok' : 'alert-eden-err' ?>"><span><?= $allOk ? '✓' : '!' ?></span><div><?= $allOk ? 'All checks passed — open your site and sign in.' : 'Fix the failed checks above before using the system.' ?></div></p>
<a class="btn btn-eden" href="index.php">Open the website</a>
</div></body></html>
