<?php
declare(strict_types=1);

function page_head(string $title, string $description = ''): void
{
    $gym = setting('gym_name', 'EDEN WORKOUTS');
    ?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e($gym) ?></title>
<meta name="description" content="<?= e($description !== '' ? $description : setting('tagline', 'BUILD STRENGTH. TRACK PROGRESS. BECOME MORE.')) ?>">
<link rel="icon" href="<?= e(asset('images/favicon.svg')) ?>" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<?php
}

function brand(int $size = 38): string
{
    return '<span class="eden-logo"><svg width="' . $size . '" height="' . $size . '" viewBox="0 0 96 96" role="img" aria-label="EDEN WORKOUTS mark">'
        . '<defs><linearGradient id="edg" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#FF914D"/><stop offset="100%" stop-color="#FF6B35"/></linearGradient></defs>'
        . '<rect width="96" height="96" rx="22" fill="#2C353D"/><rect x="8" y="8" width="80" height="80" rx="18" fill="#28313A"/>'
        . '<rect x="18" y="24" width="10" height="48" rx="5" fill="url(#edg)"/><rect x="68" y="24" width="10" height="48" rx="5" fill="url(#edg)"/>'
        . '<rect x="28" y="34" width="40" height="9" rx="4.5" fill="#F5F5F5"/><rect x="28" y="53" width="40" height="9" rx="4.5" fill="#F5F5F5"/></svg>'
        . '<span class="eden-logo-text"><strong>EDEN</strong><small>WORKOUTS</small></span></span>';
}

function public_nav(string $active = ''): void
{
    $links = ['index.php' => 'Home', 'about.php' => 'About', 'memberships.php' => 'Memberships', 'trainers.php' => 'Trainers', 'services.php' => 'Services', 'contact.php' => 'Contact'];
    $u = current_user();
    ?>
<header class="site-header">
  <div class="container d-flex align-items-center justify-content-between gap-3">
    <a href="<?= e(url('index.php')) ?>" class="brand-link"><?= brand() ?></a>
    <nav class="site-nav d-none d-lg-flex">
      <?php foreach ($links as $href => $label): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="d-flex align-items-center gap-2">
      <?php if ($u): ?>
        <a class="btn btn-eden btn-sm" href="<?= e(url(ROLE_HOME[$u['role']])) ?>">My dashboard</a>
      <?php else: ?>
        <a class="btn btn-eden-ghost btn-sm" href="<?= e(url('login.php')) ?>">Login</a>
        <a class="btn btn-eden btn-sm" href="<?= e(url('register.php')) ?>">Join Now</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="container site-nav-mobile d-lg-none">
    <?php foreach ($links as $href => $label): ?>
      <a href="<?= e(url($href)) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</header>
<?= flash_markup() ?>
<?php
}

function public_footer(): void
{
    ?>
<footer class="site-footer">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4"><?= brand(34) ?><p class="mt-3 small text-muted-eden"><?= e(setting('tagline')) ?></p></div>
      <div class="col-6 col-lg-3"><h3>Visit us</h3><ul><li><?= e(setting('address', 'Address to be confirmed')) ?></li><li><?= e(setting('phone', 'Phone to be confirmed')) ?></li><li><?= e(setting('email', 'Email to be confirmed')) ?></li></ul></div>
      <div class="col-6 col-lg-2"><h3>Explore</h3><ul>
        <?php foreach (['about.php' => 'About', 'memberships.php' => 'Memberships', 'trainers.php' => 'Trainers', 'contact.php' => 'Contact'] as $h => $l): ?>
          <li><a href="<?= e(url($h)) ?>"><?= e($l) ?></a></li>
        <?php endforeach; ?>
      </ul></div>
      <div class="col-lg-3"><h3>Follow</h3><ul>
        <?php $socials = ['facebook_url' => 'Facebook', 'instagram_url' => 'Instagram', 'x_url' => 'X', 'tiktok_url' => 'TikTok'];
        $any = false; foreach ($socials as $k => $l) { $v = setting($k); if ($v !== '') { $any = true; echo '<li><a href="' . e($v) . '" rel="noopener" target="_blank">' . e($l) . '</a></li>'; } }
        if (!$any): ?><li class="text-muted-eden">Social links are configured in Settings → Public Website.</li><?php endif; ?>
      </ul></div>
    </div>
    <div class="footer-bottom">© <?= date('Y') ?> <?= e(setting('gym_name', 'EDEN WORKOUTS')) ?>. All rights reserved. · Prices in <?= e(setting('currency', 'KES')) ?> · <?= e(setting('timezone', 'Africa/Nairobi')) ?></div>
  </div>
</footer>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body></html>
<?php
}

function flash_markup(): string
{
    $f = take_flash(); if (!$f) return '';
    $out = '<div class="container mt-3">';
    foreach ($f as $m) {
        $cls = $m['type'] === 'ok' ? 'alert-eden-ok' : 'alert-eden-err';
        $out .= '<div class="alert-eden ' . $cls . '" role="status"><span>' . ($m['type'] === 'ok' ? '✓' : '!') . '</span><div>' . e($m['message']) . '</div></div>';
    }
    return $out . '</div>';
}

function dash_nav(string $role): array
{
    $nav = [
        'admin' => [
            'admin/dashboard.php' => 'Overview', 'admin/members.php' => 'Members', 'admin/plans.php' => 'Membership Plans',
            'admin/memberships.php' => 'Subscriptions', 'admin/attendance.php' => 'Attendance', 'admin/payments.php' => 'Payments',
            'admin/trainers.php' => 'Trainers', 'admin/workouts.php' => 'Workout Plans', 'admin/exercises.php' => 'Exercises',
            'admin/progress.php' => 'Fitness Progress', 'admin/expenses.php' => 'Expenses', 'admin/reports.php' => 'Reports',
            'admin/notifications.php' => 'Notifications', 'admin/enquiries.php' => 'Contact Enquiries',
            'admin/staff.php' => 'Staff Management', 'admin/audit-logs.php' => 'Audit Logs', 'admin/settings.php' => 'Settings',
            'admin/website.php' => 'Public Website',
        ],
        'receptionist' => [
            'reception/dashboard.php' => 'Overview', 'reception/members.php' => 'Members', 'reception/check-in.php' => 'Check-in',
            'reception/attendance.php' => 'Attendance', 'reception/memberships.php' => 'Subscriptions', 'reception/payments.php' => 'Payments',
        ],
        'trainer' => [
            'trainer/dashboard.php' => 'Overview', 'trainer/clients.php' => 'My Clients', 'trainer/workouts.php' => 'Workout Plans',
            'trainer/sessions.php' => 'Sessions', 'trainer/appointments.php' => 'Appointments', 'trainer/progress.php' => 'Client Progress',
        ],
        'member' => [
            'member/dashboard.php' => 'Dashboard', 'member/membership.php' => 'My Membership', 'member/payments.php' => 'Payments',
            'member/attendance.php' => 'My Attendance', 'member/workouts.php' => 'Workout Plans', 'member/sessions.php' => 'Log Workout',
            'member/progress.php' => 'My Progress', 'member/goals.php' => 'My Goals', 'member/appointments.php' => 'Appointments',
            'member/profile.php' => 'My Profile',
        ],
    ];
    return $nav[$role] ?? [];
}

function dash_start(array $user, string $title, string $sub = '', string $active = ''): void
{
    page_head($title);
    $unread = unread_notifications((int) $user['id']);
    ?>
<div class="dash-wrap">
  <aside class="dash-side">
    <div class="dash-brand"><?= brand(34) ?></div>
    <p class="dash-role"><?= e(ROLE_LABEL[$user['role']]) ?></p>
    <nav class="dash-nav">
      <?php foreach (dash_nav($user['role']) as $href => $label): ?>
        <a href="<?= e(url($href)) ?>" class="<?= $active === $href ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
      <a href="<?= e(url('change-password.php')) ?>">Change password</a>
      <a href="<?= e(url('index.php')) ?>">Public website</a>
    </nav>
    <div class="dash-foot"><strong><?= e(setting('gym_name', 'EDEN WORKOUTS')) ?></strong><br><?= e(setting('currency', 'KES')) ?> · <?= e(setting('timezone', 'Africa/Nairobi')) ?></div>
  </aside>
  <div class="dash-main">
    <header class="dash-top">
      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-eden-ghost btn-sm d-lg-none" type="button" data-toggle-side><i class="bi bi-list"></i> Menu</button>
        <span class="d-none d-lg-inline text-muted-eden small"><?= e(setting('gym_name', 'EDEN WORKOUTS')) ?></span>
      </div>
      <div class="d-flex align-items-center gap-2">
        <a href="<?= e(url($user['role'] === 'admin' ? 'admin/notifications.php' : 'member/index.php')) ?>" class="btn btn-eden-ghost btn-sm"><i class="bi bi-bell"></i> <?= $unread ? '<strong class="text-eden">' . count($unread) . '</strong>' : '' ?></a>
        <span class="d-none d-sm-inline small"><strong><?= e($user['full_name']) ?></strong><br><span class="text-muted-eden"><?= e(ROLE_LABEL[$user['role']]) ?></span></span>
        <a href="<?= e(url('logout.php')) ?>" class="btn btn-eden-quiet btn-sm">Logout</a>
      </div>
    </header>
    <main class="dash-body">
      <?= flash_markup() ?>
      <div class="page-head"><div><h1><?= e($title) ?></h1><?php if ($sub !== ''): ?><p><?= e($sub) ?></p><?php endif; ?></div></div>
<?php
}

function dash_end(): void
{
    ?>
    </main>
    <footer class="dash-footer"><?= e(setting('gym_name', 'EDEN WORKOUTS')) ?> management system</footer>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body></html>
<?php
}

function stat_card(string $label, $value, string $hint = '', string $tone = ''): string
{
    return '<div class="stat-card ' . e($tone) . '"><p>' . e($label) . '</p><strong>' . e((string) $value) . '</strong>'
        . ($hint !== '' ? '<small>' . e($hint) . '</small>' : '') . '</div>';
}

function status_badge(?string $status): string
{
    $s = $status ?? '';
    $good = ['active', 'completed', 'resolved', 'paid'];
    $warn = ['pending', 'new', 'in_progress', 'on_leave', 'partial'];
    $bad  = ['expired', 'suspended', 'cancelled', 'abandoned', 'reversed', 'no_show', 'spam'];
    $cls = in_array($s, $good, true) ? 'b-ok' : (in_array($s, $warn, true) ? 'b-warn' : (in_array($s, $bad, true) ? 'b-bad' : 'b-mute'));
    return '<span class="badge-eden ' . $cls . '">' . e($s === '' ? '—' : str_replace('_', ' ', $s)) . '</span>';
}

function empty_state(string $title, string $message): string
{
    return '<div class="empty-state"><i class="bi bi-activity"></i><strong>' . e($title) . '</strong><p>' . e($message) . '</p></div>';
}
