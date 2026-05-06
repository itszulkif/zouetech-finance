<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (isPartnerLoggedIn()) {
    header('Location: /zou-finance/public/partner-ledger.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Partner Login - Zouetech</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
  <section class="w-full max-w-md bg-white rounded-2xl shadow p-6">
    <h1 class="text-2xl font-bold">Partner Ledger Access</h1>
    <p class="text-sm text-slate-500 mt-1">Use your assigned credentials.</p>
    <form id="partnerLoginForm" class="mt-6 space-y-4">
      <input name="username" placeholder="Username" class="w-full rounded-xl border px-3 py-2" required>
      <input name="password" type="password" placeholder="Password" class="w-full rounded-xl border px-3 py-2" required>
      <button class="w-full rounded-xl bg-blue-500 text-white py-2">Sign In</button>
    </form>
    <p id="msg" class="mt-3 text-sm text-rose-600"></p>
  </section>
  <script>
    document.getElementById("partnerLoginForm").addEventListener("submit", async (e) => {
      e.preventDefault();
      const res = await fetch("/zou-finance/api/partner_login.php", { method: "POST", body: new FormData(e.target) });
      const data = await res.json();
      if (data.success) {
        window.location.href = "/zou-finance/public/partner-ledger.php";
        return;
      }
      document.getElementById("msg").textContent = data.message || "Login failed";
    });
  </script>
</body>
</html>
