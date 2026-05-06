const $ = (s) => document.querySelector(s);
const toast = $("#toast");
let txPage = 1;
const txLimit = 10;
let txHasMore = true;
let expensePage = 1;
const expenseLimit = 10;
let expenseHasMore = true;
let reimbursementLiabilities = [];
let incomeDetailsPage = 1;
const incomeDetailsLimit = 10;
let incomeDetailsHasMore = true;
let reimbursementHistoryPage = 1;
const reimbursementHistoryLimit = 10;
let reimbursementHistoryHasMore = true;
// Allow pages to opt into always-visible daily expense panel via global flag
let dailyExpensePanelVisible = typeof window !== "undefined" && window.__DAILY_EXPENSE_PAGE__ ? true : false;
let kpiChart;
const pkrFormatter = new Intl.NumberFormat("en-PK", { style: "currency", currency: "PKR", maximumFractionDigits: 2 });
const formatPKR = (value) => pkrFormatter.format(Number(value || 0));
const allowedRanges = new Set(["all", "daily", "weekly", "monthly", "yearly"]);
const appSidebar = $("#appSidebar");
const sidebarToggleBtn = $("#sidebarToggleBtn");
const sidebarBackdrop = $("#sidebarBackdrop");

function isMobileViewport() {
  return typeof window !== "undefined" && window.matchMedia("(max-width: 1023px)").matches;
}

function setSidebarOpenState(isOpen) {
  if (!appSidebar) return;
  appSidebar.classList.toggle("hidden", !isOpen);
  appSidebar.classList.toggle("block", isOpen);
  if (sidebarBackdrop) {
    sidebarBackdrop.classList.toggle("hidden", !isOpen);
  }
  if (sidebarToggleBtn) {
    sidebarToggleBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    sidebarToggleBtn.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
  }
}

function closeSidebarOnMobile() {
  if (!isMobileViewport()) return;
  setSidebarOpenState(false);
}

function bindSidebarEvents() {
  if (!appSidebar) return;

  setSidebarOpenState(false);

  sidebarToggleBtn?.addEventListener("click", () => {
    const isOpen = appSidebar.classList.contains("hidden");
    setSidebarOpenState(isOpen);
  });

  sidebarBackdrop?.addEventListener("click", () => {
    setSidebarOpenState(false);
  });

  appSidebar.querySelectorAll("a").forEach((navLink) => {
    navLink.addEventListener("click", closeSidebarOnMobile);
  });

  window.addEventListener("resize", () => {
    if (!isMobileViewport()) {
      setSidebarOpenState(false);
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeSidebarOnMobile();
    }
  });
}

function notify(msg, ok = true) {
  if (!toast) return;
  toast.className = `fixed right-4 top-4 ${ok ? "bg-emerald-600" : "bg-rose-600"} text-white px-4 py-2 rounded-xl text-sm`;
  toast.textContent = msg;
  toast.classList.remove("hidden");
  setTimeout(() => toast.classList.add("hidden"), 2200);
}

async function api(url, opts = {}) {
  const res = await fetch(url, opts);
  const data = await res.json();
  if (!data.success) throw new Error(data.message || "Request failed");
  return data.data || {};
}

function has(el) {
  return !!$(el);
}

function hasCustomRangeControls() {
  const rangeEl = $("#range");
  if (!rangeEl || !$("#fromDate") || !$("#toDate")) return false;
  return !!rangeEl.querySelector("option[value='custom']");
}

function getSelectedRange() {
  const range = $("#range")?.value || "all";
  return allowedRanges.has(range) ? range : "all";
}

function buildRangeQuery() {
  const selectedRange = getSelectedRange();
  const params = new URLSearchParams({ range: selectedRange });
  if (selectedRange === "custom" && hasCustomRangeControls()) {
    const from = $("#fromDate").value || "";
    const to = $("#toDate").value || "";
    if (from && to) {
      params.set("from", from);
      params.set("to", to);
    }
  }
  return params.toString();
}

function getTransactionRange() {
  const txRange = $("#txRange")?.value;
  if (txRange && allowedRanges.has(txRange)) return txRange;
  const pageName = document.body?.dataset?.page || "";
  if (pageName === "dashboard") return "daily";
  return getSelectedRange();
}

function buildTransactionQuery() {
  const params = new URLSearchParams({ range: getTransactionRange() });
  return params.toString();
}

function toggleCustomRangeControls() {
  if (!hasCustomRangeControls()) return;
  const isCustom = getSelectedRange() === "custom";
  $("#fromDate")?.classList.toggle("hidden", !isCustom);
  $("#toDate")?.classList.toggle("hidden", !isCustom);
}

function updateTxPaginationControls(rows = []) {
  txHasMore = rows.length >= txLimit;
  const prevBtn = $("#txPrev");
  const nextBtn = $("#txNext");
  if (prevBtn) prevBtn.disabled = txPage <= 1;
  if (nextBtn) nextBtn.disabled = !txHasMore;
  if (prevBtn) prevBtn.classList.toggle("opacity-50", prevBtn.disabled);
  if (nextBtn) nextBtn.classList.toggle("opacity-50", nextBtn.disabled);
}

function updateExpensePaginationControls(rows = []) {
  expenseHasMore = rows.length >= expenseLimit;
  const prevBtn = $("#expensePrev");
  const nextBtn = $("#expenseNext");
  if (prevBtn) prevBtn.disabled = expensePage <= 1;
  if (nextBtn) nextBtn.disabled = !expenseHasMore;
  if (prevBtn) prevBtn.classList.toggle("opacity-50", prevBtn.disabled);
  if (nextBtn) nextBtn.classList.toggle("opacity-50", nextBtn.disabled);
}

function updateIncomeDetailsPaginationControls(rows = []) {
  incomeDetailsHasMore = rows.length >= incomeDetailsLimit;
  const prevBtn = $("#incomeDetailsPrev");
  const nextBtn = $("#incomeDetailsNext");
  if (prevBtn) prevBtn.disabled = incomeDetailsPage <= 1;
  if (nextBtn) nextBtn.disabled = !incomeDetailsHasMore;
  if (prevBtn) prevBtn.classList.toggle("opacity-50", prevBtn.disabled);
  if (nextBtn) nextBtn.classList.toggle("opacity-50", nextBtn.disabled);
}

function updateReimbursementHistoryPaginationControls(rows = []) {
  reimbursementHistoryHasMore = rows.length >= reimbursementHistoryLimit;
  const prevBtn = $("#reimbursementPrev");
  const nextBtn = $("#reimbursementNext");
  if (prevBtn) prevBtn.disabled = reimbursementHistoryPage <= 1;
  if (nextBtn) nextBtn.disabled = !reimbursementHistoryHasMore;
  if (prevBtn) prevBtn.classList.toggle("opacity-50", prevBtn.disabled);
  if (nextBtn) nextBtn.classList.toggle("opacity-50", nextBtn.disabled);
}

function buildExpenseQuery() {
  const selected = $("#expenseRange")?.value || "all";
  const range = allowedRanges.has(selected) ? selected : "all";
  const type = $("#expenseTypeFilter")?.value || "all";
  const params = new URLSearchParams({
    range,
    type,
    page: String(expensePage),
    limit: String(expenseLimit)
  });
  return params.toString();
}

function buildIncomeDetailsQuery() {
  const selected = $("#incomeDetailsRange")?.value || "all";
  const range = allowedRanges.has(selected) ? selected : "all";
  const params = new URLSearchParams({
    range,
    page: String(incomeDetailsPage),
    limit: String(incomeDetailsLimit)
  });
  return params.toString();
}

function buildReimbursementHistoryQuery() {
  const selected = $("#reimbursementRange")?.value || "all";
  const range = allowedRanges.has(selected) ? selected : "all";
  const params = new URLSearchParams({
    range,
    page: String(reimbursementHistoryPage),
    limit: String(reimbursementHistoryLimit)
  });
  return params.toString();
}

function togglePartnerExpenseFields(expenseType) {
  const isPartnerExpense = expenseType === "partner";
  if (has("#partnerSelectWrap")) $("#partnerSelectWrap").classList.toggle("hidden", !isPartnerExpense);
}

async function refreshRangeDependentData() {
  await Promise.all([loadDashboard(), loadTransactions()]);
}

async function applyFilters({ resetTransactions = false } = {}) {
  if (resetTransactions) txPage = 1;
  toggleCustomRangeControls();
  await refreshRangeDependentData();
}

async function refreshAllData() {
  await Promise.all([loadDashboard(), loadPartners(), loadTransactions(), loadIncomePreview(), loadPartnerBalanceCards(), loadReimbursementModule()]);
  if (dailyExpensePanelVisible) {
    await Promise.all([loadExpenseHistory(), loadPartnerExpenseDetails()]);
  }
}

function supportsRangeFilter() {
  return has("#range");
}

function isCustomRangeActive() {
  return hasCustomRangeControls() && getSelectedRange() === "custom";
}

function shouldSkipCustomDateEvent() {
  return !isCustomRangeActive();
}

async function safeLoadWithToast(loader) {
  try {
    await loader();
  } catch (err) {
    notify(err.message, false);
  }
}

async function loadDashboard() {
  if (!has("#kpis") && !has("#partnerBreakdownRows") && !has("#kpiChart")) return;
  const query = buildRangeQuery();
  const url = `/zou-finance/api/dashboard.php?${query}`;
  const data = await api(url);
  renderKpis(data);
  renderPartnerBreakdown(data.partner_breakdown || []);
  renderDashboardChart(data);
  return data;
}

async function loadTransactions() {
  if (!has("#transactionRows")) return;
  const data = await api(`/zou-finance/api/transactions.php?${buildTransactionQuery()}&page=${txPage}&limit=${txLimit}`);
  const rows = data.transactions || [];
  $("#transactionRows").innerHTML = rows.map((t) => {
    let rowClass = "";
    if (t.tx_group === "expense" && t.tx_type === "company") {
      rowClass = "bg-amber-50";
    } else if (t.tx_group === "expense" && t.tx_type === "partner") {
      const impact = String(t.impact_note || "").toLowerCase();
      rowClass = impact.includes("paid by company") ? "bg-indigo-50" : "bg-rose-50";
    }
    return `
    <tr class="border-b ${rowClass}">
      <td class="py-2 pr-3 hidden md:table-cell">${t.recorded_at || "-"}</td>
      <td class="py-2 pr-3">${t.transaction_date || "-"}</td>
      <td class="py-2 pr-3 capitalize hidden sm:table-cell">${t.tx_group}</td>
      <td class="py-2 pr-3 capitalize">${t.tx_type}</td>
      <td class="py-2 pr-3">${t.partner_name || "-"}</td>
      <td class="py-2 pr-3 hidden lg:table-cell">${t.description || "-"}</td>
      <td class="py-2 pr-3 hidden lg:table-cell">${t.impact_note || "-"}</td>
      <td class="py-2 pr-3">${formatPKR(t.amount)}</td>
    </tr>
  `;
  }).join("") || `<tr><td class="py-2" colspan="8">No transactions</td></tr>`;
  updateTxPaginationControls(rows);
}

async function loadIncomePreview() {
  if (!has("#incomeForm")) return;
  const amount = Number($("#incomeForm [name='amount']")?.value || 0);
  const type = $("#incomeForm [name='type']")?.value || "distributed";
  const data = await api(`/zou-finance/api/income_preview.php?amount=${encodeURIComponent(amount)}&type=${encodeURIComponent(type)}`);
  const modeLabel = type === "distributed" ? "Mode: 50/50 Split" : "Mode: 100% Company";
  if (has("#incomePreview")) $("#incomePreview").textContent = `${modeLabel} | Company: ${formatPKR(data.company_share)} | Partner Pool: ${formatPKR(data.partner_pool)}`;
  if (has("#incomeDistributionPreview")) {
    const lines = (data.distribution || []).map((d) => `<span class="inline-block mr-3">${d.name}: ${formatPKR(d.amount)} (${d.percentage}%)</span>`).join("");
    $("#incomeDistributionPreview").innerHTML = `<p><strong>Partner Distribution:</strong> ${lines || "N/A"}</p>`;
  }
}

async function loadExpenseHistory() {
  if (!has("#expenseHistoryRows")) return;
  if (!dailyExpensePanelVisible) return;
  const data = await api(`/zou-finance/api/expenses.php?${buildExpenseQuery()}`);
  const rows = data.expenses || [];
  $("#expenseHistoryRows").innerHTML = rows.map((r) => {
    const rowClass = r.type === "company"
      ? "bg-amber-50"
      : (r.payment_mode === "company_pay" ? "bg-indigo-50" : "bg-rose-50");
    return `
    <tr class="border-b ${rowClass}">
      <td class="py-2 pr-3">${r.created_at || "-"}</td>
      <td class="py-2 pr-3">${r.transaction_date || "-"}</td>
      <td class="py-2 pr-3 capitalize">${r.type === "partner" ? `partner (${r.payment_mode === "company_pay" ? "company reimburses" : "out-of-pocket"})` : (r.type || "-")}</td>
      <td class="py-2 pr-3">${r.partner_name || "-"}</td>
      <td class="py-2 pr-3">${r.description || "-"}</td>
      <td class="py-2 pr-3">${formatPKR(r.amount)}</td>
    </tr>
  `;
  }).join("") || `<tr><td class="py-2" colspan="6">No expense history</td></tr>`;
  updateExpensePaginationControls(rows);
}

async function loadPartnerExpenseDetails() {
  if (!has("#partnerExpenseRows")) return;
  if (!dailyExpensePanelVisible) return;
  const range = $("#expenseRange")?.value || "all";
  const data = await api(`/zou-finance/api/expenses.php?range=${encodeURIComponent(range)}&type=partner&page=1&limit=10`);
  const rows = data.expenses || [];
  $("#partnerExpenseRows").innerHTML = rows.map((r) => `
    <tr class="border-b ${r.payment_mode === "company_pay" ? "bg-indigo-50" : "bg-rose-50"}">
      <td class="py-2 pr-3">${r.created_at || "-"}</td>
      <td class="py-2 pr-3">${r.transaction_date || "-"}</td>
      <td class="py-2 pr-3">${r.partner_name || "-"}</td>
      <td class="py-2 pr-3">${r.description || "-"}</td>
      <td class="py-2 pr-3">${formatPKR(r.amount)}</td>
    </tr>
  `).join("") || `<tr><td class="py-2" colspan="5">No partner expense details</td></tr>`;
  if (has("#partnerExpenseTotal")) {
    $("#partnerExpenseTotal").textContent = formatPKR(data.summary?.partner_total || 0);
  }
}

async function loadPartnerBalanceCards() {
  if (!has("#partnerBalanceCards")) return;
  const selectedRange = $("#expenseRange")?.value || "all";
  const range = dailyExpensePanelVisible && allowedRanges.has(selectedRange) ? selectedRange : "all";
  const data = await api(`/zou-finance/api/dashboard.php?range=${encodeURIComponent(range)}`);
  const rows = data.partner_breakdown || [];
  $("#partnerBalanceCards").innerHTML = rows.map((r) => {
    const liability = Number(r.remaining_balance || 0);
    const hasLiability = liability > 0;
    const status = hasLiability ? "Liability (Payable)" : "No Liability";
    const statusClass = hasLiability ? "text-rose-600" : "text-emerald-600";
    return `
      <article class="rounded-xl border border-slate-200 p-3 bg-white">
        <p class="text-sm text-slate-600">${r.name}</p>
        <p class="text-xs mt-1 ${statusClass}">${status}</p>
        <p class="text-lg font-semibold mt-1 ${statusClass}">${formatPKR(liability)}</p>
      </article>
    `;
  }).join("") || `<p class="text-sm text-slate-500">No partner balances found.</p>`;
}

function normalizeIncomeTypeLabel(type) {
  if (type === "distributed") return "Normal Income (50/50)";
  if (type === "company_only") return "Company Only";
  if (type === "external_source") return "External Source";
  return type || "-";
}

async function loadIncomeDetails() {
  if (!has("#incomeDetailsRows")) return;
  const data = await api(`/zou-finance/api/income_details.php?${buildIncomeDetailsQuery()}`);
  const rows = data.income || [];
  $("#incomeDetailsRows").innerHTML = rows.map((r) => `
    <tr class="border-b">
      <td class="py-2 pr-3">${r.created_at || "-"}</td>
      <td class="py-2 pr-3">${r.transaction_date || "-"}</td>
      <td class="py-2 pr-3">${normalizeIncomeTypeLabel(r.type)}</td>
      <td class="py-2 pr-3 capitalize">${r.source || "-"}</td>
      <td class="py-2 pr-3">${r.note || "-"}</td>
      <td class="py-2 pr-3">${formatPKR(r.amount)}</td>
    </tr>
  `).join("") || `<tr><td class="py-2" colspan="6">No income records found.</td></tr>`;
  if (has("#incomeOverallTotal")) $("#incomeOverallTotal").textContent = formatPKR(data.summary?.overall_total || 0);
  if (has("#incomeDistributedTotal")) $("#incomeDistributedTotal").textContent = formatPKR(data.summary?.distributed_total || 0);
  if (has("#incomeCompanyTotal")) $("#incomeCompanyTotal").textContent = formatPKR(data.summary?.company_total || 0);
  updateIncomeDetailsPaginationControls(rows);
}

async function loadReimbursementHistory() {
  if (!has("#reimbursementRows")) return;
  const data = await api(`/zou-finance/api/reimbursement_history.php?${buildReimbursementHistoryQuery()}`);
  const rows = data.reimbursements || [];
  $("#reimbursementRows").innerHTML = rows.map((r) => `
    <tr class="border-b bg-indigo-50">
      <td class="py-2 pr-3">${r.created_at || "-"}</td>
      <td class="py-2 pr-3">${r.transaction_date || "-"}</td>
      <td class="py-2 pr-3">${r.partner_name || "-"}</td>
      <td class="py-2 pr-3">${r.note || "-"}</td>
      <td class="py-2 pr-3">${formatPKR(r.amount)}</td>
    </tr>
  `).join("") || `<tr><td class="py-2" colspan="5">No reimbursement records found.</td></tr>`;
  if (has("#reimbursementTotal")) $("#reimbursementTotal").textContent = formatPKR(data.summary?.total_reimbursed || 0);
  updateReimbursementHistoryPaginationControls(rows);
}

function renderReimbursementOutstanding() {
  const partnerId = Number($("#reimbursementPartnerSelect")?.value || 0);
  const selected = reimbursementLiabilities.find((item) => Number(item.id) === partnerId);
  const outstanding = Number(selected?.remaining_balance || 0);
  if (has("#reimbursementOutstanding")) {
    $("#reimbursementOutstanding").value = formatPKR(outstanding);
  }
}

async function loadReimbursementModule() {
  if (!has("#reimbursementForm")) return;
  const data = await api("/zou-finance/api/reimbursements.php?range=all");
  const rows = (data.partners || []).filter((row) => Number(row.remaining_balance || 0) > 0);
  reimbursementLiabilities = rows;

  if (has("#reimbursementLiabilityCards")) {
    $("#reimbursementLiabilityCards").innerHTML = rows.map((r) => `
      <article class="rounded-xl border border-slate-200 p-3 bg-white">
        <p class="text-sm text-slate-600">${r.name}</p>
        <p class="text-xs mt-1 text-rose-600">Outstanding Liability</p>
        <p class="text-lg font-semibold mt-1 text-rose-600">${formatPKR(r.remaining_balance)}</p>
      </article>
    `).join("") || `<p class="text-sm text-slate-500">No outstanding partner liabilities.</p>`;
  }

  if (has("#reimbursementPartnerSelect")) {
    const options = rows.map((row) => `<option value="${row.id}">${row.name} (${formatPKR(row.remaining_balance)})</option>`).join("");
    $("#reimbursementPartnerSelect").innerHTML = `<option value="">Select partner</option>${options}`;
    renderReimbursementOutstanding();
  }
}

function bindRangeEvents() {
  if (!supportsRangeFilter()) return;

  $("#range")?.addEventListener("change", async () => {
    await safeLoadWithToast(() => applyFilters({ resetTransactions: true }));
  });

  $("#fromDate")?.addEventListener("change", async () => {
    if (shouldSkipCustomDateEvent()) return;
    await safeLoadWithToast(() => applyFilters({ resetTransactions: true }));
  });

  $("#toDate")?.addEventListener("change", async () => {
    if (shouldSkipCustomDateEvent()) return;
    await safeLoadWithToast(() => applyFilters({ resetTransactions: true }));
  });
}

function bindReloadEvent() {
  return;
}

function bindTransactionPaginationEvents() {
  $("#txPrev")?.addEventListener("click", async () => {
    if (txPage <= 1) return;
    txPage = Math.max(1, txPage - 1);
    await safeLoadWithToast(loadTransactions);
  });

  $("#txNext")?.addEventListener("click", async () => {
    if (!txHasMore) return;
    txPage += 1;
    await safeLoadWithToast(loadTransactions);
  });
}

function bindTransactionFilterEvents() {
  $("#txRange")?.addEventListener("change", async () => {
    txPage = 1;
    await safeLoadWithToast(loadTransactions);
  });
  $("#txFilterApply")?.addEventListener("click", async () => {
    txPage = 1;
    await safeLoadWithToast(loadTransactions);
  });
}

function bindExpenseHistoryEvents() {
  $("#toggleDailyExpensesBtn")?.addEventListener("click", async (e) => {
    const panel = $("#dailyExpensePanel");
    if (!panel) return;
    dailyExpensePanelVisible = !dailyExpensePanelVisible;
    panel.classList.toggle("hidden", !dailyExpensePanelVisible);
    e.target.textContent = dailyExpensePanelVisible ? "Hide Daily Expense Details" : "Show Daily Expense Details";
    if (dailyExpensePanelVisible) {
      expensePage = 1;
      if ($("#expenseRange")) $("#expenseRange").value = "daily";
      await safeLoadWithToast(async () => {
        await Promise.all([loadExpenseHistory(), loadPartnerExpenseDetails(), loadPartnerBalanceCards()]);
      });
    }
  });
  $("#expenseFilterApply")?.addEventListener("click", async () => {
    if (!dailyExpensePanelVisible) return;
    expensePage = 1;
    await safeLoadWithToast(async () => {
      await Promise.all([loadExpenseHistory(), loadPartnerExpenseDetails(), loadPartnerBalanceCards()]);
    });
  });
  $("#expenseRange")?.addEventListener("change", async () => {
    if (!dailyExpensePanelVisible) return;
    expensePage = 1;
    await safeLoadWithToast(async () => {
      await Promise.all([loadExpenseHistory(), loadPartnerExpenseDetails(), loadPartnerBalanceCards()]);
    });
  });
  $("#expenseTypeFilter")?.addEventListener("change", async () => {
    expensePage = 1;
    await safeLoadWithToast(loadExpenseHistory);
  });
  $("#expensePrev")?.addEventListener("click", async () => {
    if (expensePage <= 1) return;
    expensePage = Math.max(1, expensePage - 1);
    await safeLoadWithToast(loadExpenseHistory);
  });
  $("#expenseNext")?.addEventListener("click", async () => {
    if (!expenseHasMore) return;
    expensePage += 1;
    await safeLoadWithToast(loadExpenseHistory);
  });
}

function bindIncomeDetailsEvents() {
  $("#incomeDetailsApply")?.addEventListener("click", async () => {
    incomeDetailsPage = 1;
    await safeLoadWithToast(loadIncomeDetails);
  });
  $("#incomeDetailsRange")?.addEventListener("change", async () => {
    incomeDetailsPage = 1;
    await safeLoadWithToast(loadIncomeDetails);
  });
  $("#incomeDetailsPrev")?.addEventListener("click", async () => {
    if (incomeDetailsPage <= 1) return;
    incomeDetailsPage = Math.max(1, incomeDetailsPage - 1);
    await safeLoadWithToast(loadIncomeDetails);
  });
  $("#incomeDetailsNext")?.addEventListener("click", async () => {
    if (!incomeDetailsHasMore) return;
    incomeDetailsPage += 1;
    await safeLoadWithToast(loadIncomeDetails);
  });
}

function bindReimbursementHistoryEvents() {
  $("#reimbursementApply")?.addEventListener("click", async () => {
    reimbursementHistoryPage = 1;
    await safeLoadWithToast(loadReimbursementHistory);
  });
  $("#reimbursementRange")?.addEventListener("change", async () => {
    reimbursementHistoryPage = 1;
    await safeLoadWithToast(loadReimbursementHistory);
  });
  $("#reimbursementPrev")?.addEventListener("click", async () => {
    if (reimbursementHistoryPage <= 1) return;
    reimbursementHistoryPage = Math.max(1, reimbursementHistoryPage - 1);
    await safeLoadWithToast(loadReimbursementHistory);
  });
  $("#reimbursementNext")?.addEventListener("click", async () => {
    if (!reimbursementHistoryHasMore) return;
    reimbursementHistoryPage += 1;
    await safeLoadWithToast(loadReimbursementHistory);
  });
}

toggleCustomRangeControls();

function renderKpis(data) {
  if (!has("#kpis")) return;
  const map = [
    ["Total Income", data.total_income, "text-emerald-600"],
    ["Total Expenses", data.total_expenses, "text-rose-600"],
    ["Net Balance", data.net_balance, "text-blue-600"],
    ["Company Balance", data.company_balance, "text-slate-900"],
    ["Partner Liabilities", data.partner_liabilities, "text-amber-600"]
  ];
  $("#kpis").innerHTML = map
    .map(([label, value, cls]) => `<article class="bg-white rounded-2xl border border-slate-200 p-4"><p class="text-xs text-slate-500">${label}</p><p class="text-xl font-bold ${cls}">${formatPKR(value)}</p></article>`)
    .join("");
}

function renderPartnerBreakdown(rows) {
  if (!has("#partnerBreakdownRows")) return;
  $("#partnerBreakdownRows").innerHTML = rows.map((r) => `
    <tr class="border-b">
      <td class="py-2 pr-3">${r.name}</td>
      <td class="py-2 pr-3">${formatPKR(r.share_received)}</td>
      <td class="py-2 pr-3 hidden sm:table-cell">${formatPKR(r.used_amount)}</td>
      <td class="py-2 pr-3">${formatPKR(r.remaining_balance)}</td>
    </tr>
  `).join("") || `<tr><td class="py-2" colspan="4">No data</td></tr>`;
}

function renderDashboardChart(data) {
  const canvas = $("#kpiChart");
  if (!canvas || typeof Chart === "undefined") return;
  if (kpiChart) kpiChart.destroy();
  kpiChart = new Chart(canvas, {
    type: "bar",
    data: {
      labels: ["Income", "Expense", "Net", "Company Balance", "Partner Liabilities"],
      datasets: [{
        data: [data.total_income, data.total_expenses, data.net_balance, data.company_balance, data.partner_liabilities],
        backgroundColor: ["#059669", "#dc2626", "#2563eb", "#0f172a", "#d97706"]
      }]
    },
    options: { plugins: { legend: { display: false } } }
  });
}

function renderPartners(partners, total) {
  if (has("#partnerTotal")) $("#partnerTotal").textContent = `Total: ${Number(total).toFixed(2)}%`;
  if (has("#partnerRows")) {
    $("#partnerRows").innerHTML = partners.map((p) => `
      <tr class="border-b">
        <td class="py-2 pr-3">${p.name}</td>
        <td class="py-2 pr-3">${Number(p.percentage).toFixed(2)}%</td>
        <td><button data-id="${p.id}" class="text-rose-600 delete-partner">Delete</button></td>
      </tr>
    `).join("");
  }
  const options = partners.map((p) => `<option value="${p.id}">${p.name}</option>`).join("");
  if (has("#partnerSelect")) $("#partnerSelect").innerHTML = `<option value="">Select partner</option>${options}`;
  if (has("#ledgerPartner")) $("#ledgerPartner").innerHTML = `<option value="">Select partner</option>${options}`;
}

async function loadPartners() {
  if (!has("#partnerRows") && !has("#partnerSelect") && !has("#ledgerPartner")) return;
  const data = await api("/zou-finance/api/partners.php?page=1&limit=100");
  renderPartners(data.partners || [], data.total_percentage || 0);
}

const partnerForm = $("#partnerForm");
if (partnerForm) {
  partnerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      await api("/zou-finance/api/partners.php", { method: "POST", body: new FormData(e.target) });
      e.target.reset();
      await loadPartners();
      notify("Partner added");
    } catch (err) { notify(err.message, false); }
  });
}

document.addEventListener("click", async (e) => {
  if (!(e.target instanceof Element) || !e.target.classList.contains("delete-partner")) return;
  try {
    await api("/zou-finance/api/partners.php", {
      method: "DELETE",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${encodeURIComponent(e.target.dataset.id)}`
    });
    await loadPartners();
    notify("Partner deleted");
  } catch (err) { notify(err.message, false); }
});

const incomeForm = $("#incomeForm");
if (incomeForm) {
  const incomeDateField = $("#incomeForm [name='transaction_date']");
  if (incomeDateField && !incomeDateField.value) {
    incomeDateField.value = new Date().toISOString().slice(0, 10);
  }

  incomeForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const selectedType = $("#incomeForm [name='type']")?.value || "distributed";
      if (selectedType === "distributed") {
        const partnersData = await api("/zou-finance/api/partners.php?page=1&limit=1");
        const totalPercentage = Number(partnersData.total_percentage || 0);
        if (Math.abs(totalPercentage - 100) > 0.01) {
          notify(`Cannot save Normal Income. Partner total is ${totalPercentage.toFixed(2)}% (required 100%).`, false);
          return;
        }
      }
      await api("/zou-finance/api/income.php", { method: "POST", body: new FormData(e.target) });
      e.target.reset();
      if (incomeDateField) {
        incomeDateField.value = new Date().toISOString().slice(0, 10);
      }
      await refreshAllData();
      notify("Income saved");
    } catch (err) { notify(err.message, false); }
  });

  $("#incomeForm [name='amount']")?.addEventListener("input", () => loadIncomePreview().catch(() => {}));
  $("#incomeForm [name='type']")?.addEventListener("change", () => loadIncomePreview().catch(() => {}));
}

const reimbursementForm = $("#reimbursementForm");
if (reimbursementForm) {
  const reimbursementDateField = $("#reimbursementForm [name='transaction_date']");
  if (reimbursementDateField && !reimbursementDateField.value) {
    reimbursementDateField.value = new Date().toISOString().slice(0, 10);
  }

  $("#reimbursementPartnerSelect")?.addEventListener("change", () => {
    renderReimbursementOutstanding();
  });

  reimbursementForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const form = e.target;
      const formData = new FormData(form);
      const partnerId = Number(formData.get("partner_id") || 0);
      const amount = Number(formData.get("amount") || 0);
      const selected = reimbursementLiabilities.find((item) => Number(item.id) === partnerId);
      const outstanding = Number(selected?.remaining_balance || 0);

      if (!partnerId || outstanding <= 0) {
        notify("Selected partner has no outstanding liability.", false);
        return;
      }
      if (amount <= 0 || amount > outstanding) {
        notify(`Reimbursement must be between 0 and ${formatPKR(outstanding)}.`, false);
        return;
      }

      await api("/zou-finance/api/reimbursements.php", { method: "POST", body: formData });
      form.reset();
      if (reimbursementDateField) reimbursementDateField.value = new Date().toISOString().slice(0, 10);
      await refreshAllData();
      notify("Reimbursement processed");
    } catch (err) { notify(err.message, false); }
  });
}

const expenseForm = $("#expenseForm");
if (expenseForm) {
  const expenseDateField = $("#expenseForm [name='transaction_date']");
  if (expenseDateField && !expenseDateField.value) {
    expenseDateField.value = new Date().toISOString().slice(0, 10);
  }

  expenseForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      await api("/zou-finance/api/expenses.php", { method: "POST", body: new FormData(e.target) });
      e.target.reset();
      if (expenseDateField) expenseDateField.value = new Date().toISOString().slice(0, 10);
      togglePartnerExpenseFields("company");
      await Promise.all([refreshRangeDependentData(), loadPartnerBalanceCards()]);
      if (dailyExpensePanelVisible) {
        await Promise.all([loadExpenseHistory(), loadPartnerExpenseDetails()]);
      }
      notify("Expense saved");
    } catch (err) { notify(err.message, false); }
  });
}

$("#expenseType")?.addEventListener("change", (e) => {
  togglePartnerExpenseFields(e.target.value);
});

if (has("#expenseType")) {
  togglePartnerExpenseFields($("#expenseType").value || "company");
}

bindRangeEvents();
bindReloadEvent();

$("#logoutBtn")?.addEventListener("click", async () => {
  await api("/zou-finance/api/logout.php", { method: "POST" });
  window.location.href = "/zou-finance/public/login.php";
});

bindTransactionPaginationEvents();
bindTransactionFilterEvents();
bindExpenseHistoryEvents();
bindIncomeDetailsEvents();
bindReimbursementHistoryEvents();
bindSidebarEvents();

(async () => {
  try {
    await refreshAllData();
    await Promise.all([loadIncomeDetails(), loadReimbursementHistory()]);
  } catch (err) {
    notify(err.message, false);
  }
})();
