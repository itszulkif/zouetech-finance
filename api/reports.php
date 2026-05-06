<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $partnerId = inputInt($_GET, 'partner_id');
    $range = sanitize((string) ($_GET['range'] ?? 'all'));
    [$start, $end] = parseFilterRange($range, $_GET['from'] ?? null, $_GET['to'] ?? null);
    $data = $service->reports($partnerId > 0 ? $partnerId : null, $start, $end);

    jsonResponse(true, 'Reports loaded', $data);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to load reports', [], 500);
}
