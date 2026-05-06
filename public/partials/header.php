<?php
declare(strict_types=1);
?>
<header class="bg-white border-b border-slate-200 sticky top-0 z-10">
  <div class="px-4 py-3 md:px-6 flex items-center justify-between gap-3">
    <div class="flex items-center gap-2">
      <button
        id="sidebarToggleBtn"
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 lg:hidden"
        aria-label="Open menu"
        aria-controls="appSidebar"
        aria-expanded="false"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <div>
      <p class="text-xs uppercase tracking-widest text-slate-400">Financial Operations</p>
      <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></h2>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <button id="logoutBtn" class="bg-slate-900 text-white rounded-lg px-3 py-1.5 text-sm">Logout</button>
    </div>
  </div>
</header>
