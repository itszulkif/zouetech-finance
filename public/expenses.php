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
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex items-center justify-between gap-2 mb-3">
            <h3 class="font-semibold text-lg">Partner Remaining Balance (Receivable / Liability)</h3>
          </div>
          <div id="partnerBalanceCards" class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3"></div>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5 max-w-4xl">
          <h3 class="font-semibold text-lg mb-3">Record Expense</h3>
          <form id="expenseForm" class="grid md:grid-cols-2 gap-3">
            <label class="text-sm font-medium text-slate-600">Amount
              <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
            </label>
            <label class="text-sm font-medium text-slate-600">Expense Type
              <select name="type" id="expenseType" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
                <option value="company">Company Expense</option>
                <option value="partner">Partner Expense</option>
              </select>
            </label>
            <label id="partnerSelectWrap" class="text-sm font-medium text-slate-600 hidden">Partner
              <select name="partner_id" id="partnerSelect" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2"></select>
            </label>
            <label class="text-sm font-medium text-slate-600">Transaction Date
              <input name="transaction_date" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
            </label>
            <label class="text-sm font-medium text-slate-600 md:col-span-2">Detailed Note
              <input name="detailed_note" placeholder="Purpose / details" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
            </label>
            <div class="md:col-span-2 flex flex-wrap gap-2">
              <button class="bg-rose-600 text-white rounded-xl px-4 py-2 w-fit">Save Expense</button>
              <a href="/zou-finance/public/expenses-details.php" class="rounded-xl border border-slate-200 px-4 py-2 w-fit hover:bg-slate-50 inline-flex items-center gap-1">
                <span>Show Daily Expense Details</span>
              </a>
            </div>
          </form>
          <p class="mt-3 text-sm text-slate-500">Use "Out-of-Pocket" when partner spends first (liability). Use "Company Reimburses" when paying partner back (deducted from company balance).</p>
        </article>
        <section id="dailyExpensePanel" class="hidden space-y-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <div>
              <h3 class="font-semibold text-lg">Daily Expense Overview</h3>
              <p class="text-sm text-slate-500">See today expenses with type, date and full details.</p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
              <label class="text-sm text-slate-600">Range
                <select id="expenseRange" class="mt-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="daily" selected>Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="all">All Time</option>
                </select>
              </label>
              <label class="text-sm text-slate-600">Expense Type
                <select id="expenseTypeFilter" class="mt-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="all">All Types</option>
                  <option value="company">Company Expense</option>
                  <option value="partner">Partner Expense</option>
                </select>
              </label>
              <button id="expenseFilterApply" class="rounded-xl bg-blue-600 text-white px-4 py-2 text-sm">Apply</button>
            </div>
          </div>
          <div class="mb-3 flex flex-wrap gap-3 text-xs text-slate-600">
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-amber-50 border border-slate-200"></span>Company Expense</span>
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-rose-50 border border-slate-200"></span>Partner Out-of-Pocket</span>
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-indigo-50 border border-slate-200"></span>Company Reimbursement</span>
          </div>
          <div class="overflow-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="py-2 pr-3">Added At</th>
                  <th class="py-2 pr-3">Transaction Date</th>
                  <th class="py-2 pr-3">Expense Type</th>
                  <th class="py-2 pr-3">Partner</th>
                  <th class="py-2 pr-3">Details</th>
                  <th class="py-2 pr-3">Amount</th>
                </tr>
              </thead>
              <tbody id="expenseHistoryRows"></tbody>
            </table>
          </div>
          <div class="mt-3 flex gap-2">
            <button id="expensePrev" class="px-3 py-1.5 rounded-lg border text-sm">Prev</button>
            <button id="expenseNext" class="px-3 py-1.5 rounded-lg border text-sm">Next</button>
          </div>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h3 class="font-semibold text-lg">Partner Expense Details (Company Balance)</h3>
            <p class="text-sm text-slate-600">Total Partner Expense: <strong id="partnerExpenseTotal">PKR 0.00</strong></p>
          </div>
          <p class="text-sm text-slate-500 mb-3">Partner ke company-related expenses yahan clear dikhte hain: kab gaye, kis partner ne kiye, aur kis detail mein use huay.</p>
          <div class="overflow-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="py-2 pr-3">Added At</th>
                  <th class="py-2 pr-3">Transaction Date</th>
                  <th class="py-2 pr-3">Partner</th>
                  <th class="py-2 pr-3">Details</th>
                  <th class="py-2 pr-3">Amount</th>
                </tr>
              </thead>
              <tbody id="partnerExpenseRows"></tbody>
            </table>
          </div>
        </article>
        </section>
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="/zou-finance/public/dashboard.js"></script>
</body>
</html>
