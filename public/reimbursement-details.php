<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'income';
$pageTitle = 'Reimbursement History';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reimbursement History - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 space-y-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-5 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="font-semibold text-lg">Reimbursement History</h3>
            <p class="text-sm text-slate-500">Track all partner reimbursement payouts made from company balance.</p>
          </div>
          <a href="/zou-finance/public/income.php" class="text-sm rounded-xl border border-slate-200 px-4 py-2 hover:bg-slate-50">
            Back to Income Management
          </a>
        </article>

        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
            <div class="rounded-xl border border-slate-200 p-3">
              <p class="text-xs text-slate-500">Total Reimbursed</p>
              <p id="reimbursementTotal" class="text-base font-semibold text-indigo-600">PKR 0.00</p>
            </div>
            <div class="flex flex-wrap items-end gap-2">
              <label class="text-sm text-slate-600">Range
                <select id="reimbursementRange" class="mt-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
                  <option value="daily">Daily</option>
                  <option value="weekly">Weekly</option>
                  <option value="monthly">Monthly</option>
                  <option value="all" selected>All Time</option>
                </select>
              </label>
              <button id="reimbursementApply" class="rounded-xl bg-blue-600 text-white px-4 py-2 text-sm">Apply</button>
            </div>
          </div>

          <div class="overflow-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left border-b">
                  <th class="py-2 pr-3">Added At</th>
                  <th class="py-2 pr-3">Transaction Date</th>
                  <th class="py-2 pr-3">Partner</th>
                  <th class="py-2 pr-3">Note</th>
                  <th class="py-2 pr-3">Amount</th>
                </tr>
              </thead>
              <tbody id="reimbursementRows"></tbody>
            </table>
          </div>
          <div class="mt-3 flex gap-2">
            <button id="reimbursementPrev" class="px-3 py-1.5 rounded-lg border text-sm">Prev</button>
            <button id="reimbursementNext" class="px-3 py-1.5 rounded-lg border text-sm">Next</button>
          </div>
        </article>
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="/zou-finance/public/dashboard.js"></script>
</body>
</html>
