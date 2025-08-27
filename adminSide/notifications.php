<?php
$activePage = 'notifications';
$pageTitle = 'Notifications';
$headerTitle = 'Notifications';
$bodyClass = 'dashboard-page';
include 'header.php';
?>
<div class="flex min-h-screen">
  <?php include $prefix . 'sidebar.php'; ?>
  <main class="flex-1 p-6 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <div class="mt-6">
      <h2 class="text-lg font-semibold mb-4">All Notifications</h2>
      <ul id="notificationsList" class="bg-white rounded shadow divide-y"></ul>
    </div>
  </main>
</div>
<script>
  fetch('../PHP/notification_api.php?action=all')
  .then(response => response.json())
  .then(data => {
    const list = document.getElementById('notificationsList');
      if (!Array.isArray(data) || data.length === 0) {
        const li = document.createElement('li');
        li.className = 'p-4 text-sm text-gray-500';
        li.textContent = 'No notifications.';
        list.appendChild(li);
        return;
      }
    data.forEach(n => {
      const li = document.createElement('li');
      li.className = 'p-4 text-sm hover:bg-gray-50';
      li.textContent = `${n.Message} (${n.Created_At})`;
      list.appendChild(li);
    });
  })
  .catch(err => console.error('Error fetching notifications:', err));
</script>
</body>
</html>
