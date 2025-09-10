<?php
$activePage = 'orders';
require_once '../../PHP/db_connect.php';
require_once '../../PHP/order_functions.php';
require_once '../../PHP/order_item_functions.php';
require_once '../../PHP/user_functions.php';
require_once '../../PHP/product_functions.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order = $pdo ? getOrderById($pdo, $orderId) : null;
$message = '';

if (!$order) {
    $pageTitle = 'Order Not Found';
    include '../header.php';
    echo '<div class="flex h-screen overflow-hidden">';
    include $prefix . 'sidebar.php';
    echo '<main class="flex-1 p-6">Order not found.</main>';
    echo '</div></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && $pdo) {
    updateOrderStatus($pdo, $orderId, $_POST['status']);
    $order['Status'] = $_POST['status'];
    $message = 'Order status updated.';
}

$user = $pdo ? getUserById($pdo, $order['User_ID']) : null;
$items = $pdo ? getOrderItemsByOrderId($pdo, $orderId) : [];
$total = $pdo ? calculateOrderTotal($pdo, $orderId) : 0;

$pageTitle = 'Order Details';
$headerTitle = 'Order Details';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>
  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>

    <div class="p-6">
      <a href="ManageOrders.php" class="text-blue-500 hover:underline inline-block mb-4">&larr; Back to Orders</a>
      <?php if (!empty($message)): ?>
        <div class="mb-4 text-green-600"><?= $message; ?></div>
      <?php endif; ?>
      <h2 class="text-lg font-semibold mb-4">Order #<?= sprintf('%05d', $order['Order_ID']); ?></h2>
      <div class="bg-white p-4 rounded shadow">
        <p><strong>Date:</strong> <?= htmlspecialchars($order['Order_Date']); ?></p>
        <p><strong>Customer:</strong> <?= htmlspecialchars($user['Name'] ?? 'User ' . $order['User_ID']); ?></p>
        <p><strong>Address:</strong> <?= htmlspecialchars($user['Address'] ?? 'Address not available'); ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['Status']); ?></p>
        <form method="post" class="mt-4 flex gap-2 items-center">
          <label for="status" class="font-medium">Update Status:</label>
          <select name="status" id="status" class="border rounded px-2 py-1">
            <option value="Pending" <?= $order['Status']==='Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Shipped" <?= $order['Status']==='Shipped' ? 'selected' : ''; ?>>Shipped</option>
            <option value="Delivered" <?= $order['Status']==='Delivered' ? 'selected' : ''; ?>>Delivered</option>
          </select>
          <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded">Save</button>
        </form>

        <h3 class="text-md font-semibold mt-6 mb-2">Items</h3>
        <table class="w-full text-sm text-left">
          <thead class="border-b">
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr class="border-b">
                <td><?= htmlspecialchars($item['Name'] ?? 'Unknown Product'); ?></td>
                <td><?= (int)$item['Quantity']; ?></td>
                <td>₱ <?= number_format($item['Subtotal'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="mt-4 font-semibold">Total: ₱ <?= number_format($total, 2); ?></p>
      </div>
    </div>
  </main>
</div>
</body>
</html>
