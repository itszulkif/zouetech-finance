<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) {
    header('Location: /zou-finance/public/dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zouetech Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">
  <main class="min-h-screen flex items-center justify-center p-4">
    <section class="w-full max-w-md bg-white rounded-2xl shadow-xl p-6">
      <h1 class="text-2xl font-bold">Zouetech Admin</h1>
      <p class="text-sm text-slate-500 mt-1">Secure login for finance operations.</p>
      <form id="loginForm" class="mt-6 space-y-4">
        <label class="block">
          <span class="text-sm">Username</span>
          <input name="username" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
        </label>
        <label class="block">
          <span class="text-sm">Password</span>
          <input name="password" type="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" required>
        </label>
        <button class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-xl font-semibold">Sign In</button>
      </form>

      <p id="msg" class="mt-4 text-sm"></p>
    </section>
  </main>
  <script>
    const msg = document.getElementById('msg');
    const show = (t, ok = true) => {
      msg.className = `mt-4 text-sm ${ok ? 'text-emerald-600' : 'text-rose-600'}`;
      msg.textContent = t;
    };

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const res = await fetch('/zou-finance/api/login.php', { method: 'POST', body: new FormData(e.target) });
      const json = await res.json();
      if (json.success) {
        window.location.href = '/zou-finance/public/dashboard.php';
        return;
      }
      show(json.message, false);
    });

  </script>
</body>
</html>
