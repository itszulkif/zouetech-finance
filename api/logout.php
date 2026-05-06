<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

session_unset();
session_destroy();
jsonResponse(true, 'Logged out');
