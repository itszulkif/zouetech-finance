<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['admin_id']);
}

function isPartnerLoggedIn(): bool
{
    return isset($_SESSION['partner_id']);
}

function requireAuthJson(): void
{
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function requireAuthPage(): void
{
    if (!isLoggedIn()) {
        header('Location: /zou-finance/public/login.php');
        exit;
    }
}

function requirePartnerPage(): void
{
    if (!isPartnerLoggedIn()) {
        header('Location: /zou-finance/public/partner-login.php');
        exit;
    }
}
