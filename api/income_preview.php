<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $amount = (float) ($_GET['amount'] ?? 0);
    $type = sanitize((string) ($_GET['type'] ?? 'distributed'));
    if ($amount < 0 || !in_array($type, ['distributed', 'company_only', 'external_source'], true)) {
        jsonResponse(false, 'Invalid preview request', [], 422);
    }

    $preview = $service->incomePreview($amount, $type);
    jsonResponse(true, 'Preview generated', $preview);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to generate preview', [], 500);
}
