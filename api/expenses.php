<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

requireAuthJson();
$service = new FinanceService();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $range = normalizeFilterRange(sanitize((string) ($_GET['range'] ?? 'all')));
        $typeFilter = sanitize((string) ($_GET['type'] ?? 'all'));
        $page = max(1, inputInt($_GET, 'page'));
        $limit = inputInt($_GET, 'limit');
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }
        if (!in_array($typeFilter, ['all', 'company', 'partner'], true)) {
            $typeFilter = 'all';
        }
        [$start, $end] = parseFilterRange($range, null, null);
        $rows = $service->listExpenses($start, $end, $range, $typeFilter, $page, $limit);
        $summary = $service->expenseSummary($start, $end, $range);
        jsonResponse(true, 'Expenses loaded', ['expenses' => $rows, 'summary' => $summary]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Method not allowed', [], 405);
    }

    $amount = inputFloat($_POST, 'amount');
    $type = sanitize((string) ($_POST['type'] ?? 'company'));
    $partnerId = inputInt($_POST, 'partner_id');
    $paymentMode = sanitize((string) ($_POST['payment_mode'] ?? 'partner_pay'));
    $description = sanitize((string) ($_POST['detailed_note'] ?? ($_POST['description'] ?? '')));
    $expenseDate = sanitize((string) ($_POST['transaction_date'] ?? ''));

    if ($amount <= 0 || !in_array($type, ['company', 'partner'], true)) {
        jsonResponse(false, 'Invalid expense payload', [], 422);
    }
    if ($type === 'partner' && $partnerId < 1) {
        jsonResponse(false, 'Partner expense requires a partner', [], 422);
    }
    if ($type === 'partner' && !in_array($paymentMode, ['partner_pay', 'company_pay'], true)) {
        jsonResponse(false, 'Invalid partner payment mode', [], 422);
    }
    if ($type !== 'partner') {
        $paymentMode = 'partner_pay';
    }
    if ($expenseDate === '') {
        jsonResponse(false, 'Transaction date is required', [], 422);
    }
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $expenseDate);
    if (!$parsed || $parsed->format('Y-m-d') !== $expenseDate) {
        jsonResponse(false, 'Invalid expense date format', [], 422);
    }

    $expenseId = $service->addExpense(
        $amount,
        $type,
        $type === 'partner' ? $partnerId : null,
        $description,
        $expenseDate,
        $paymentMode
    );
    jsonResponse(true, 'Expense recorded', ['expense_id' => $expenseId]);
} catch (Throwable $e) {
    logError($e);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        jsonResponse(false, 'Failed to load expenses', [], 500);
    }
    jsonResponse(false, 'Failed to record expense', [], 500);
}
