<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

final class FinanceService
{
    private const INCOME_TYPE_DISTRIBUTED = 'distributed';
    private const INCOME_TYPE_COMPANY_ONLY = 'company_only';
    private const INCOME_TYPE_EXTERNAL = 'external_source';
    private const INCOME_SOURCE_NORMAL = 'normal';
    private const INCOME_SOURCE_EXTERNAL = 'external';

    public function listPartners(int $page = 1, int $limit = 50): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $rows = [];
        $stmt = db()->prepare('SELECT id, name, percentage, created_at FROM partners ORDER BY id DESC LIMIT ? OFFSET ?');
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function partnerPercentageTotal(?int $excludePartnerId = null): float
    {
        if ($excludePartnerId) {
            $stmt = db()->prepare('SELECT COALESCE(SUM(percentage),0) AS total FROM partners WHERE id != ?');
            $stmt->bind_param('i', $excludePartnerId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            return (float) $res['total'];
        }

        $res = db()->query('SELECT COALESCE(SUM(percentage),0) AS total FROM partners')->fetch_assoc();
        return (float) $res['total'];
    }

    public function addPartner(string $name, float $percentage): int
    {
        $stmt = db()->prepare('INSERT INTO partners (name, percentage) VALUES (?, ?)');
        $stmt->bind_param('sd', $name, $percentage);
        $stmt->execute();
        return (int) db()->insert_id;
    }

    public function deletePartner(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM partners WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    public function addIncome(float $amount, string $type, string $source = self::INCOME_SOURCE_NORMAL, ?string $transactionDate = null, string $note = ''): int
    {
        if ($transactionDate === null || trim($transactionDate) === '') {
            throw new RuntimeException('Income transaction date is required.');
        }
        $conn = db();
        $conn->begin_transaction();
        try {
            $allocation = $this->buildIncomeAllocationPlan($amount, $type, $source);
            $incomeColumns = $this->getIncomeColumns($conn);
            $effectiveDate = trim($transactionDate);
            $trimmedNote = trim($note);

            $columns = ['amount', 'type'];
            $types = 'ds';
            $values = [$amount, $allocation['type']];

            if (in_array('source', $incomeColumns, true)) {
                $columns[] = 'source';
                $types .= 's';
                $values[] = $allocation['source'];
            }
            if (in_array('income_date', $incomeColumns, true)) {
                $columns[] = 'income_date';
                $types .= 's';
                $values[] = $effectiveDate;
            }
            if (in_array('transaction_date', $incomeColumns, true)) {
                $columns[] = 'transaction_date';
                $types .= 's';
                $values[] = $effectiveDate;
            }
            if (in_array('created_at', $incomeColumns, true)) {
                $columns[] = 'created_at';
                $types .= 's';
                $values[] = $effectiveDate . ' 00:00:00';
            }
            if (in_array('note', $incomeColumns, true)) {
                $columns[] = 'note';
                $types .= 's';
                $values[] = $trimmedNote;
            }

            $columnsSql = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $stmt = $conn->prepare("INSERT INTO income ({$columnsSql}) VALUES ({$placeholders})");
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $incomeId = (int) $conn->insert_id;

            if ($allocation['partner_pool'] > 0.0) {
                foreach ($allocation['distribution'] as $share) {
                    $partnerId = (int) $share['partner_id'];
                    $shareAmount = (float) $share['amount'];
                    $shareStmt = $conn->prepare('INSERT INTO partner_shares (partner_id, income_id, amount) VALUES (?, ?, ?)');
                    $shareStmt->bind_param('iid', $partnerId, $incomeId, $shareAmount);
                    $shareStmt->execute();

                    $this->appendLedgerEntry($conn, $partnerId, $shareAmount, 0.0, '', $effectiveDate);
                }
            }

            $conn->commit();
            return $incomeId;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function addExpense(
        float $amount,
        string $type,
        ?int $partnerId,
        string $description,
        ?string $transactionDate = null,
        string $paymentMode = 'partner_pay'
    ): int
    {
        if ($transactionDate === null || trim($transactionDate) === '') {
            throw new RuntimeException('Expense transaction date is required.');
        }
        $conn = db();
        $conn->begin_transaction();
        try {
            $expenseColumns = $this->getTableColumns($conn, 'expenses');
            $columns = ['amount', 'type', 'partner_id', 'description'];
            $types = 'dsis';
            $values = [$amount, $type, $partnerId, $description];
            if (in_array('transaction_date', $expenseColumns, true)) {
                $columns[] = 'transaction_date';
                $types .= 's';
                $values[] = trim($transactionDate);
            }
            if (in_array('created_at', $expenseColumns, true)) {
                $columns[] = 'created_at';
                $types .= 's';
                $values[] = trim($transactionDate) . ' 00:00:00';
            }
            if (in_array('payment_mode', $expenseColumns, true)) {
                $columns[] = 'payment_mode';
                $types .= 's';
                $values[] = $type === 'partner' ? $paymentMode : 'partner_pay';
            }
            $stmt = $conn->prepare('INSERT INTO expenses (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')');
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            $expenseId = (int) $conn->insert_id;

            $conn->commit();
            return $expenseId;
        } catch (Throwable $e) {
            $conn->rollback();
            throw $e;
        }
    }

    public function getPartnerLiabilities(string $filter = 'all', ?string $start = null, ?string $end = null): array
    {
        $rows = $this->partnerBreakdown($filter, $start, $end);
        $total = 0.0;
        foreach ($rows as &$row) {
            $liability = $this->roundMoney((float) ($row['remaining_balance'] ?? 0.0));
            $row['liability'] = $liability;
            $total += $liability;
        }
        unset($row);

        return [
            'total_liability' => $this->roundMoney($total),
            'partners' => $rows,
        ];
    }

    public function recordPartnerReimbursement(int $partnerId, float $amount, string $description = '', ?string $transactionDate = null): int
    {
        $liability = $this->partnerOutstandingLiability($partnerId);
        if ($liability <= 0.0) {
            throw new RuntimeException('No outstanding liability found for selected partner.');
        }
        if ($amount > $liability) {
            throw new RuntimeException('Reimbursement exceeds outstanding liability.');
        }

        $note = trim($description);
        if ($note === '') {
            $note = 'Partner reimbursement paid by company';
        }
        $note = 'REIMBURSEMENT: ' . $note;

        return $this->addExpense(
            $amount,
            'partner',
            $partnerId,
            $note,
            $transactionDate,
            'company_pay'
        );
    }

    public function dashboard(?string $start = null, ?string $end = null, string $filter = 'all'): array
    {
        $incomeDateExpr = $this->resolveDateExpression('income', ['transaction_date', 'income_date', 'created_at']);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at']);
        $incomeDateCondition = $this->buildDateFilterCondition($incomeDateExpr, $filter, $start, $end);
        $expenseDateCondition = $this->buildDateFilterCondition($expenseDateExpr, $filter, $start, $end);
        $whereIncome = $incomeDateCondition !== '' ? " WHERE {$incomeDateCondition}" : '';
        $whereExpenses = $expenseDateCondition !== '' ? " WHERE {$expenseDateCondition}" : '';
        $incomeTypes = $this->conditionNeedsBindings($incomeDateCondition) ? 'ss' : '';
        $incomeParams = $incomeTypes !== '' ? [$start, $end] : [];
        $expenseTypes = $this->conditionNeedsBindings($expenseDateCondition) ? 'ss' : '';
        $expenseParams = $expenseTypes !== '' ? [$start, $end] : [];

        $incomeTotal = $this->sumByTable('income', 'amount', $whereIncome, $incomeTypes, $incomeParams);
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseTotalExpr = $hasPaymentMode
            ? "COALESCE(SUM(CASE WHEN e.type = 'partner' AND (e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%') THEN 0 ELSE e.amount END),0)"
            : "COALESCE(SUM(CASE WHEN e.type = 'partner' AND e.description LIKE 'REIMBURSEMENT:%' THEN 0 ELSE e.amount END),0)";
        $expenseTotal = $this->sumBySql(
            "SELECT {$expenseTotalExpr} AS total FROM expenses e" . ($expenseDateCondition !== '' ? " WHERE {$expenseDateCondition}" : ''),
            $expenseTypes,
            $expenseParams
        );
        $net = $incomeTotal - $expenseTotal;

        $companyOnly = $this->sumBySql(
            "SELECT COALESCE(SUM(amount),0) AS total FROM income WHERE type IN ('company_only','external_source')" . ($incomeDateCondition !== '' ? " AND {$incomeDateCondition}" : ''),
            $incomeTypes,
            $incomeParams
        );
        $distributedCompanyHalf = $this->sumBySql(
            "SELECT COALESCE(SUM(amount * 0.5),0) AS total FROM income WHERE type = 'distributed'" . ($incomeDateCondition !== '' ? " AND {$incomeDateCondition}" : ''),
            $incomeTypes,
            $incomeParams
        );
        $reimbursementCondition = "type = 'partner' AND description LIKE 'REIMBURSEMENT:%'";
        $companyExpenseWhere = "(type = 'company' OR {$reimbursementCondition})";
        if ($hasPaymentMode) {
            $companyExpenseWhere = "(type = 'company' OR (type = 'partner' AND payment_mode = 'company_pay') OR {$reimbursementCondition})";
        }
        $companyExpenses = $this->sumBySql(
            "SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE {$companyExpenseWhere}" . ($expenseDateCondition !== '' ? " AND {$expenseDateCondition}" : ''),
            $expenseTypes,
            $expenseParams
        );
        $companyBalance = ($companyOnly + $distributedCompanyHalf) - $companyExpenses;

        $ledgerDateCondition = $this->buildDateFilterCondition('created_at', $filter, $start, $end);
        $ledgerTypes = $this->conditionNeedsBindings($ledgerDateCondition) ? 'ss' : '';
        $ledgerParams = $ledgerTypes !== '' ? [$start, $end] : [];
        $partnerLiabilities = $this->sumPartnerLiabilities($filter, $start, $end);

        return [
            'total_income' => round($incomeTotal, 2),
            'total_expenses' => round($expenseTotal, 2),
            'net_balance' => round($net, 2),
            'company_balance' => round($companyBalance, 2),
            'partner_liabilities' => round($partnerLiabilities, 2),
            'company_payable_to_partners' => round($partnerLiabilities, 2),
            'partner_breakdown' => $this->partnerBreakdown($filter, $start, $end)
        ];
    }

    public function partnerLedger(int $partnerId, ?string $start = null, ?string $end = null): array
    {
        $details = $this->partnerLedgerDetails($partnerId, $start, $end);
        $timeline = [];
        foreach (($details['timeline'] ?? []) as $entry) {
            $kind = (string) ($entry['kind'] ?? '');
            $amount = (float) ($entry['amount'] ?? 0);
            $credit = 0.0;
            $debit = 0.0;
            if ($kind === 'income') {
                $credit = $amount;
            } elseif ($kind === 'out_of_pocket') {
                $debit = $amount;
            }
            $timeline[] = [
                'credit' => round($credit, 2),
                'debit' => round($debit, 2),
                'note' => (string) ($entry['description'] ?? ''),
                'created_at' => (string) ($entry['transaction_date'] ?? ($entry['created_at'] ?? '')),
            ];
        }
        return [
            'summary' => [
                'total_share_received' => round((float) (($details['summary']['total_income_received'] ?? 0.0)), 2),
                'total_used' => round((float) (($details['summary']['total_out_of_pocket'] ?? 0.0)), 2),
                'remaining_balance' => round((float) (($details['summary']['remaining_balance'] ?? 0.0)), 2),
                'receivable_from_company' => round((float) (($details['summary']['outstanding_liability'] ?? 0.0)), 2),
            ],
            'timeline' => $timeline,
        ];
    }

    public function partnerLedgerDetails(int $partnerId, ?string $start = null, ?string $end = null): array
    {
        $conn = db();

        $incomeColumns = $this->getIncomeColumns($conn);
        $incomeDescriptionExpr = in_array('note', $incomeColumns, true) ? "COALESCE(i.note, '')" : 'NULL';
        $expenseColumns = $this->getTableColumns($conn, 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $paymentModeSelect = $hasPaymentMode ? "COALESCE(e.payment_mode, 'partner_pay')" : "'partner_pay'";

        $incomeDateExpr = $this->resolveDateExpression('income', ['transaction_date', 'income_date', 'created_at'], 'i');
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');

        $incomeWhereSql = '';
        $expenseWhereSql = '';
        $commonTypes = '';
        $commonParams = [];
        if ($start && $end) {
            $incomeWhereSql = ' AND ' . $this->buildDateRangeCondition($incomeDateExpr);
            $expenseWhereSql = ' AND ' . $this->buildDateRangeCondition($expenseDateExpr);
            $commonTypes = 'ss';
            $commonParams = [$start, $end];
        }

        $incomeSql = "
            SELECT
                DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT({$incomeDateExpr}, '%Y-%m-%d') AS transaction_date,
                {$incomeDescriptionExpr} AS description,
                ps.amount AS amount
            FROM partner_shares ps
            JOIN income i ON i.id = ps.income_id
            WHERE ps.partner_id = ?
            {$incomeWhereSql}
            ORDER BY i.created_at DESC
        ";
        $incomeStmt = $conn->prepare($incomeSql);
        $incomeTypes = 'i' . $commonTypes;
        $incomeParams = array_merge([$partnerId], $commonParams);
        if ($incomeTypes !== '') {
            $incomeStmt->bind_param($incomeTypes, ...$incomeParams);
        }
        $incomeStmt->execute();
        $incomeRes = $incomeStmt->get_result();

        $incomeEntries = [];
        $totalIncome = 0.0;
        while ($r = $incomeRes->fetch_assoc()) {
            $amount = (float) ($r['amount'] ?? 0);
            $totalIncome += $amount;
            $incomeEntries[] = [
                'transaction_date' => $r['transaction_date'] ?? null,
                'created_at' => $r['created_at'] ?? null,
                'description' => $r['description'] ?? '',
                'amount' => round($amount, 2),
            ];
        }

        $expenseSql = "
            SELECT
                e.id,
                DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT({$expenseDateExpr}, '%Y-%m-%d') AS transaction_date,
                COALESCE(e.description, '') AS description,
                {$paymentModeSelect} AS payment_mode,
                e.amount AS amount
            FROM expenses e
            WHERE e.type = 'partner'
              AND e.partner_id = ?
              {$expenseWhereSql}
            ORDER BY e.created_at DESC
        ";
        $expenseStmt = $conn->prepare($expenseSql);
        $expenseTypes = 'i' . $commonTypes;
        $expenseParams = array_merge([$partnerId], $commonParams);
        if ($expenseTypes !== '') {
            $expenseStmt->bind_param($expenseTypes, ...$expenseParams);
        }
        $expenseStmt->execute();
        $expenseRes = $expenseStmt->get_result();

        $outOfPocketEntries = [];
        $reimbursementEntries = [];
        while ($r = $expenseRes->fetch_assoc()) {
            $amount = (float) ($r['amount'] ?? 0);
            $description = (string) ($r['description'] ?? '');
            $paymentMode = (string) ($r['payment_mode'] ?? 'partner_pay');
            $isReimbursement = $paymentMode === 'company_pay' || str_starts_with($description, 'REIMBURSEMENT:');
            if ($isReimbursement) {
                $reimbursementEntries[] = [
                    'id' => (int) ($r['id'] ?? 0),
                    'transaction_date' => $r['transaction_date'] ?? null,
                    'created_at' => $r['created_at'] ?? null,
                    'description' => trim(preg_replace('/^REIMBURSEMENT:\s*/', '', $description)) ?: 'Reimbursement paid by company',
                    'amount' => $this->roundMoney($amount),
                ];
                continue;
            }
            $outOfPocketEntries[] = [
                'id' => (int) ($r['id'] ?? 0),
                'transaction_date' => $r['transaction_date'] ?? null,
                'created_at' => $r['created_at'] ?? null,
                'description' => $description,
                'amount' => $this->roundMoney($amount),
            ];
        }

        usort($outOfPocketEntries, static function (array $a, array $b): int {
            return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
        });
        usort($reimbursementEntries, static function (array $a, array $b): int {
            return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? ''));
        });

        $reimbursementPool = 0.0;
        foreach ($reimbursementEntries as $entry) {
            $reimbursementPool += (float) ($entry['amount'] ?? 0.0);
        }

        $purchaseEntries = [];
        $totalOutOfPocket = 0.0;
        $totalCleared = 0.0;
        foreach ($outOfPocketEntries as $entry) {
            $expenseAmount = (float) ($entry['amount'] ?? 0.0);
            $totalOutOfPocket += $expenseAmount;

            $clearedAmount = min($expenseAmount, $reimbursementPool);
            $reimbursementPool = max(0.0, $reimbursementPool - $clearedAmount);
            $pendingAmount = max(0.0, $expenseAmount - $clearedAmount);
            $status = $pendingAmount > 0.0 ? ($clearedAmount > 0.0 ? 'Partially Cleared' : 'Outstanding') : 'Cleared';
            $totalCleared += $clearedAmount;

            $purchaseEntries[] = [
                'transaction_date' => $entry['transaction_date'],
                'created_at' => $entry['created_at'],
                'description' => $entry['description'],
                'amount' => $this->roundMoney($expenseAmount),
                'status' => $status,
                'cleared_amount' => $this->roundMoney($clearedAmount),
                'outstanding_amount' => $this->roundMoney($pendingAmount),
            ];
        }

        $totalReimbursed = 0.0;
        foreach ($reimbursementEntries as $entry) {
            $totalReimbursed += (float) ($entry['amount'] ?? 0.0);
        }

        $remainingProfitBalance = $totalIncome;
        $outstandingLiability = max(0.0, $totalOutOfPocket - $totalReimbursed);

        $timeline = [];
        foreach ($incomeEntries as $it) {
            $timeline[] = [
                'kind' => 'income',
                'created_at' => $it['created_at'],
                'transaction_date' => $it['transaction_date'],
                'description' => $it['description'],
                'credit' => $it['amount'],
                'debit' => 0.0,
                'amount' => $it['amount'],
            ];
        }
        foreach ($purchaseEntries as $it) {
            $timeline[] = [
                'kind' => 'out_of_pocket',
                'created_at' => $it['created_at'],
                'transaction_date' => $it['transaction_date'],
                'description' => $it['description'],
                'credit' => 0.0,
                'debit' => $it['amount'],
                'amount' => $it['amount'],
            ];
        }
        foreach ($reimbursementEntries as $it) {
            $timeline[] = [
                'kind' => 'reimbursement',
                'created_at' => $it['created_at'],
                'transaction_date' => $it['transaction_date'],
                'description' => $it['description'],
                'credit' => 0.0,
                'debit' => -$it['amount'],
                'amount' => $it['amount'],
            ];
        }

        usort($timeline, function (array $a, array $b): int {
            $aT = (string) ($a['created_at'] ?? '');
            $bT = (string) ($b['created_at'] ?? '');
            return strcmp($bT, $aT);
        });

        return [
            'summary' => [
                'total_income_received' => round($totalIncome, 2),
                'total_out_of_pocket' => round($totalOutOfPocket, 2),
                'total_reimbursed' => round($totalReimbursed, 2),
                'total_cleared_expenses' => round($totalCleared, 2),
                'outstanding_liability' => round($outstandingLiability, 2),
                // Backward-compatible keys used by older UI widgets.
                'total_purchases' => round($totalOutOfPocket, 2),
                'remaining_balance' => round($remainingProfitBalance, 2),
                'receivable_from_company' => round($outstandingLiability, 2),
            ],
            'income' => $incomeEntries,
            'purchases' => $purchaseEntries,
            'reimbursements' => $reimbursementEntries,
            'timeline' => $timeline,
        ];
    }

    public function reports(?int $partnerId, ?string $start = null, ?string $end = null): array
    {
        $incomeDateExpr = $this->resolveDateExpression('income', ['transaction_date', 'income_date', 'created_at']);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at']);
        $whereDate = '';
        $expenseWhereDate = '';
        $types = '';
        $params = [];
        if ($start && $end) {
            $whereDate = " AND " . $this->buildDateRangeCondition($incomeDateExpr);
            $expenseWhereDate = " AND " . $this->buildDateRangeCondition($expenseDateExpr);
            $types .= 'ss';
            $params[] = $start;
            $params[] = $end;
        }

        $income = $this->sumBySql("SELECT COALESCE(SUM(amount),0) AS total FROM income WHERE 1=1{$whereDate}", $types, $params);
        $expenses = $this->sumBySql("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE 1=1{$expenseWhereDate}", $types, $params);

        $partnerQuery = "
            SELECT p.id, p.name, COALESCE(SUM(ps.amount),0) AS total
            FROM partners p
            LEFT JOIN partner_shares ps ON ps.partner_id = p.id
            LEFT JOIN income i ON i.id = ps.income_id
            WHERE 1=1
        ";
        $pTypes = '';
        $pParams = [];
        if ($partnerId) {
            $partnerQuery .= ' AND p.id = ?';
            $pTypes .= 'i';
            $pParams[] = $partnerId;
        }
        if ($start && $end) {
            $partnerQuery .= " AND " . $this->buildDateRangeCondition($incomeDateExpr);
            $pTypes .= 'ss';
            $pParams[] = $start;
            $pParams[] = $end;
        }
        $partnerQuery .= ' GROUP BY p.id, p.name ORDER BY total DESC';

        $stmt = db()->prepare($partnerQuery);
        if ($pTypes !== '') {
            $stmt->bind_param($pTypes, ...$pParams);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $distribution = [];
        while ($r = $res->fetch_assoc()) {
            $distribution[] = $r;
        }

        return [
            'income_vs_expense' => [
                'income' => round($income, 2),
                'expense' => round($expenses, 2)
            ],
            'partner_distribution' => $distribution
        ];
    }

    public function listExpenses(?string $start = null, ?string $end = null, string $range = 'all', string $type = 'all', int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $dateCondition = $this->buildDateFilterCondition($expenseDateExpr, $range, $start, $end);

        $conditions = [];
        $types = '';
        $params = [];

        if ($dateCondition !== '') {
            $conditions[] = $dateCondition;
            if ($this->conditionNeedsBindings($dateCondition)) {
                $types .= 'ss';
                $params[] = $start;
                $params[] = $end;
            }
        }

        if (in_array($type, ['company', 'partner'], true)) {
            $conditions[] = 'e.type = ?';
            $types .= 's';
            $params[] = $type;
        }

        $whereClause = $conditions === [] ? '' : (' WHERE ' . implode(' AND ', $conditions));

        $sql = "
            SELECT
                e.id,
                DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT({$expenseDateExpr}, '%Y-%m-%d') AS transaction_date,
                e.type,
                e.description,
                " . ($hasPaymentMode ? "e.payment_mode AS payment_mode" : "'partner_pay' AS payment_mode") . ",
                e.amount,
                p.name AS partner_name
            FROM expenses e
            LEFT JOIN partners p ON p.id = e.partner_id
            {$whereClause}
            ORDER BY e.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = db()->prepare($sql);
        $bindTypes = $types . 'ii';
        $bindParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function expenseSummary(?string $start = null, ?string $end = null, string $range = 'all'): array
    {
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $dateCondition = $this->buildDateFilterCondition($expenseDateExpr, $range, $start, $end);

        $whereClause = $dateCondition !== '' ? " WHERE {$dateCondition}" : '';
        $types = '';
        $params = [];
        if ($dateCondition !== '' && $this->conditionNeedsBindings($dateCondition)) {
            $types = 'ss';
            $params = [$start, $end];
        }

        $companyTotalExpr = $hasPaymentMode
            ? "COALESCE(SUM(CASE WHEN e.type = 'company' OR e.payment_mode = 'company_pay' OR (e.type = 'partner' AND e.description LIKE 'REIMBURSEMENT:%') THEN e.amount ELSE 0 END),0)"
            : "COALESCE(SUM(CASE WHEN e.type = 'company' OR (e.type = 'partner' AND e.description LIKE 'REIMBURSEMENT:%') THEN e.amount ELSE 0 END),0)";
        $sql = "
            SELECT
                {$companyTotalExpr} AS company_total,
                COALESCE(SUM(CASE WHEN e.type = 'partner' THEN e.amount ELSE 0 END),0) AS partner_total,
                COALESCE(SUM(e.amount),0) AS overall_total
            FROM expenses e
            {$whereClause}
        ";

        $stmt = db()->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return [
            'company_total' => round((float) ($row['company_total'] ?? 0), 2),
            'partner_total' => round((float) ($row['partner_total'] ?? 0), 2),
            'overall_total' => round((float) ($row['overall_total'] ?? 0), 2),
        ];
    }

    public function listIncomeDetails(?string $start = null, ?string $end = null, string $range = 'all', int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $incomeColumns = $this->getIncomeColumns(db());
        $incomeDateExpr = $this->resolveDateExpression('income', ['transaction_date', 'income_date', 'created_at'], 'i');
        $hasSource = in_array('source', $incomeColumns, true);
        $hasNote = in_array('note', $incomeColumns, true);
        $dateCondition = $this->buildDateFilterCondition($incomeDateExpr, $range, $start, $end);

        $whereClause = $dateCondition !== '' ? " WHERE {$dateCondition}" : '';
        $types = '';
        $params = [];
        if ($dateCondition !== '' && $this->conditionNeedsBindings($dateCondition)) {
            $types = 'ss';
            $params = [$start, $end];
        }

        $sql = "
            SELECT
                i.id,
                DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT({$incomeDateExpr}, '%Y-%m-%d') AS transaction_date,
                i.type,
                " . ($hasSource ? "i.source" : "'normal'") . " AS source,
                " . ($hasNote ? "COALESCE(i.note, '')" : "''") . " AS note,
                i.amount
            FROM income i
            {$whereClause}
            ORDER BY i.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = db()->prepare($sql);
        $bindTypes = $types . 'ii';
        $bindParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function incomeDetailsSummary(?string $start = null, ?string $end = null, string $range = 'all'): array
    {
        $incomeDateExpr = $this->resolveDateExpression('income', ['transaction_date', 'income_date', 'created_at'], 'i');
        $dateCondition = $this->buildDateFilterCondition($incomeDateExpr, $range, $start, $end);
        $whereClause = $dateCondition !== '' ? " WHERE {$dateCondition}" : '';
        $types = '';
        $params = [];
        if ($dateCondition !== '' && $this->conditionNeedsBindings($dateCondition)) {
            $types = 'ss';
            $params = [$start, $end];
        }

        $sql = "
            SELECT
                COALESCE(SUM(i.amount),0) AS overall_total,
                COALESCE(SUM(CASE WHEN i.type = 'distributed' THEN i.amount ELSE 0 END),0) AS distributed_total,
                COALESCE(SUM(CASE WHEN i.type IN ('company_only', 'external_source') THEN i.amount ELSE 0 END),0) AS company_total
            FROM income i
            {$whereClause}
        ";
        $stmt = db()->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        return [
            'overall_total' => round((float) ($row['overall_total'] ?? 0), 2),
            'distributed_total' => round((float) ($row['distributed_total'] ?? 0), 2),
            'company_total' => round((float) ($row['company_total'] ?? 0), 2),
        ];
    }

    public function listReimbursements(?string $start = null, ?string $end = null, string $range = 'all', int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $dateCondition = $this->buildDateFilterCondition($expenseDateExpr, $range, $start, $end);
        $reimburseCondition = $hasPaymentMode
            ? "(e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%')"
            : "(e.description LIKE 'REIMBURSEMENT:%')";

        $whereParts = ["e.type = 'partner'", $reimburseCondition];
        $types = '';
        $params = [];
        if ($dateCondition !== '') {
            $whereParts[] = $dateCondition;
            if ($this->conditionNeedsBindings($dateCondition)) {
                $types = 'ss';
                $params = [$start, $end];
            }
        }
        $whereClause = ' WHERE ' . implode(' AND ', $whereParts);

        $sql = "
            SELECT
                e.id,
                DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT({$expenseDateExpr}, '%Y-%m-%d') AS transaction_date,
                p.name AS partner_name,
                TRIM(
                    CASE
                        WHEN e.description LIKE 'REIMBURSEMENT:%' THEN SUBSTRING(e.description, LENGTH('REIMBURSEMENT:') + 1)
                        ELSE COALESCE(e.description, '')
                    END
                ) AS note,
                e.amount
            FROM expenses e
            LEFT JOIN partners p ON p.id = e.partner_id
            {$whereClause}
            ORDER BY e.id DESC
            LIMIT ? OFFSET ?
        ";
        $stmt = db()->prepare($sql);
        $bindTypes = $types . 'ii';
        $bindParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function reimbursementSummary(?string $start = null, ?string $end = null, string $range = 'all'): array
    {
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $dateCondition = $this->buildDateFilterCondition($expenseDateExpr, $range, $start, $end);
        $reimburseCondition = $hasPaymentMode
            ? "(e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%')"
            : "(e.description LIKE 'REIMBURSEMENT:%')";

        $whereParts = ["e.type = 'partner'", $reimburseCondition];
        $types = '';
        $params = [];
        if ($dateCondition !== '') {
            $whereParts[] = $dateCondition;
            if ($this->conditionNeedsBindings($dateCondition)) {
                $types = 'ss';
                $params = [$start, $end];
            }
        }
        $whereClause = ' WHERE ' . implode(' AND ', $whereParts);

        $stmt = db()->prepare("SELECT COALESCE(SUM(e.amount),0) AS total FROM expenses e {$whereClause}");
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return [
            'total_reimbursed' => round((float) ($row['total'] ?? 0), 2),
        ];
    }

    public function recentTransactions(?string $start = null, ?string $end = null, int $page = 1, int $limit = 20): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $incomeColumns = $this->getIncomeColumns(db());
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $incomeDateExpr = $this->resolveDateExpression('income', ['transaction_date', 'income_date', 'created_at'], 'i');
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $incomeDescriptionExpr = in_array('note', $incomeColumns, true) ? "COALESCE(i.note, '')" : "NULL";
        $partnerExpenseCompanyPayCondition = $hasPaymentMode
            ? "(e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%')"
            : "e.description LIKE 'REIMBURSEMENT:%'";
        $dateFilterIncome = '';
        $dateFilterExpense = '';
        $types = '';
        $params = [];
        if ($start && $end) {
            $dateFilterIncome = " WHERE " . $this->buildDateRangeCondition($incomeDateExpr);
            $dateFilterExpense = " WHERE " . $this->buildDateRangeCondition($expenseDateExpr);
            $types = 'ss';
            $params = [$start, $end];
        }

        $sql = "
            SELECT * FROM (
                SELECT
                    i.id,
                    DATE_FORMAT(i.created_at, '%Y-%m-%d %H:%i:%s') AS recorded_at,
                    DATE_FORMAT({$incomeDateExpr}, '%Y-%m-%d') AS transaction_date,
                    'income' AS tx_group,
                    i.type AS tx_type,
                    i.amount,
                    NULL AS partner_name,
                    {$incomeDescriptionExpr} AS description,
                    'Inflow to business pool' AS impact_note
                FROM income i {$dateFilterIncome}
                UNION ALL
                SELECT
                    e.id,
                    DATE_FORMAT(e.created_at, '%Y-%m-%d %H:%i:%s') AS recorded_at,
                    DATE_FORMAT({$expenseDateExpr}, '%Y-%m-%d') AS transaction_date,
                    'expense' AS tx_group,
                    e.type AS tx_type,
                    e.amount,
                    p.name AS partner_name,
                    e.description,
                    CASE
                        WHEN e.type = 'partner' AND {$partnerExpenseCompanyPayCondition} THEN 'Partner expense paid by company balance'
                        WHEN e.type = 'partner' THEN 'Partner spent for company; may create payable'
                        ELSE 'Direct company expense'
                    END AS impact_note
                FROM expenses e
                LEFT JOIN partners p ON p.id = e.partner_id
                {$dateFilterExpense}
            ) t
            ORDER BY t.recorded_at DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = db()->prepare($sql);
        if ($types !== '') {
            $bindTypes = $types . $types . 'ii';
            $bindParams = array_merge($params, $params, [$limit, $offset]);
            $stmt->bind_param($bindTypes, ...$bindParams);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function incomePreview(float $amount, string $type): array
    {
        return $this->buildIncomeAllocationPlan($amount, $type, self::INCOME_SOURCE_NORMAL);
    }

    public function buildIncomeAllocationPlan(float $amount, string $type, string $source = self::INCOME_SOURCE_NORMAL): array
    {
        $normalizedSource = $this->normalizeIncomeSource($type, $source);
        $normalizedType = $this->normalizeIncomeType($type, $normalizedSource);

        $companyShare = $amount;
        $partnerPool = 0.0;
        $distribution = [];
        if ($normalizedSource === self::INCOME_SOURCE_NORMAL && $normalizedType === self::INCOME_TYPE_DISTRIBUTED) {
            $companyShare = $this->roundMoney($amount * 0.5);
            $partnerPool = $this->roundMoney($amount * 0.5);
            $partners = $this->listPartners(1, 1000);
            foreach ($partners as $partner) {
                $distribution[] = [
                    'partner_id' => (int) $partner['id'],
                    'name' => $partner['name'],
                    'percentage' => (float) $partner['percentage'],
                    'amount' => $this->roundMoney($partnerPool * (((float) $partner['percentage']) / 100))
                ];
            }
        }

        return [
            'type' => $normalizedType,
            'source' => $normalizedSource,
            'company_share' => $this->roundMoney($companyShare),
            'partner_pool' => $this->roundMoney($partnerPool),
            'distribution' => $distribution
        ];
    }

    private function partnerBreakdown(string $filter = 'all', ?string $start = null, ?string $end = null): array
    {
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $dateCondition = $this->buildDateFilterCondition($expenseDateExpr, $filter, $start, $end);
        $types = '';
        $params = [];
        $dateSql = '';
        if ($dateCondition !== '') {
            $dateSql = " AND {$dateCondition}";
            if ($this->conditionNeedsBindings($dateCondition)) {
                $types = 'ss';
                $params = [$start, $end];
            }
        }

        $sql = "
            SELECT
                p.id,
                p.name,
                0.00 AS share_received,
                0.00 AS used_amount,
                COALESCE(SUM(
                    CASE
                        WHEN e.type = 'partner' THEN
                            " . ($hasPaymentMode
                                ? "CASE WHEN e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%' THEN -e.amount ELSE e.amount END"
                                : "CASE WHEN e.description LIKE 'REIMBURSEMENT:%' THEN -e.amount ELSE e.amount END") . "
                        ELSE 0
                    END
                ),0) AS remaining_balance
            FROM partners p
            LEFT JOIN expenses e ON e.partner_id = p.id {$dateSql}
            GROUP BY p.id, p.name
            ORDER BY p.name ASC
        ";

        $stmt = db()->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $row['remaining_balance'] = max(0.0, (float) ($row['remaining_balance'] ?? 0.0));
            $rows[] = $row;
        }
        return $rows;
    }

    private function sumByTable(string $table, string $field, string $where = '', string $types = '', array $params = []): float
    {
        $sql = "SELECT COALESCE(SUM($field),0) AS total FROM $table" . $where;
        return $this->sumBySql($sql, $types, $params);
    }

    private function sumBySql(string $sql, string $types = '', array $params = []): float
    {
        $stmt = db()->prepare($sql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float) ($row['total'] ?? 0);
    }

    private function appendLedgerEntry(mysqli $conn, int $partnerId, float $credit, float $debit, string $note = '', ?string $entryDate = null): void
    {
        $entryDateTime = ($entryDate !== null && trim($entryDate) !== '') ? (trim($entryDate) . ' 00:00:00') : date('Y-m-d H:i:s');
        $balanceStmt = $conn->prepare('SELECT COALESCE(balance,0) AS balance FROM ledger WHERE partner_id = ? ORDER BY id DESC LIMIT 1');
        $balanceStmt->bind_param('i', $partnerId);
        $balanceStmt->execute();
        $last = $balanceStmt->get_result()->fetch_assoc();
        $lastBalance = (float) ($last['balance'] ?? 0.0);
        $newBalance = $lastBalance + $credit - $debit;

        $ledgerColumns = $this->getTableColumns($conn, 'ledger');
        $columns = ['partner_id', 'credit', 'debit', 'balance'];
        $types = 'iddd';
        $values = [$partnerId, $credit, $debit, $newBalance];
        if (in_array('note', $ledgerColumns, true)) {
            $columns[] = 'note';
            $types .= 's';
            $values[] = $note;
        }
        if (in_array('created_at', $ledgerColumns, true)) {
            $columns[] = 'created_at';
            $types .= 's';
            $values[] = $entryDateTime;
        }
        $ledgerStmt = $conn->prepare(
            'INSERT INTO ledger (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')'
        );
        $ledgerStmt->bind_param($types, ...$values);
        $ledgerStmt->execute();
    }

    private function normalizeIncomeSource(string $type, string $source): string
    {
        if ($type === self::INCOME_TYPE_EXTERNAL || $source === self::INCOME_SOURCE_EXTERNAL) {
            return self::INCOME_SOURCE_EXTERNAL;
        }
        return self::INCOME_SOURCE_NORMAL;
    }

    private function normalizeIncomeType(string $type, string $normalizedSource): string
    {
        if ($normalizedSource === self::INCOME_SOURCE_EXTERNAL) {
            return self::INCOME_TYPE_EXTERNAL;
        }
        if ($type === self::INCOME_TYPE_COMPANY_ONLY) {
            return self::INCOME_TYPE_COMPANY_ONLY;
        }
        return self::INCOME_TYPE_DISTRIBUTED;
    }

    private function sumPartnerLiabilities(string $filter = 'all', ?string $start = null, ?string $end = null): float
    {
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $expenseDateExpr = $this->resolveDateExpression('expenses', ['transaction_date', 'created_at'], 'e');
        $dateCondition = $this->buildDateFilterCondition($expenseDateExpr, $filter, $start, $end);

        $totalExpr = $hasPaymentMode
            ? "COALESCE(SUM(CASE WHEN e.type = 'partner' AND (e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%') THEN -e.amount WHEN e.type = 'partner' THEN e.amount ELSE 0 END),0)"
            : "COALESCE(SUM(CASE WHEN e.type = 'partner' AND e.description LIKE 'REIMBURSEMENT:%' THEN -e.amount WHEN e.type = 'partner' THEN e.amount ELSE 0 END),0)";

        $whereSql = "1=1";
        if ($dateCondition !== '') {
            $whereSql .= " AND {$dateCondition}";
        }

        $types = '';
        $params = [];
        if ($dateCondition !== '' && $this->conditionNeedsBindings($dateCondition)) {
            $types = 'ss';
            $params = [$start, $end];
        }

        $total = $this->sumBySql("SELECT {$totalExpr} AS total FROM expenses e WHERE {$whereSql}", $types, $params);
        return max(0.0, $total);
    }

    private function partnerOutstandingLiability(int $partnerId): float
    {
        $expenseColumns = $this->getTableColumns(db(), 'expenses');
        $hasPaymentMode = in_array('payment_mode', $expenseColumns, true);
        $totalExpr = $hasPaymentMode
            ? "COALESCE(SUM(CASE WHEN e.type = 'partner' AND (e.payment_mode = 'company_pay' OR e.description LIKE 'REIMBURSEMENT:%') THEN -e.amount WHEN e.type = 'partner' THEN e.amount ELSE 0 END),0)"
            : "COALESCE(SUM(CASE WHEN e.type = 'partner' AND e.description LIKE 'REIMBURSEMENT:%' THEN -e.amount WHEN e.type = 'partner' THEN e.amount ELSE 0 END),0)";
        $stmt = db()->prepare("SELECT {$totalExpr} AS total FROM expenses e WHERE e.partner_id = ?");
        $stmt->bind_param('i', $partnerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return max(0.0, (float) ($row['total'] ?? 0.0));
    }

    private function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    private function getIncomeColumns(mysqli $conn): array
    {
        return $this->getTableColumns($conn, 'income');
    }

    private function getTableColumns(mysqli $conn, string $table): array
    {
        $columns = [];
        $result = $conn->query("SHOW COLUMNS FROM {$table}");
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $columns[] = (string) ($row['Field'] ?? '');
            }
        }
        return $columns;
    }

    private function resolveDateExpression(string $table, array $priority, ?string $tableAlias = null): string
    {
        $columns = $this->getTableColumns(db(), $table);
        $resolved = [];
        foreach ($priority as $column) {
            if (in_array($column, $columns, true)) {
                $qualified = $tableAlias ? "{$tableAlias}.{$column}" : $column;
                if (str_contains($column, 'date')) {
                    $resolved[] = "NULLIF({$qualified}, '0000-00-00')";
                    continue;
                }
                $resolved[] = "NULLIF({$qualified}, '0000-00-00 00:00:00')";
            }
        }
        if ($resolved === []) {
            return $tableAlias ? "{$tableAlias}.created_at" : 'created_at';
        }
        if (count($resolved) === 1) {
            return $resolved[0];
        }
        return 'COALESCE(' . implode(', ', $resolved) . ')';
    }

    private function buildDateRangeCondition(string $column, ?string $tableAlias = null): string
    {
        $qualifiedColumn = $tableAlias ? "{$tableAlias}.{$column}" : $column;
        return "DATE({$qualifiedColumn}) BETWEEN DATE(?) AND DATE(?)";
    }

    private function buildDateFilterCondition(string $columnExpr, string $filter, ?string &$start = null, ?string &$end = null): string
    {
        $normalizedFilter = strtolower(trim($filter));
        switch ($normalizedFilter) {
            case 'daily':
                $start = date('Y-m-d 00:00:00');
                $end = date('Y-m-d 23:59:59');
                return "DATE({$columnExpr}) = CURDATE()";
            case 'weekly':
                $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                return "YEARWEEK({$columnExpr}, 1) = YEARWEEK(CURDATE(), 1)";
            case 'monthly':
                $start = date('Y-m-01 00:00:00');
                $end = date('Y-m-t 23:59:59');
                return "MONTH({$columnExpr}) = MONTH(CURDATE()) AND YEAR({$columnExpr}) = YEAR(CURDATE())";
            case 'all':
                $start = null;
                $end = null;
                return '';
            default:
                if ($start && $end) {
                    return $this->buildDateRangeCondition($columnExpr);
                }
                $start = null;
                $end = null;
                return '';
        }
    }

    private function conditionNeedsBindings(string $condition): bool
    {
        return str_contains($condition, '?');
    }
}
