<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'expenses';
$pageTitle = 'Expense Tracker';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Expense Tracker - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800" data-page="expenses">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-5">
        <section class="grid md:grid-cols-3 gap-4">
          <article class="bg-white rounded-2xl border border-slate-200 p-4">
            <h3 class="font-semibold text-sm text-slate-700 mb-3">Partner Liabilities</h3>
            <div id="partnerBalanceCards" class="space-y-2 text-sm"></div>
          </article>
          <article class="bg-white rounded-2xl border border-slate-200 p-4">
            <h3 class="font-semibold text-sm text-slate-700 mb-2">Total Expenses</h3>
            <p id="totalExpenseValue" class="text-2xl font-bold text-rose-600">PKR 0.00</p>
          </article>
          <article class="bg-white rounded-2xl border border-slate-200 p-4">
            <h3 class="font-semibold text-sm text-slate-700 mb-2">Company Balance</h3>
            <p id="companyBalanceValue" class="text-2xl font-bold text-emerald-600">PKR 0.00</p>
          </article>
        </section>

        <section class="grid xl:grid-cols-4 gap-5">
          <article class="xl:col-span-3 bg-white rounded-2xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
              <h3 class="font-semibold text-base text-slate-800">Latest 20 Expense Records</h3>
              <button id="expenseExportBtn" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">Export to Excel</button>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="text-left border-b">
                    <th class="py-2 pr-3">Transaction Date</th>
                    <th class="py-2 pr-3">Detailed Note</th>
                    <th class="py-2 pr-3">Expense Type</th>
                    <th class="py-2 pr-3">Amount</th>
                  </tr>
                </thead>
                <tbody id="expenseHistoryRows"></tbody>
              </table>
            </div>
            <div class="mt-3 text-right">
              <a href="<?= htmlspecialchars(app_url('/public/expenses-details.php')) ?>" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50 inline-flex">View All</a>
            </div>
          </article>

          <article class="xl:col-span-1 bg-white rounded-2xl border border-slate-200 p-4">
            <h3 class="font-semibold text-lg mb-3">Record Expense</h3>
            <form id="expenseForm" class="space-y-3">
              <label class="text-sm font-medium text-slate-600 block">Amount
                <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
              </label>
              <label class="text-sm font-medium text-slate-600 block">Expense Type
                <select name="type" id="expenseType" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
                  <option value="company">Company Expense</option>
                  <option value="partner">Partner Expense</option>
                </select>
              </label>
              <label id="partnerSelectWrap" class="text-sm font-medium text-slate-600 hidden block">Partner
                <select name="partner_id" id="partnerSelect" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2"></select>
              </label>
              <label class="text-sm font-medium text-slate-600 block">Transaction Date
                <input name="transaction_date" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
              </label>
              <label class="text-sm font-medium text-slate-600 block">Detailed Note
                <input name="detailed_note" placeholder="Purpose / details" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
              </label>
              <button class="w-full bg-rose-600 text-white rounded-xl px-4 py-2">Save Expense</button>
            </form>
          </article>
        </section>
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="<?= htmlspecialchars(app_url('/public/dashboard.js')) ?>"></script>
</body>
</html>
