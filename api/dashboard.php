<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $filter = normalizeFilterRange(sanitize((string) ($_GET['range'] ?? 'all')));
    $from = $_GET['from'] ?? null;
    $to = $_GET['to'] ?? null;
    [$start, $end] = parseFilterRange($filter, $from, $to);

    $data = $service->dashboard($start, $end, $filter);
    $data['applied_filter'] = [
        'range' => $filter,
        'start' => $start,
        'end' => $end
    ];
    jsonResponse(true, 'Dashboard loaded', $data);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to load dashboard', [], 500);
}
