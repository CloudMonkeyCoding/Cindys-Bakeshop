<?php
$headerTitle = $headerTitle ?? ($pageTitle ?? '');
$avatarSrc = $avatarSrc ?? 'https://i.imgur.com/1Q2Z1ZL.png';
?>
<div class="header-bar">
  <h1><?= htmlspecialchars($headerTitle); ?></h1>
  <div class="flex gap-4 items-center">
    <div class="relative">
      <button onclick="toggleDropdown()" class="relative focus:outline-none">
        <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute top-0 right-0 block h-2 w-2 bg-red-600 rounded-full"></span>
      </button>
      <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded shadow-lg z-50">
        <div class="p-3 border-b font-semibold text-gray-700">Notifications</div>
        <ul class="max-h-60 overflow-y-auto text-sm" id="notifList"></ul>
      </div>
    </div>
    <img src="<?= htmlspecialchars($avatarSrc); ?>" alt="User Avatar" class="h-10 w-10 rounded-full border border-gray-300" />
  </div>
</div>
<script>
console.log('Topbar notification script initialized');

function toggleDropdown() {
  const dropdown = document.getElementById('notificationDropdown');
  console.log('toggleDropdown called');
  if (dropdown) {
    dropdown.classList.toggle('hidden');
    console.log('Dropdown now', dropdown.classList.contains('hidden') ? 'hidden' : 'visible');
  } else {
    console.warn('notificationDropdown element not found');
  }
}

window.addEventListener('click', function (e) {
  const button = document.querySelector('button[onclick="toggleDropdown()"]');
  const dropdown = document.getElementById('notificationDropdown');
  if (button && dropdown && !button.contains(e.target) && !dropdown.contains(e.target)) {
    console.log('Click outside dropdown detected, hiding notification menu');
    dropdown.classList.add('hidden');
  }
});
</script>
