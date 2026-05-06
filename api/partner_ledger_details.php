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
    if ($partnerId < 1) {
        jsonResponse(false, 'partner_id is required', [], 422);
    }

    $range = normalizeFilterRange(sanitize((string) ($_GET['range'] ?? 'monthly')));
    [$start, $end] = parseFilterRange($range, $_GET['from'] ?? null, $_GET['to'] ?? null);

    $data = $service->partnerLedgerDetails($partnerId, $start, $end);
    jsonResponse(true, 'Partner ledger details loaded', $data);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to load partner ledger details', [], 500);
}

