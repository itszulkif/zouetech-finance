<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: /zou-finance/public/dashboard.php');
    exit;
}

header('Location: /zou-finance/public/login.php');
exit;
