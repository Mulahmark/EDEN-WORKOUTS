<?php
declare(strict_types=1);

function e($v): string { return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function url(string $path = ''): string { return rtrim(APP_URL, '/') . '/' . ltrim($path, '/'); }
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }

function redirect(string $path): void { header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path))); exit; }

function flash(string $type, string $message): void { $_SESSION['flash'][] = ['type' => $type, 'message' => $message]; }
function ok(string $m): void { flash('ok', $m); }
function err(string $m): void { flash('err', $m); }
function take_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function money($v, ?string $currency = null): string
{
    $c = $currency ?? setting('currency', 'KES');
    return $c . ' ' . number_format((float) $v, 2);
}

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try { $cache = db_one('SELECT * FROM gym_settings WHERE id = 1') ?: []; }
        catch (Throwable $e) { $cache = []; }
    }
    $v = $cache[$key] ?? null;
    return $v === null || $v === '' ? $default : (string) $v;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Security check failed. Go back, reload the page and try again.');
    }
}

function post(string $k, string $default = ''): string { return trim((string) ($_POST[$k] ?? $default)); }
function get(string $k, string $default = ''): string { return trim((string) ($_GET[$k] ?? $default)); }

function valid_email(string $v): bool { return (bool) filter_var($v, FILTER_VALIDATE_EMAIL); }

/** Accepts 07XXXXXXXX, 01XXXXXXXX, +2547XXXXXXXX, 2547..., or +international. */
function normalise_phone(string $raw): string
{
    $digits = preg_replace('/[^\d+]/', '', $raw);
    if (preg_match('/^(\+?254|0)(7|1)\d{8}$/', ltrim($digits, '+'), $m)) {
        $national = preg_replace('/^\+?254/', '', ltrim($digits, '+'));
        $national = preg_replace('/^0/', '', $national);
        return '+254' . $national;
    }
    if (preg_match('/^\+\d{8,15}$/', $digits)) return $digits;
    throw new InvalidArgumentException('Use a Kenyan number (07XX or +2547XX) or a valid international number.');
}

function money_input(string $raw): float
{
    $n = (float) str_replace([',', ' '], '', $raw);
    if (!is_finite($n) || $n < 0.01 || $n > 10000000) throw new InvalidArgumentException('Enter a valid amount between 0.01 and 10,000,000.');
    return round($n, 2);
}

function today(): string { return (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('Y-m-d'); }
function now_local(): string { return (new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE)))->format('Y-m-d\TH:i'); }
function fmt_date(?string $d): string { return $d ? date('d M Y', strtotime($d)) : '—'; }
function fmt_datetime(?string $d): string { return $d ? date('d M Y, H:i', strtotime($d)) : '—'; }

/**
 * Expiry rule: start date is the first valid day, end date is the LAST valid day.
 * Month-end is clamped (31 Jan + 1 month = 28/29 Feb).
 */
function compute_end_date(string $start, int $value, string $unit): string
{
    $d = new DateTimeImmutable($start . ' 00:00:00', new DateTimeZone(APP_TIMEZONE));
    $value = max(1, $value);
    switch ($unit) {
        case 'day':     return $d->modify('+' . ($value - 1) . ' days')->format('Y-m-d');
        case 'week':    return $d->modify('+' . ($value * 7 - 1) . ' days')->format('Y-m-d');
        case 'month':   $end = $d->modify('+' . $value . ' months'); break;
        case 'quarter': $end = $d->modify('+' . ($value * 3) . ' months'); break;
        case 'year':    $end = $d->modify('+' . ($value * 12) . ' months'); break;
        default:        return $start;
    }
    return $end->modify('-1 day')->format('Y-m-d');
}

function duration_label(int $v, string $unit): string
{
    $map = ['day' => 'Days', 'week' => 'Weeks', 'month' => 'Months', 'quarter' => 'Quarters', 'year' => 'Years'];
    $word = $map[$unit] ?? ucfirst($unit);
    if ($v === 1) $word = rtrim($word, 's');
    return $v . ' ' . $word;
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    return strtoupper(substr($parts[0] ?? 'E', 0, 1) . substr($parts[1] ?? 'W', 0, 1));
}

function ref_code(string $prefix): string { return $prefix . '-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))); }

function balance_of($due, $paid): float { return max(0.0, round((float) $due - (float) $paid, 2)); }

function pct_of($current, $start, $target): ?int
{
    if ($start === null || $target === null || $current === null) return null;
    $range = (float) $target - (float) $start;
    if (abs($range) < 0.001) return null;
    return (int) max(0, min(100, round(((float) $current - (float) $start) / $range * 100)));
}

function page_param(int $default = 1): int { $p = (int) ($_GET['page'] ?? $default); return $p < 1 ? 1 : $p; }

function old(string $k, string $default = ''): string { return e($_SESSION['old'][$k] ?? $default); }
function clear_old(): void { unset($_SESSION['old']); }
