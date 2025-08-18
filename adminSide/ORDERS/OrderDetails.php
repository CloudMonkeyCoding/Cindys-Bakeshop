<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Order Details</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <div class="flex h-screen overflow-hidden">

    <?php
    $activePage = 'orders';
    include '../sidebar.php';

    require_once '../../PHP/db_connect.php';
    require_once '../../PHP/order_functions.php';
    require_once '../../PHP/order_item_functions.php';
    require_once '../../PHP/user_functions.php';
    require_once '../../PHP/product_functions.php';

    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    $order = $pdo ? getOrderById($pdo, $orderId) : null;
    if (!$order) {
        echo '<main class="flex-1 p-6">Order not found.</main>';
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
    ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
      <div class="header-bar">
        <h1>Order Details</h1>
        <div class="flex gap-4 items-center">
          <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
          <img src="avatar.png" alt="User Avatar" class="h-10 w-10 rounded-full border border-gray-300" />
        </div>
      </div>

      <div class="p-6">
        <?php if (!empty($message)): ?>
          <div class="mb-4 text-green-600"><?= $message; ?></div>
        <?php endif; ?>
        <h2 class="text-lg font-semibold mb-4">Order #<?= sprintf('%05d', $order['Order_ID']); ?></h2>
        <div class="bg-white p-4 rounded shadow">
          <p><strong>Date:</strong> <?= htmlspecialchars($order['Order_Date']); ?></p>
          <p><strong>Customer:</strong> <?= htmlspecialchars($user['Name'] ?? 'User ' . $order['User_ID']); ?></p>
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
            <?php foreach ($items as $item):
              $product = getProductById($pdo, $item['Product_ID']);
            ?>
              <tr>
                <td><?= htmlspecialchars($product['Name'] ?? 'Product ' . $item['Product_ID']); ?></td>
                <td><?= (int)$item['Quantity']; ?></td>
                <td>₱<?= number_format($item['Subtotal'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <p class="text-right font-semibold mt-4">Total: ₱<?= number_format($total ?? 0, 2); ?></p>
          <div class="mt-4 flex gap-2">
            <button onclick="window.print()" class="bg-gray-300 px-4 py-1 rounded text-sm">Print Invoice</button>
            <a href="ManageOrders.php" class="bg-gray-300 px-4 py-1 rounded text-sm">Back</a>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
