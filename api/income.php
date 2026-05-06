<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed', [], 405);
}

try {
    $amount = inputFloat($_POST, 'amount');
    $type = sanitize((string) ($_POST['type'] ?? 'distributed'));
    $source = sanitize((string) ($_POST['source'] ?? 'normal'));
    $incomeDate = sanitize((string) ($_POST['transaction_date'] ?? ($_POST['income_date'] ?? '')));
    $note = sanitize((string) ($_POST['note'] ?? ''));
    $allowed = ['distributed', 'company_only', 'external_source'];
    $allowedSources = ['normal', 'external'];
    if ($amount <= 0 || !in_array($type, $allowed, true) || !in_array($source, $allowedSources, true)) {
        jsonResponse(false, 'Invalid amount or income type', [], 422);
    }
    if ($incomeDate === '') {
        jsonResponse(false, 'Transaction date is required', [], 422);
    }
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $incomeDate);
    if (!$parsed || $parsed->format('Y-m-d') !== $incomeDate) {
        jsonResponse(false, 'Invalid income date format', [], 422);
    }

    if ($type === 'distributed' && $source !== 'external') {
        $totalPercentage = round($service->partnerPercentageTotal(), 2);
        if (abs($totalPercentage - 100.00) > 0.01) {
            jsonResponse(false, "Partner percentage total must be 100% before distributed income. Current total: {$totalPercentage}%", [], 422);
        }
    }

    $incomeId = $service->addIncome($amount, $type, $source, $incomeDate, $note);
    jsonResponse(true, 'Income added', ['income_id' => $incomeId]);
} catch (Throwable $e) {
    logError($e);
    jsonResponse(false, 'Failed to add income', [], 500);
}
