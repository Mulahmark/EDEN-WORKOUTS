<?php
declare(strict_types=1);

const ROLE_HOME = ['admin' => 'admin/dashboard.php', 'receptionist' => 'reception/dashboard.php', 'trainer' => 'trainer/dashboard.php', 'member' => 'member/dashboard.php'];
const ROLE_LABEL = ['admin' => 'Administrator', 'receptionist' => 'Receptionist', 'trainer' => 'Personal Trainer', 'member' => 'Member'];

function current_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    static $user = null;
    if ($user !== null) return $user;
    $u = db_one("SELECT id, email, full_name, role, status FROM users WHERE id = ? LIMIT 1", [(int) $_SESSION['uid']]);
    if (!$u || in_array($u['status'], ['suspended', 'inactive'], true)) { $_SESSION = []; return null; }
    if ($u['role'] === 'member') { $u['member_id'] = db_val("SELECT id FROM members WHERE user_id = ? LIMIT 1", [$u['id']]); }
    if ($u['role'] === 'trainer') { $u['trainer_id'] = db_val("SELECT id FROM trainers WHERE user_id = ? LIMIT 1", [$u['id']]); }
    return $user = $u;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) { ok('Please sign in to continue.'); redirect('login.php'); }
    return $u;
}

function require_role(array $roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) {
        err('You are not authorised to view that page.');
        redirect(ROLE_HOME[$u['role']] ?? 'login.php');
    }
    return $u;
}

function login_attempts_recent(string $email): int
{
    return (int) db_val("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND success = 0 AND created_at > (NOW() - INTERVAL 15 MINUTE)", [strtolower($email)]);
}

function log_attempt(string $email, bool $success): void
{
    db_run("INSERT INTO login_attempts (email, ip_address, success) VALUES (?,?,?)", [strtolower($email), client_ip(), $success ? 1 : 0]);
}

function client_ip(): string
{
    $f = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    return $f !== '' ? trim(explode(',', $f)[0]) : (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

/** @return array{ok:bool,message:string} */
function attempt_login(string $email, string $password): array
{
    $email = strtolower(trim($email));
    if (login_attempts_recent($email) >= 5) {
        return ['ok' => false, 'message' => 'Too many failed attempts. Wait 15 minutes and try again.'];
    }
    $u = db_one("SELECT * FROM users WHERE email = ? LIMIT 1", [$email]);
    if (!$u || !password_verify($password, $u['password_hash'])) {
        log_attempt($email, false);
        audit('login.failed', 'user', $u['id'] ?? null, ['email' => $email], $u['id'] ?? null, $email);
        if ($u) {
            $fails = (int) $u['failed_login_count'] + 1;
            db_run("UPDATE users SET failed_login_count = ?, locked_until = ? WHERE id = ?",
                [$fails, $fails >= 8 ? date('Y-m-d H:i:s', time() + 900) : null, $u['id']]);
        }
        return ['ok' => false, 'message' => 'Invalid email or password.'];
    }
    if (!empty($u['locked_until']) && strtotime($u['locked_until']) > time()) {
        return ['ok' => false, 'message' => 'This account is temporarily locked. Try again shortly.'];
    }
    if ($u['status'] === 'pending') return ['ok' => false, 'message' => 'Your account is awaiting activation by the gym.'];
    if ($u['status'] !== 'active') return ['ok' => false, 'message' => 'This account is not active. Contact reception.'];

    db_run("UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?", [$u['id']]);
    log_attempt($email, true);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    audit('login.success', 'user', (int) $u['id'], ['role' => $u['role']], (int) $u['id'], $email);
    return ['ok' => true, 'message' => 'Welcome back, ' . $u['full_name'] . '.'];
}

function logout_user(): void
{
    $u = current_user();
    if ($u) audit('logout', 'user', (int) $u['id'], null, (int) $u['id'], $u['email']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

/** IDOR guard: the only ways a member record may be touched. */
function guard_member(int $memberId, array $user): int
{
    if (in_array($user['role'], ['admin', 'receptionist'], true)) return $memberId;
    if ($user['role'] === 'member') {
        if ((int) ($user['member_id'] ?? 0) !== $memberId) { http_response_code(403); exit('You can only access your own records.'); }
        return $memberId;
    }
    if ($user['role'] === 'trainer') {
        $assigned = db_val("SELECT 1 FROM trainer_assignments WHERE trainer_id = ? AND member_id = ? AND status = 'active' LIMIT 1", [(int) $user['trainer_id'], $memberId]);
        if (!$assigned) { http_response_code(403); exit('That client is not assigned to you.'); }
        return $memberId;
    }
    http_response_code(403); exit('Not authorised.');
}
