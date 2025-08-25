<?php
require_once '../PHP/db_connect.php';
require_once '../PHP/notification_functions.php';

$activePage = 'notifications';
$pageTitle = 'Notifications';
$headerTitle = 'Notifications';
$bodyClass = 'dashboard-page';

$notifications = $pdo ? getAllNotifications($pdo) : [];
include 'header.php';
?>
<div class="flex min-h-screen">
  <?php include $prefix . 'sidebar.php'; ?>
  <main class="flex-1 p-6 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <div class="mt-6">
      <?php if (empty($notifications)): ?>
        <p>No notifications.</p>
      <?php else: ?>
        <ul class="bg-white rounded shadow divide-y">
          <?php foreach ($notifications as $notif): ?>
            <li class="p-4">
              <div class="font-medium"><?= htmlspecialchars($notif['Message']); ?></div>
              <div class="text-sm text-gray-500"><?= htmlspecialchars($notif['Created_At']); ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
