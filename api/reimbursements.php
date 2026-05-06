<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $range = normalizeFilterRange(sanitize((string) ($_GET['range'] ?? 'all')));
        [$start, $end] = parseFilterRange($range, $_GET['from'] ?? null, $_GET['to'] ?? null);
        $snapshot = $service->getPartnerLiabilities($range, $start, $end);
        jsonResponse(true, 'Partner liabilities loaded', $snapshot);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Method not allowed', [], 405);
    }

    $partnerId = inputInt($_POST, 'partner_id');
    $amount = inputFloat($_POST, 'amount');
    $transactionDate = sanitize((string) ($_POST['transaction_date'] ?? ''));
    $note = sanitize((string) ($_POST['note'] ?? ''));

    if ($partnerId < 1 || $amount <= 0) {
        jsonResponse(false, 'Invalid reimbursement payload', [], 422);
    }

    if ($transactionDate === '') {
        jsonResponse(false, 'Payment date is required', [], 422);
    }
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $transactionDate);
    if (!$parsed || $parsed->format('Y-m-d') !== $transactionDate) {
        jsonResponse(false, 'Invalid reimbursement date format', [], 422);
    }

    $expenseId = $service->recordPartnerReimbursement(
        $partnerId,
        $amount,
        $note,
        $transactionDate
    );

    jsonResponse(true, 'Reimbursement processed', ['expense_id' => $expenseId]);
} catch (RuntimeException $e) {
    jsonResponse(false, $e->getMessage(), [], 422);
} catch (Throwable $e) {
    logError($e);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        jsonResponse(false, 'Failed to load partner liabilities', [], 500);
    }
    jsonResponse(false, 'Failed to process reimbursement', [], 500);
}
