<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $count = (int) db()->query('SELECT COUNT(*) AS c FROM admin_users')->fetch_assoc()['c'];
    if ($count > 0) {
        jsonResponse(false, 'Admin already configured', [], 409);
    }

    $username = sanitize((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || strlen($password) < 6) {
        jsonResponse(false, 'Username and password (min 6 chars) are required', [], 422);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
    $stmt->bind_param('ss', $username, $hash);
    $stmt->execute();

    jsonResponse(true, 'Admin created');
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Setup failed', [], 500);
}
