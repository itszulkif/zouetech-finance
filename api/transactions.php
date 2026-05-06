<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $range = normalizeFilterRange(sanitize((string) ($_GET['range'] ?? 'all')));
    [$start, $end] = parseFilterRange($range, $_GET['from'] ?? null, $_GET['to'] ?? null);
    $page = max(1, inputInt($_GET, 'page'));
    $limit = max(1, min(100, inputInt($_GET, 'limit') ?: 20));

    $rows = $service->recentTransactions($start, $end, $page, $limit);
    jsonResponse(true, 'Transactions loaded', [
        'transactions' => $rows,
        'page' => $page,
        'limit' => $limit
    ]);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to load transactions', [], 500);
}
