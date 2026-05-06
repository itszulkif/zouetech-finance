<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
requirePartnerPage();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partner Ledger - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">
  <main class="max-w-4xl mx-auto p-4">
    <header class="bg-white rounded-2xl shadow p-4 flex justify-between items-center">
      <div>
        <h1 class="text-xl font-bold">Partner Ledger</h1>
        <p class="text-sm text-slate-500"><?= htmlspecialchars((string) ($_SESSION['partner_name'] ?? 'Partner')) ?></p>
      </div>
      <select id="range" class="rounded-lg border px-2 py-1 text-sm">
        <option value="daily">Daily</option>
        <option value="weekly">Weekly</option>
        <option value="monthly" selected>Monthly</option>
      </select>
    </header>
    <section class="mt-4 bg-white rounded-2xl shadow p-4">
      <div id="summary" class="grid sm:grid-cols-2 gap-3"></div>
      <ul id="timeline" class="mt-4 space-y-2 text-sm"></ul>
    </section>
  </main>
  <script>
    const f = new Intl.NumberFormat("en-PK", { style: "currency", currency: "PKR" });
    async function load() {
      const range = document.getElementById("range").value;
      const res = await fetch(`/zou-finance/api/partner_ledger.php?range=${encodeURIComponent(range)}`);
      const json = await res.json();
      if (!json.success) return;
      const s = json.data.summary;
      document.getElementById("summary").innerHTML = `
        <div class="bg-slate-50 rounded-lg p-3">Share: ${f.format(s.total_share_received)}</div>
        <div class="bg-slate-50 rounded-lg p-3">Used: ${f.format(s.total_used)}</div>
        <div class="bg-slate-50 rounded-lg p-3">Remaining: ${f.format(s.remaining_balance)}</div>
        <div class="bg-slate-50 rounded-lg p-3">Receivable: ${f.format(s.receivable_from_company)}</div>
      `;
      document.getElementById("timeline").innerHTML = json.data.timeline.map((r) => `<li class="border-b pb-1">${r.created_at} | +${f.format(r.credit)} / -${f.format(r.debit)}${r.note ? ` | ${r.note}` : ""}</li>`).join("") || "<li>No entries</li>";
    }
    document.getElementById("range").addEventListener("change", load);
    load();
  </script>
</body>
</html>
