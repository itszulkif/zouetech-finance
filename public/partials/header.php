<?php
declare(strict_types=1);
?>
<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
  <div class="px-4 py-3 md:px-6 flex items-center justify-between gap-3">
    <div>
      <p class="text-xs uppercase tracking-widest text-slate-400">Financial Operations</p>
      <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
    </div>
    <div class="flex items-center gap-2">
      <button id="logoutBtn" class="bg-slate-900 text-white rounded-lg px-3 py-1.5 text-sm">Logout</button>
    </div>
  </div>
</header>
