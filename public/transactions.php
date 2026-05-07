<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'reports';
$pageTitle = 'All Transactions';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>All Transactions - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800" data-page="transactions">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-4">
          <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3">
            <div>
              <h2 class="text-lg font-semibold">All Transactions</h2>
              <p class="text-sm text-slate-500">View full transaction history with date filters.</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-2">
              <label class="text-sm text-slate-600">Filter
                <select id="txRange" class="mt-1 rounded-lg border border-slate-200 px-3 py-2 text-sm w-full sm:w-auto">
                  <option value="daily" selected>Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                </select>
              </label>
              <button id="txFilterApply" type="button" class="rounded-lg bg-blue-600 text-white px-4 py-2 text-sm">Apply</button>
            </div>
          </div>
        </article>

        <article class="bg-white rounded-2xl border border-slate-200 p-4">
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
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="<?= htmlspecialchars(app_url('/public/dashboard.js')) ?>"></script>
</body>
</html>

