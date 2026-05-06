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
    $limit = inputInt($_GET, 'limit');
    if ($limit < 1 || $limit > 100) {
        $limit = 10;
    }

    $rows = $service->listIncomeDetails($start, $end, $range, $page, $limit);
    $summary = $service->incomeDetailsSummary($start, $end, $range);
    jsonResponse(true, 'Income details loaded', ['income' => $rows, 'summary' => $summary]);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to load income details', [], 500);
}
