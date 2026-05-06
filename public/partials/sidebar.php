<?php
declare(strict_types=1);

$activePage = $activePage ?? 'dashboard';
$nav = [
    'dashboard' => ['label' => 'Dashboard', 'href' => '/zou-finance/public/dashboard.php'],
    'income' => ['label' => 'Income Management', 'href' => '/zou-finance/public/income.php'],
    'expenses' => ['label' => 'Expense Tracker', 'href' => '/zou-finance/public/expenses.php'],
    'partners' => ['label' => 'Partner Center', 'href' => '/zou-finance/public/partners.php'],
    'reports' => ['label' => 'Reports', 'href' => '/zou-finance/public/reports.php'],
];
?>
<aside class="w-full lg:w-64 bg-white border-r border-slate-200 min-h-screen">
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
