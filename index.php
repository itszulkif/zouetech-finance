<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . app_url('/public/dashboard.php'));
    exit;
}

header('Location: ' . app_url('/public/login.php'));
exit;
