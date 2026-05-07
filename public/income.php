<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'income';
$pageTitle = 'Income Management';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Income Management - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6">
        <section class="grid sm:grid-cols-2 xl:grid-cols-5 gap-3 mb-4" id="kpis"></section>
        <article class="bg-white rounded-2xl border border-slate-200 p-5 max-w-4xl">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h3 class="font-semibold text-lg">Add Income</h3>
            <a href="<?= htmlspecialchars(app_url('/public/income-details.php')) ?>" class="rounded-xl border border-slate-200 px-4 py-2 text-sm hover:bg-slate-50">Show Income Details</a>
          </div>
          <p class="text-sm text-slate-500 mb-4">Normal income follows Zouetech formula (50% Company + 50% Partner Pool).</p>
          <form id="incomeForm" class="grid md:grid-cols-2 gap-3">
            <label class="text-sm font-medium text-slate-600">Amount
              <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
            </label>
            <label class="text-sm font-medium text-slate-600">Income Type
              <select name="type" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
                <option value="distributed">Normal Income (50/50)</option>
                <option value="company_only">Company Only (100% Company)</option>
              </select>
            </label>
            <label class="text-sm font-medium text-slate-600">Transaction Date
              <input name="transaction_date" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
            </label>
            <label class="text-sm font-medium text-slate-600">Note
              <input name="note" type="text" maxlength="255" placeholder="Note (optional)" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
            </label>
            <button class="bg-emerald-600 text-white rounded-xl px-4 py-2 md:col-span-2 w-fit">Save Income</button>
          </form>
          <div id="incomePreview" class="mt-4 text-sm text-slate-700 bg-slate-50 rounded-xl p-3">Company: PKR 0 | Partner Pool: PKR 0</div>
          <div id="incomeDistributionPreview" class="mt-3 text-sm text-slate-600"></div>
        </article>
        <article class="bg-white rounded-2xl border border-slate-200 p-5 max-w-4xl mt-4">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
            <h3 class="font-semibold text-lg">Partner Reimbursement</h3>
            <a href="<?= htmlspecialchars(app_url('/public/reimbursement-details.php')) ?>" class="rounded-xl border border-slate-200 px-4 py-2 text-sm hover:bg-slate-50">Show Reimbursement History</a>
          </div>
          <p class="text-sm text-slate-500 mb-4">Settle partner out-of-pocket liabilities from company balance. This is separate from 50/50 income distribution.</p>
          <div id="reimbursementLiabilityCards" class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-4"></div>
          <form id="reimbursementForm" class="grid md:grid-cols-2 gap-3">
            <label class="text-sm font-medium text-slate-600">Partner
              <select name="partner_id" id="reimbursementPartnerSelect" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required></select>
            </label>
            <label class="text-sm font-medium text-slate-600">Outstanding Liability
              <input id="reimbursementOutstanding" type="text" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 bg-slate-50" readonly value="PKR 0.00">
            </label>
            <label class="text-sm font-medium text-slate-600">Reimbursement Amount
              <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount to reimburse" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
            </label>
            <label class="text-sm font-medium text-slate-600">Payment Date
              <input name="transaction_date" type="date" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
            </label>
            <label class="text-sm font-medium text-slate-600 md:col-span-2">Note
              <input name="note" type="text" maxlength="255" placeholder="Optional note" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2">
            </label>
            <button class="bg-indigo-600 text-white rounded-xl px-4 py-2 md:col-span-2 w-fit">Process Reimbursement</button>
          </form>
        </article>
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="<?= htmlspecialchars(app_url('/public/dashboard.js')) ?>"></script>
</body>
</html>
