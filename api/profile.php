<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../config/database.php';

requireAuthJson();

function ensureAdminProfileColumns(): void
{
    $required = [
        'name' => "ALTER TABLE admin_users ADD COLUMN name VARCHAR(150) NULL AFTER username",
        'picture_path' => "ALTER TABLE admin_users ADD COLUMN picture_path VARCHAR(255) NULL AFTER password_hash",
    ];

    $result = db()->query('SHOW COLUMNS FROM admin_users');
    $existing = [];
    while ($row = $result->fetch_assoc()) {
        $existing[] = $row['Field'];
    }

    foreach ($required as $column => $sql) {
        if (!in_array($column, $existing, true)) {
            db()->query($sql);
        }
    }
}

function getPublicPictureUrl(?string $path): string
{
    if (!$path) {
        return '';
    }
    return '/zou-finance/' . ltrim(str_replace('\\', '/', $path), '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        ensureAdminProfileColumns();
        $adminId = (int) ($_SESSION['admin_id'] ?? 0);
        $stmt = db()->prepare('SELECT id, username, COALESCE(name, "") AS name, COALESCE(picture_path, "") AS picture_path FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            jsonResponse(false, 'Profile not found', [], 404);
        }

        jsonResponse(true, 'Profile loaded', [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'username' => (string) $user['username'],
            'picture_url' => getPublicPictureUrl((string) $user['picture_path']),
        ]);
    } catch (Throwable $e) {
        logError($e);
        jsonResponse(false, 'Failed to load profile', [], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    ensureAdminProfileColumns();
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);

    $name = sanitize((string) ($_POST['name'] ?? ''));
    $username = sanitize((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '') {
        jsonResponse(false, 'Username is required', [], 422);
    }
    if ($password !== '' && strlen($password) < 6) {
        jsonResponse(false, 'Password must be at least 6 characters', [], 422);
    }

    $checkStmt = db()->prepare('SELECT id FROM admin_users WHERE username = ? AND id <> ? LIMIT 1');
    $checkStmt->bind_param('si', $username, $adminId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->fetch_assoc()) {
        jsonResponse(false, 'Username already exists', [], 409);
    }

    $picturePath = null;
    if (isset($_FILES['picture']) && is_array($_FILES['picture']) && (int) $_FILES['picture']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ((int) $_FILES['picture']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Picture upload failed', [], 422);
        }
        if ((int) $_FILES['picture']['size'] > 2 * 1024 * 1024) {
            jsonResponse(false, 'Picture must be under 2MB', [], 422);
        }

        $tmpFile = (string) $_FILES['picture']['tmp_name'];
        $mime = mime_content_type($tmpFile) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            jsonResponse(false, 'Only JPG, PNG, or WEBP images are allowed', [], 422);
        }

        $uploadDir = __DIR__ . '/../public/uploads/profiles';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            jsonResponse(false, 'Failed to create upload directory', [], 500);
        }

        $filename = 'admin_' . $adminId . '_' . time() . '.' . $allowed[$mime];
        $destPath = $uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpFile, $destPath)) {
            jsonResponse(false, 'Failed to save picture', [], 500);
        }
        $picturePath = 'public/uploads/profiles/' . $filename;
    }

    if ($password !== '' && $picturePath !== null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE admin_users SET name = ?, username = ?, password_hash = ?, picture_path = ? WHERE id = ?');
        $stmt->bind_param('ssssi', $name, $username, $hash, $picturePath, $adminId);
        $stmt->execute();
    } elseif ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE admin_users SET name = ?, username = ?, password_hash = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $username, $hash, $adminId);
        $stmt->execute();
    } elseif ($picturePath !== null) {
        $stmt = db()->prepare('UPDATE admin_users SET name = ?, username = ?, picture_path = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $username, $picturePath, $adminId);
        $stmt->execute();
    } else {
        $stmt = db()->prepare('UPDATE admin_users SET name = ?, username = ? WHERE id = ?');
        $stmt->bind_param('ssi', $name, $username, $adminId);
        $stmt->execute();
    }

    $_SESSION['admin_username'] = $username;

    $profileStmt = db()->prepare('SELECT id, username, COALESCE(name, "") AS name, COALESCE(picture_path, "") AS picture_path FROM admin_users WHERE id = ? LIMIT 1');
    $profileStmt->bind_param('i', $adminId);
    $profileStmt->execute();
    $updated = $profileStmt->get_result()->fetch_assoc();

    jsonResponse(true, 'Profile updated', [
        'id' => (int) $updated['id'],
        'name' => (string) $updated['name'],
        'username' => (string) $updated['username'],
        'picture_url' => getPublicPictureUrl((string) $updated['picture_path']),
    ]);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to update profile', [], 500);
}
