<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'dashboard';
$pageTitle = 'Dashboard';
$allowedFilters = ['all', 'daily', 'weekly', 'monthly', 'yearly'];
$filter = strtolower((string) ($_GET['range'] ?? 'all'));
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zouetech Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-slate-50 text-slate-800" data-page="dashboard">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-5">
        <article class="bg-white rounded-2xl border border-slate-200 p-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold">Project Overview</h2>
              <p class="text-sm text-slate-500">Track finance, reports, and partner balances from one dashboard.</p>
            </div>
            <form method="GET" action="dashboard.php" class="flex flex-wrap items-center gap-2">
              <label class="text-sm text-slate-600" for="range">Date Range</label>
              <select id="range" name="range" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All Time</option>
                <option value="daily" <?= $filter === 'daily' ? 'selected' : '' ?>>Daily</option>
                <option value="weekly" <?= $filter === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
              </select>
              <input id="fromDate" type="date" class="hidden rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
              <input id="toDate" type="date" class="hidden rounded-lg border border-slate-200 px-3 py-1.5 text-sm" />
              <button id="reloadBtn" type="submit" class="bg-blue-600 text-white rounded-lg px-3 py-1.5 text-sm">Apply</button>
            </form>
          </div>
        </article>

        <section class="grid sm:grid-cols-2 xl:grid-cols-5 gap-3" id="kpis"></section>

        <section class="grid xl:grid-cols-3 gap-5">
          <article class="bg-white rounded-2xl border border-slate-200 p-4 xl:col-span-2">
            <h3 class="font-semibold mb-3">Income vs Expense Trend</h3>
            <canvas id="kpiChart" height="120"></canvas>
          </article>
          <article class="bg-white rounded-2xl border border-slate-200 p-4 space-y-4">
            <div>
              <h3 class="font-semibold mb-2">Quick Actions</h3>
              <div class="space-y-2 text-sm">
                <a class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50" href="/zou-finance/public/income.php">Add Income</a>
                <a class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50" href="/zou-finance/public/expenses.php">Record Expense</a>
                <a class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50" href="/zou-finance/public/partners.php">Manage Partners</a>
                <a class="block rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50" href="/zou-finance/public/reports.php">Open Full Reports</a>
              </div>
            </div>
          </article>
        </section>

        <section class="grid lg:grid-cols-2 gap-5">
          <article class="bg-white rounded-2xl border border-slate-200 p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
              <h3 class="font-semibold">Today's Recent Transactions</h3>
              <a href="/zou-finance/public/transactions.php" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm hover:bg-slate-50">View All</a>
            </div>
            <div class="overflow-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="text-left border-b">
                    <th class="py-2 pr-3">Recorded At</th>
                    <th class="py-2 pr-3">Transaction Date</th>
                    <th class="py-2 pr-3">Group</th>
                    <th class="py-2 pr-3">Type</th>
                    <th class="py-2 pr-3">Partner</th>
                    <th class="py-2 pr-3">Description</th>
                    <th class="py-2 pr-3">Impact</th>
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

          <article class="bg-white rounded-2xl border border-slate-200 p-4">
            <h3 class="font-semibold mb-3">Partner Breakdown</h3>
            <div class="overflow-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="text-left border-b">
                    <th class="py-2 pr-3">Partner</th>
                    <th class="py-2 pr-3">Share</th>
                    <th class="py-2 pr-3">Used</th>
                    <th class="py-2 pr-3">Remaining</th>
                  </tr>
                </thead>
                <tbody id="partnerBreakdownRows"></tbody>
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
