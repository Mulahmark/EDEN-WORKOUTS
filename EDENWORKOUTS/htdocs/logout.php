<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
logout_user();
ok('You have been signed out.');
redirect('login.php');
