<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'expenses';
$pageTitle = 'Daily Expense Details';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daily Expense Details - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-5 flex items-center justify-between gap-3">
          <div>
            <h3 class="font-semibold text-lg">Daily Expense Details</h3>
            <p class="text-sm text-slate-500">Yahan per aap ko tamam expenses list ki form mein milenge, filters ke sath.</p>
          </div>
          <a href="<?= htmlspecialchars(app_url('/public/expenses.php')) ?>" class="text-sm rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50">
            Back to Expense Entry
          </a>
        </article>

        <section id="dailyExpensePanel" class="space-y-4">
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
              <button id="expenseExportBtn" class="rounded-xl border border-slate-200 px-4 py-2 text-sm hover:bg-slate-50">Export to Excel</button>
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
  <script>
    // Mark this page as dedicated daily expense details view
    window.__DAILY_EXPENSE_PAGE__ = true;
  </script>
  <script src="<?= htmlspecialchars(app_url('/public/dashboard.js')) ?>"></script>
</body>
</html>

