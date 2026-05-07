<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'reports';
$pageTitle = 'Reports';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <h3 class="font-semibold text-lg mb-3">Report Filters</h3>
          <div class="grid md:grid-cols-4 gap-3">
            <select id="range" class="rounded-lg border border-slate-200 px-2 py-2 text-sm">
              <option value="all" selected>All Time</option>
              <option value="daily">Today</option>
              <option value="weekly">This Week</option>
              <option value="monthly">This Month</option>
              <option value="yearly">This Year</option>
              <option value="custom">Custom Range</option>
            </select>
            <input id="fromDate" type="date" class="rounded-lg border border-slate-200 px-2 py-2 text-sm hidden">
            <input id="toDate" type="date" class="rounded-lg border border-slate-200 px-2 py-2 text-sm hidden">
            <button id="reloadBtn" class="bg-blue-600 text-white rounded-lg px-3 py-2 text-sm">Refresh Reports</button>
          </div>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <h3 class="font-semibold text-lg mb-3">Income vs Expense</h3>
          <div id="reportBox" class="text-sm text-slate-700"></div>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <h3 class="font-semibold text-lg mb-3">Recent Transactions</h3>
          <div class="mb-3 flex flex-wrap gap-3 text-xs text-slate-600">
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-amber-50 border border-slate-200"></span>Company Expense</span>
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-rose-50 border border-slate-200"></span>Partner Out-of-Pocket</span>
            <span class="inline-flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-indigo-50 border border-slate-200"></span>Company Reimbursement</span>
          </div>
          <div class="overflow-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="py-2 pr-3">Date</th>
                  <th class="py-2 pr-3">Group</th>
                  <th class="py-2 pr-3">Type</th>
                  <th class="py-2 pr-3">Partner</th>
                  <th class="py-2 pr-3">Description</th>
                  <th class="py-2 pr-3">Amount</th>
                </tr>
              </thead>
              <tbody id="transactionRows"></tbody>
            </table>
          </div>
          <div class="mt-3 flex gap-2">
            <button id="txPrev" class="px-3 py-1.5 rounded-lg border text-sm">Prev</button>
            <button id="txNext" class="px-3 py-1.5 rounded-lg border text-sm">Next</button>
          </div>
        </article>
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="<?= htmlspecialchars(app_url('/public/dashboard.js')) ?>"></script>
</body>
</html>
