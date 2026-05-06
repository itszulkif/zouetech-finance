<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requireAuthPage();
$activePage = 'partners';
$pageTitle = 'Partner Center';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partner Center - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <div class="min-h-screen lg:flex">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <section class="flex-1 min-w-0">
      <?php require __DIR__ . '/partials/header.php'; ?>
      <main class="p-4 md:p-6 grid grid-cols-1 gap-4">
        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-lg">Partners</h3>
            <span id="partnerTotal" class="text-sm text-slate-500"></span>
          </div>
          <form id="partnerForm" class="grid md:grid-cols-3 gap-2 mb-3">
            <input name="name" placeholder="Partner name" class="rounded-xl border border-slate-200 px-3 py-2" required>
            <input name="percentage" type="number" min="0.01" max="100" step="0.01" placeholder="Share %" class="rounded-xl border border-slate-200 px-3 py-2" required>
            <button class="bg-blue-600 text-white rounded-xl px-4 py-2">Add</button>
          </form>
          <div class="overflow-auto">
            <table class="min-w-full text-sm">
              <thead><tr class="text-left border-b"><th class="py-2 pr-3">Name</th><th class="pr-3">Share</th><th>Action</th></tr></thead>
              <tbody id="partnerRows"></tbody>
            </table>
          </div>
        </article>

        <article class="bg-white rounded-2xl border border-slate-200 p-5">
          <h3 class="font-semibold text-lg mb-3">Partner Ledger Snapshot</h3>
          <div class="flex flex-col sm:flex-row gap-2 mb-3">
            <select id="ledgerPartner" class="rounded-xl border border-slate-200 px-3 py-2 flex-1">
              <option value="">Loading partners...</option>
            </select>
            <select id="ledgerRange" class="rounded-xl border border-slate-200 px-3 py-2 flex-1">
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly" selected>Monthly</option>
              <option value="all">All Time</option>
            </select>
            <button id="ledgerLoad" class="bg-slate-900 text-white rounded-xl px-4">Load</button>
          </div>
          <div id="ledgerSummary" class="grid grid-cols-2 gap-2 text-sm"></div>
          <section class="mt-4 grid lg:grid-cols-2 gap-4">
            <article class="rounded-xl border border-slate-200 p-4">
              <h4 class="font-semibold mb-2 text-sm">Income (Partner Share Received)</h4>
              <div class="overflow-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left border-b">
                      <th class="py-2 pr-3">Added At</th>
                      <th class="py-2 pr-3">Transaction Date</th>
                      <th class="py-2 pr-3">Details</th>
                      <th class="py-2 pr-3">Amount</th>
                    </tr>
                  </thead>
                  <tbody id="incomeRows"></tbody>
                </table>
              </div>
            </article>
            <article class="rounded-xl border border-slate-200 p-4">
              <h4 class="font-semibold mb-2 text-sm">Out-of-Pocket Expenses (with Status)</h4>
              <div class="overflow-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="text-left border-b">
                      <th class="py-2 pr-3">Added At</th>
                      <th class="py-2 pr-3">Transaction Date</th>
                      <th class="py-2 pr-3">Details</th>
                      <th class="py-2 pr-3">Amount</th>
                      <th class="py-2 pr-3">Status</th>
                    </tr>
                  </thead>
                  <tbody id="purchaseRows"></tbody>
                </table>
              </div>
            </article>
          </section>

          <ul id="ledgerTimeline" class="mt-4 text-sm space-y-2 max-h-56 overflow-auto"></ul>
        </article>
      </main>
    </section>
  </div>
  <div id="toast" class="fixed right-4 top-4 hidden bg-slate-900 text-white px-4 py-2 rounded-xl text-sm"></div>
  <script src="/zou-finance/public/dashboard.js"></script>
  <script>
    const f = new Intl.NumberFormat("en-PK", { style: "currency", currency: "PKR" });
    let partnersCache = [];

    async function api(url, opts = {}) {
      const res = await fetch(url, opts);
      const data = await res.json();
      if (!data.success) throw new Error(data.message || "Request failed");
      return data.data || {};
    }

    function formatMoney(val) {
      const n = Number(val || 0);
      return f.format(n);
    }

    function renderLedgerPartnerSelect(partners) {
      partnersCache = Array.isArray(partners) ? partners : [];
      const options = partnersCache.map((p) => `<option value="${p.id}">${p.name}</option>`).join("");
      const ledgerSel = document.getElementById("ledgerPartner");
      if (ledgerSel) {
        ledgerSel.innerHTML = `<option value="">Select partner</option>${options}`;
        if (!ledgerSel.value && partnersCache.length > 0) {
          ledgerSel.value = String(partnersCache[0].id);
        }
      }
    }

    async function loadPartnersCenter() {
      const data = await api("/zou-finance/api/partners.php?page=1&limit=100");
      renderLedgerPartnerSelect(data.partners || []);
    }

    function renderRows(tbodyId, rows, emptyText, { showStatus = false } = {}) {
      const el = document.getElementById(tbodyId);
      if (!el) return;
      el.innerHTML = rows.map((r) => `
        <tr class="border-b">
          <td class="py-2 pr-3">${r.created_at || "-"}</td>
          <td class="py-2 pr-3">${r.transaction_date || "-"}</td>
          <td class="py-2 pr-3">${r.description || "-"}</td>
          <td class="py-2 pr-3">${formatMoney(r.amount)}</td>
          ${showStatus ? `<td class="py-2 pr-3">${r.status || "-"}</td>` : ""}
        </tr>
      `).join("") || `<tr><td class="py-2" colspan="${showStatus ? 5 : 4}">${emptyText}</td></tr>`;
    }

    async function loadLedgerDetails() {
      const partnerId = document.getElementById("ledgerPartner")?.value || "";
      if (!partnerId) return;
      const range = document.getElementById("ledgerRange")?.value || "monthly";

      const json = await api(`/zou-finance/api/partner_ledger_details.php?partner_id=${encodeURIComponent(partnerId)}&range=${encodeURIComponent(range)}`);
      const payload = json;

      const s = payload.summary || {};
      document.getElementById("ledgerSummary").innerHTML = `
        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">Profit Shares Received: ${formatMoney(s.total_income_received)}</div>
        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">Out-of-Pocket Paid: ${formatMoney(s.total_out_of_pocket)}</div>
        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">Cleared by Company: ${formatMoney(s.total_reimbursed)}</div>
        <div class="bg-slate-50 rounded-lg p-3 border border-slate-100">Outstanding Liability: ${formatMoney(s.outstanding_liability)}</div>
      `;

      renderRows("incomeRows", payload.income || [], "No income found");
      renderRows("purchaseRows", payload.purchases || [], "No out-of-pocket expenses found", { showStatus: true });

      const timeline = payload.timeline || [];
      const list = timeline.map((r) => {
        const isIncome = r.kind === "income";
        const isReimbursement = r.kind === "reimbursement";
        const sign = isIncome ? "+" : (isReimbursement ? "~" : "-");
        const color = isIncome ? "text-emerald-600" : (isReimbursement ? "text-blue-600" : "text-rose-600");
        return `
          <li class="border-b pb-1 flex flex-wrap gap-2">
            <span>${r.transaction_date || "-"}</span>
            <span class="${color}">${sign}${formatMoney(r.amount)}</span>
            <span class="text-slate-500">${r.description || ""}</span>
          </li>
        `;
      }).join("");

      document.getElementById("ledgerTimeline").innerHTML = list || "<li>No ledger entries</li>";
    }

    document.getElementById("ledgerLoad")?.addEventListener("click", () => loadLedgerDetails().catch(() => {}));
    document.getElementById("ledgerRange")?.addEventListener("change", () => loadLedgerDetails().catch(() => {}));
    document.getElementById("ledgerPartner")?.addEventListener("change", () => loadLedgerDetails().catch(() => {}));

    (async () => {
      try {
        await loadPartnersCenter();
        await loadLedgerDetails();
      } catch (err) {
        if (typeof notify === "function") {
          notify(err.message || "Failed to load partners", false);
        }
      }
    })();
  </script>
</body>
</html>
