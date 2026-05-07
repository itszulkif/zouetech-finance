<?php
declare(strict_types=1);

$activePage = $activePage ?? 'dashboard';
$nav = [
    'dashboard' => ['label' => 'Dashboard', 'href' => app_url('/public/dashboard.php')],
    'income' => ['label' => 'Income Management', 'href' => app_url('/public/income.php')],
    'expenses' => ['label' => 'Expense Tracker', 'href' => app_url('/public/expenses.php')],
    'partners' => ['label' => 'Partner Center', 'href' => app_url('/public/partners.php')],
    'reports' => ['label' => 'Reports', 'href' => app_url('/public/reports.php')],
];
?>
<aside id="appSidebar" class="hidden fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] bg-white border-r border-slate-200 min-h-screen lg:static lg:z-auto lg:block lg:w-64 lg:max-w-none">
  <div class="p-5 border-b border-slate-100">
    <p class="text-xs uppercase tracking-widest text-slate-400">Zouetech</p>
    <h1 class="text-xl font-bold text-slate-900">Finance ERP</h1>
  </div>
  <nav class="p-3 space-y-1">
    <?php foreach ($nav as $key => $item): ?>
      <a
        href="<?= htmlspecialchars($item['href']) ?>"
        class="block rounded-xl px-3 py-2 text-sm font-medium <?= $activePage === $key ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'text-slate-600 hover:bg-slate-50' ?>"
      >
        <?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</aside>
<div id="sidebarBackdrop" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden"></div>
