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
  console.log('Fetching all notifications for admin');
  fetch('../PHP/notification_api.php?action=all')
  .then(response => {
    console.log('Fetch response received', response);
    return response.json();
  })
  .then(data => {
    console.log('Fetched data:', data);
    const list = document.getElementById('notificationsList');
    if (!Array.isArray(data) || data.length === 0) {
      console.log('No notifications returned from API');
      const li = document.createElement('li');
      li.className = 'p-4 text-sm text-gray-500';
      li.textContent = 'No notifications.';
      list.appendChild(li);
      return;
    }
    console.log(`Rendering ${data.length} notifications`);
    data.forEach((n, index) => {
      console.log(`Adding notification ${index + 1}:`, n);
      const li = document.createElement('li');
      li.className = 'p-4 text-sm hover:bg-gray-50';
      li.textContent = `${n.Message} (${n.Created_At})`;
      list.appendChild(li);
    });
    console.log('Finished rendering notifications');
  })
  .catch(err => console.error('Error fetching notifications:', err));
</script>
</body>
</html>
