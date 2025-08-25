<?php
$activePage = $activePage ?? '';
$scriptDir = dirname($_SERVER['PHP_SELF']);
$adminDir = '/' . basename(__DIR__);
$subPath = trim(substr($scriptDir, strlen($adminDir)), '/');
$depth = $subPath === '' ? 0 : substr_count($subPath, '/') + 1;
$prefix = str_repeat('../', $depth);
?>
<aside class="w-64 bg-white border-r border-gray-200 p-4 overflow-y-auto">
  <div class="text-center mb-6">
    <img src="<?= $prefix ?>images/cindy's logo.png" alt="CINDY'S" class="mx-auto">
    <p class="text-sm text-red-600 font-semibold mt-2">Give your sweet tooth a treat</p>
  </div>
  <nav class="space-y-2 text-sm font-medium">
    <a href="<?= $prefix ?>dashboard/admin_dash.php" class="flex items-center gap-2 p-2 rounded <?php echo $activePage === 'dashboard' ? 'bg-gray-200 font-semibold' : 'sidebar-link'; ?>">🏠 Dashboard</a>

    <a href="<?= $prefix ?>notifications.php" class="flex items-center gap-2 p-2 rounded <?php echo $activePage === 'notifications' ? 'bg-gray-200 font-semibold' : 'sidebar-link'; ?>">🔔 Notifications</a>

    <!-- Orders -->
    <div class="menu">
      <a href="javascript:void(0)" onclick="toggleMenu(this)" class="flex items-center gap-2 p-2 rounded sidebar-link">📦 Orders</a>
      <div class="submenu hidden ml-6 space-y-1">
        <a href="<?= $prefix ?>ORDERS/ManageOrders.php" class="block p-2 hover:bg-gray-100 rounded">Manage Orders</a>
        <a href="<?= $prefix ?>ORDERS/ManageCancel.php" class="block p-2 hover:bg-gray-100 rounded">Manage Cancellations</a>
      </div>
    </div>

    <!-- Products -->
    <div class="menu">
      <a href="javascript:void(0)" onclick="toggleMenu(this)" class="flex items-center gap-2 p-2 rounded sidebar-link">🛒 Products</a>
      <div class="submenu hidden ml-6 space-y-1">
        <a href="<?= $prefix ?>products/ManageProduct.php" class="block p-2 hover:bg-gray-100 rounded">Manage Products</a>
        <a href="<?= $prefix ?>dashboard/Ratings.php" class="block p-2 hover:bg-gray-100 rounded">Product Ratings</a>
      </div>
    </div>

    <a href="<?= $prefix ?>dashboard/user.php" class="flex items-center gap-2 p-2 rounded sidebar-link">👥 Users</a>

    <!-- Reports -->
    <div class="menu">
      <a href="javascript:void(0)" onclick="toggleMenu(this)" class="flex items-center gap-2 p-2 rounded sidebar-link">📈 Reports</a>
      <div class="submenu hidden ml-6 space-y-1">
        <a href="<?= $prefix ?>Reports/FinanceSalesReport.php" class="block p-2 hover:bg-gray-100 rounded">Finance & Sales Report</a>
        <a href="<?= $prefix ?>Reports/InventoryReport.php" class="block p-2 hover:bg-gray-100 rounded">Inventory Report</a>
      </div>
    </div>
    <a href="<?= $prefix ?>reset_database.php" class="flex items-center gap-2 p-2 rounded <?php echo $activePage === 'reset' ? 'bg-gray-200 font-semibold' : 'sidebar-link'; ?>">🗑️ Reset Database</a>
  </nav>
</aside>

<script>
  function toggleMenu(element) {
    const parent = element.parentElement;
    const submenu = parent.querySelector('.submenu');
    submenu.classList.toggle('hidden');
    submenu.style.display = submenu.classList.contains('hidden') ? 'none' : 'block';
  }
</script>
