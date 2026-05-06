<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $username = sanitize((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT pu.partner_id, pu.password_hash, p.name FROM partner_users pu JOIN partners p ON p.id = pu.partner_id WHERE pu.username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        jsonResponse(false, 'Invalid credentials', [], 401);
    }

    $_SESSION['partner_id'] = (int) $row['partner_id'];
    $_SESSION['partner_name'] = $row['name'];
    session_regenerate_id(true);
    jsonResponse(true, 'Partner login successful');
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Partner login failed', [], 500);
}
