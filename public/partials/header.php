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
      <button
        id="openProfileBtn"
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100"
        aria-label="Open profile settings"
      >
        <img id="profileAvatar" src="" alt="Profile" class="hidden h-full w-full object-cover" />
        <span id="profileAvatarFallback" class="text-xs font-semibold text-slate-700">PR</span>
      </button>
      <button id="logoutBtn" class="bg-slate-900 text-white rounded-lg px-3 py-1.5 text-sm">Logout</button>
    </div>
  </div>
</header>

<div id="profileModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 px-4">
  <div class="w-full max-w-lg rounded-2xl bg-white p-4 md:p-5">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-slate-900">Edit Profile</h3>
      <button id="closeProfileBtn" type="button" class="rounded-lg border border-slate-200 px-2.5 py-1 text-sm text-slate-600">Close</button>
    </div>
    <form id="profileForm" class="space-y-3">
      <div class="flex items-center gap-3">
        <div class="h-14 w-14 overflow-hidden rounded-full border border-slate-200 bg-slate-100">
          <img id="profilePreviewImage" src="" alt="Profile preview" class="hidden h-full w-full object-cover" />
        </div>
        <label class="text-sm font-medium text-slate-600">
          Picture
          <input id="profilePictureInput" name="picture" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm" />
        </label>
      </div>
      <label class="block text-sm font-medium text-slate-600">
        Name
        <input id="profileNameInput" name="name" type="text" maxlength="150" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
      </label>
      <label class="block text-sm font-medium text-slate-600">
        Username
        <input id="profileUsernameInput" name="username" type="text" required maxlength="100" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
      </label>
      <label class="block text-sm font-medium text-slate-600">
        New Password
        <input id="profilePasswordInput" name="password" type="password" minlength="6" placeholder="Leave blank to keep current password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2" />
      </label>
      <div class="flex flex-wrap justify-end gap-2 pt-1">
        <button id="cancelProfileBtn" type="button" class="rounded-lg border border-slate-200 px-4 py-2 text-sm">Cancel</button>
        <button id="saveProfileBtn" type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white">Save Profile</button>
      </div>
    </form>
  </div>
</div>

