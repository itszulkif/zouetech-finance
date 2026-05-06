<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

if (!isPartnerLoggedIn()) {
    jsonResponse(false, 'Unauthorized', [], 401);
}

$service = new FinanceService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $range = sanitize((string) ($_GET['range'] ?? 'monthly'));
    [$start, $end] = parseFilterRange($range, $_GET['from'] ?? null, $_GET['to'] ?? null);
    $partnerId = (int) $_SESSION['partner_id'];
    $data = $service->partnerLedger($partnerId, $start, $end);
    jsonResponse(true, 'Partner ledger loaded', $data);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to load partner ledger', [], 500);
}
