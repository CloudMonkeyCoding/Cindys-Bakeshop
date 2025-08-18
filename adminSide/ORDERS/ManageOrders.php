
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manage Orders</title>
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

    $orders = [];
    if ($pdo) {
        $orders = getAllOrders($pdo);
    } else {
        error_log('Database connection failed in ManageOrders.php');
    }
    ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
      <div class="header-bar">
        <h1>Orders</h1>
        <div class="flex gap-4 items-center">
          <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
          </svg>
          <img src="avatar.png" alt="User Avatar" class="h-10 w-10 rounded-full border border-gray-300" />
        </div>
      </div>

      <!-- Orders Section -->
      <div class="p-6">
        <h2 class="text-lg font-semibold mb-4">Manage Orders</h2>
        <div class="bg-white p-4 rounded shadow">
          <div class="flex items-center gap-4 mb-4">
            <input type="text" id="searchOrder" placeholder="Search Order ID" class="border rounded px-2 py-1 text-sm">
            <input type="date" id="startDate" class="border rounded px-2 py-1 text-sm">
            <input type="date" id="endDate" class="border rounded px-2 py-1 text-sm">
            <a href="../../PHP/export_orders_csv.php" class="bg-gray-300 px-4 py-1 rounded text-sm">Export Orders</a>
          </div>
          <div class="flex space-x-4 mb-2 text-sm">
            <button onclick="filterOrders('all')" class="tab-active">All</button>
            <button onclick="filterOrders('pending')" class="text-blue-600">Pending</button>
            <button onclick="filterOrders('shipped')" class="text-blue-600">Shipped</button>
            <button onclick="filterOrders('delivered')" class="text-blue-600">Delivered</button>
          </div>
          <table class="table w-full text-sm text-left">
            <thead class="border-b">
              <tr>
                <th class="py-2">✓</th>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="orderTable">
            <?php foreach ($orders as $order):
              $user = $pdo ? getUserById($pdo, $order['User_ID']) : null;
              $items = $pdo ? getOrderItemsByOrderId($pdo, $order['Order_ID']) : [];
              $itemCount = count($items);
              $total = $pdo ? calculateOrderTotal($pdo, $order['Order_ID']) : 0;
              $status = strtolower($order['Status']);
              $statusClass = '';
              if ($status === 'pending') { $statusClass = 'text-yellow-500'; }
              elseif ($status === 'shipped') { $statusClass = 'text-blue-500'; }
              elseif ($status === 'delivered') { $statusClass = 'text-green-500'; }
            ?>
              <tr data-status="<?= $status ?>" data-date="<?= htmlspecialchars($order['Order_Date']); ?>">
                <td><input type="checkbox"></td>
                <td><?= sprintf('%05d', $order['Order_ID']); ?></td>
                <td><?= htmlspecialchars($order['Order_Date']); ?></td>
                <td><?= htmlspecialchars($user['Name'] ?? 'User '.$order['User_ID']); ?></td>
                <td><?= $itemCount; ?> item<?= $itemCount === 1 ? '' : 's'; ?></td>
                <td>₱<?= number_format($total ?? 0, 2); ?></td>
                <td class="<?= $statusClass ?> font-medium"><?= htmlspecialchars($order['Status']); ?></td>
                <td><a href="OrderDetails.php?order_id=<?= $order['Order_ID']; ?>" class="text-blue-500 hover:underline">View</a></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Sidebar Script -->
  <script>
    let currentStatus = 'all';

    function filterOrders(status) {
      currentStatus = status;
      applyFilters();
    }

    function applyFilters() {
      const start = document.getElementById('startDate').value;
      const end = document.getElementById('endDate').value;
      const rows = document.querySelectorAll('#orderTable tr');
      rows.forEach(row => {
        const rowStatus = row.dataset.status;
        const rowDate = row.dataset.date;
        const statusMatch = currentStatus === 'all' || rowStatus === currentStatus;
        const afterStart = !start || rowDate >= start;
        const beforeEnd = !end || rowDate <= end;
        if (statusMatch && afterStart && beforeEnd) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }

    document.getElementById('startDate').addEventListener('change', applyFilters);
    document.getElementById('endDate').addEventListener('change', applyFilters);
  </script>
</body>
</html>
