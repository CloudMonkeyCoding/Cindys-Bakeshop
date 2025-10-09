<?php
$activePage = $activePage ?? '';
$adminSession = $adminSession ?? [];
$isSuperAdmin = !empty($adminSession['is_super_admin']);
function isActive($page, $activePage) {
    return $page === $activePage ? 'active' : '';
}
function isDropdownActive(array $pages, $activePage) {
    return in_array($activePage, $pages, true) ? 'active' : '';
}
?>
<div class="sidebar">
  <div class="sidebar-logo">
    <img src="Cindys.png" alt="Cindy's Bakeshop Logo">
  </div>
  <ul>
    <li>
      <a href="dashboard.php" class="<?= isActive('dashboard', $activePage); ?>">
        <span><i class="fa-solid fa-chart-pie"></i></span>
        Dashboard
      </a>
    </li>
    <li>
      <a href="users.php" class="<?= isActive('users', $activePage); ?>">
        <span><i class="fa-solid fa-users"></i></span>
        Users
      </a>
    </li>
    <li>
      <a href="shifts.php" class="<?= isActive('shifts', $activePage); ?>">
        <span><i class="fa-solid fa-clock"></i></span>
        Shift Management
      </a>
    </li>
    <li>
      <a href="products.php" class="<?= isActive('products', $activePage); ?>">
        <span><i class="fa-solid fa-box-open"></i></span>
        Products
      </a>
    </li>
    <li class="dropdown <?= isDropdownActive(['orders', 'delivery', 'walkin-order'], $activePage); ?>">
      <a href="#">
        <span><i class="fa-solid fa-cart-shopping"></i></span>
        Orders
        <i class="fa-solid fa-caret-down" style="margin-left:auto;"></i>
      </a>
      <ul class="submenu">
        <li>
          <a href="walkin-order.php" class="<?= isActive('walkin-order', $activePage); ?>">
            <i class="fa-solid fa-cart-plus"></i>
            New Order
          </a>
        </li>
        <li>
          <a href="orders.php" class="<?= isActive('orders', $activePage); ?>">
            <i class="fa-solid fa-list-check"></i>
            Manage Orders
          </a>
        </li>
        <li>
          <a href="delivery.php" class="<?= isActive('delivery', $activePage); ?>">
            <i class="fa-solid fa-truck"></i>
            Delivery
          </a>
        </li>
      </ul>
    </li>
    <li class="dropdown <?= isDropdownActive(['inventory-report', 'financial-report', 'product-sales-report'], $activePage); ?>">
      <a href="#">
        <span><i class="fa-solid fa-chart-line"></i></span>
        Reports
        <i class="fa-solid fa-caret-down" style="margin-left:auto;"></i>
      </a>
      <ul class="submenu">
        <li>
          <a href="report.php" class="<?= isActive('inventory-report', $activePage); ?>">
            <i class="fa-solid fa-boxes-stacked"></i>
            Inventory Report
          </a>
        </li>
        <?php if ($isSuperAdmin): ?>
          <li>
            <a href="financial-report.php" class="<?= isActive('financial-report', $activePage); ?>">
              <i class="fa-solid fa-peso-sign"></i>
              Financial Report
            </a>
          </li>
          <li>
            <a href="product-sales-report.php" class="<?= isActive('product-sales-report', $activePage); ?>">
              <i class="fa-solid fa-chart-column"></i>
              Product Sales Report
            </a>
          </li>
        <?php endif; ?>
      </ul>
    </li>
    <li>
      <a href="notifications.php" class="<?= isActive('notifications', $activePage); ?>">
        <span><i class="fa-solid fa-bell"></i></span>
        Notifications
      </a>
    </li>
    <?php if ($isSuperAdmin): ?>
      <li>
        <a href="logs.php" class="<?= isActive('logs', $activePage); ?>">
          <span><i class="fa-solid fa-clipboard-list"></i></span>
          Activity Logs
        </a>
      </li>
    <?php endif; ?>
    <li>
      <a href="settings.php" class="<?= isActive('settings', $activePage); ?>">
        <span><i class="fa-solid fa-gear"></i></span>
        Settings
      </a>
    </li>
  </ul>
</div>
<script>
  document.querySelectorAll('.sidebar .dropdown > a').forEach(menu => {
    menu.addEventListener('click', event => {
      event.preventDefault();
      menu.parentElement.classList.toggle('active');
    });
  });
</script>
