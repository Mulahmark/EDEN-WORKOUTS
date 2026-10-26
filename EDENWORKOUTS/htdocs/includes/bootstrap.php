<?php
declare(strict_types=1);

$cfg = __DIR__ . '/../config/database.php';
if (!is_file($cfg)) {
    http_response_code(500);
    exit('Configuration missing. Copy config/database.example.php to config/database.php and fill in your database details.');
}
require_once $cfg;

date_default_timezone_set(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Africa/Nairobi');
if (defined('DEBUG_MODE') && DEBUG_MODE) { ini_set('display_errors', '1'); error_reporting(E_ALL); }
else { ini_set('display_errors', '0'); error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE); ini_set('log_errors', '1'); }

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('eden_session');
    session_set_cookie_params([
        'lifetime' => (defined('SESSION_TTL_H') ? SESSION_TTL_H : 8) * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
    ]);
    session_start();
}
// Idle timeout
$ttl = (defined('SESSION_TTL_H') ? SESSION_TTL_H : 8) * 3600;
if (isset($_SESSION['last_seen']) && (time() - (int) $_SESSION['last_seen']) > $ttl) {
    $_SESSION = []; session_regenerate_id(true); $_SESSION['flash'][] = ['type' => 'ok', 'message' => 'Your session expired. Please sign in again.'];
}
$_SESSION['last_seen'] = time();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/services.php';
require_once __DIR__ . '/layout.php';