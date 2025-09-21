<?php
require_once '../PHP/db_connect.php';
require_once '../PHP/order_functions.php';
require_once '../PHP/order_item_functions.php';
require_once '../PHP/transaction_functions.php';
require_once '../PHP/inventory_functions.php';
require_once '../PHP/user_functions.php';

$activePage = 'dashboard';
$pageTitle = "Dashboard - Cindy's Bakeshop";
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

$totalOrders = 0;
$pendingOrders = 0;
$deliveredOrders = 0;
$totalRevenue = 0.0;
$totalUsers = 0;
$lowStockCount = 0;
$lowStockProducts = [];
$topProduct = null;
$monthlySales = [];
$paymentBreakdown = [];
$recentOrders = [];

if ($pdo) {
    $totalOrders = countOrders($pdo);
    $pendingOrders = count(getOrdersByStatus($pdo, 'Pending'));
    $deliveredOrders = count(getOrdersByStatus($pdo, 'Delivered'));

    $stmtRevenue = $pdo->query("SELECT COALESCE(SUM(Amount_Paid), 0) FROM transaction");
    $totalRevenue = (float)($stmtRevenue ? $stmtRevenue->fetchColumn() : 0);

    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM user");
    $totalUsers = (int)($stmtUsers ? $stmtUsers->fetchColumn() : 0);

    $stmtLowStockCount = $pdo->query("SELECT COUNT(*) FROM inventory WHERE Stock_Quantity IS NULL OR Stock_Quantity <= 10");
    $lowStockCount = (int)($stmtLowStockCount ? $stmtLowStockCount->fetchColumn() : 0);

    $stmtLowStock = $pdo->query("SELECT p.Name, i.Stock_Quantity FROM inventory i JOIN product p ON i.Product_ID = p.Product_ID WHERE i.Stock_Quantity IS NULL OR i.Stock_Quantity <= 10 ORDER BY i.Stock_Quantity ASC LIMIT 6");
    $lowStockProducts = $stmtLowStock ? $stmtLowStock->fetchAll(PDO::FETCH_ASSOC) : [];

    $stmtTopProduct = $pdo->query("SELECT p.Name, SUM(oi.Quantity) AS total_qty FROM order_item oi JOIN product p ON oi.Product_ID = p.Product_ID GROUP BY p.Product_ID ORDER BY total_qty DESC LIMIT 1");
    $topProduct = $stmtTopProduct ? $stmtTopProduct->fetch(PDO::FETCH_ASSOC) : null;

    $monthlyTotals = [];
    $stmtMonthly = $pdo->query("SELECT DATE_FORMAT(Payment_Date, '%Y-%m') AS period, COALESCE(SUM(Amount_Paid), 0) AS total FROM transaction WHERE Payment_Date IS NOT NULL AND Payment_Date >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH) GROUP BY period");
    if ($stmtMonthly) {
        foreach ($stmtMonthly->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $monthlyTotals[$row['period']] = (float)$row['total'];
        }
    }

    $currentMonth = new DateTime('first day of this month');
    for ($i = 11; $i >= 0; $i--) {
        $month = (clone $currentMonth)->modify("-{$i} months");
        $periodKey = $month->format('Y-m');
        $monthlySales[] = [
            'label' => $month->format('M Y'),
            'value' => round($monthlyTotals[$periodKey] ?? 0, 2)
        ];
    }

    $stmtPayment = $pdo->query("SELECT Payment_Method, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction GROUP BY Payment_Method");
    if ($stmtPayment) {
        $paymentBreakdown = $stmtPayment->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtRecent = $pdo->query("SELECT o.Order_ID, o.Order_Date, o.Status, u.Name, COALESCE(SUM(oi.Subtotal),0) AS Total FROM `order` o LEFT JOIN user u ON o.User_ID = u.User_ID LEFT JOIN order_item oi ON oi.Order_ID = o.Order_ID GROUP BY o.Order_ID, o.Order_Date, o.Status, u.Name ORDER BY o.Order_Date DESC, o.Order_ID DESC LIMIT 6");
    if ($stmtRecent) {
        $recentOrders = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    }
}

$salesLabels = json_encode(!empty($monthlySales) ? array_column($monthlySales, 'label') : ['No Data']);
$salesValues = json_encode(!empty($monthlySales) ? array_map(function ($item) { return round($item['value'], 2); }, $monthlySales) : [0]);
$paymentLabels = json_encode(!empty($paymentBreakdown) ? array_map(function ($item) { return $item['Payment_Method'] ?: 'Unknown'; }, $paymentBreakdown) : ['No Data']);
$paymentValues = json_encode(!empty($paymentBreakdown) ? array_map(function ($item) { return round((float)$item['total'], 2); }, $paymentBreakdown) : [0]);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Welcome back!</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <section class="stats-grid columns-4">
    <div class="stat-card">
      <h3>Total Orders</h3>
      <div class="value"><?= number_format($totalOrders); ?></div>
      <div class="meta">All recorded orders</div>
    </div>
    <div class="stat-card">
      <h3>Pending Orders</h3>
      <div class="value"><?= number_format($pendingOrders); ?></div>
      <div class="meta">Awaiting preparation</div>
    </div>
    <div class="stat-card">
      <h3>Delivered Orders</h3>
      <div class="value"><?= number_format($deliveredOrders); ?></div>
      <div class="meta">Completed deliveries</div>
    </div>
    <div class="stat-card">
      <h3>Total Revenue</h3>
      <div class="value">₱<?= number_format($totalRevenue, 2); ?></div>
      <div class="meta">All payment records</div>
    </div>
  </section>

  <section class="stats-grid columns-4" style="margin-top: 24px;">
    <div class="stat-card">
      <h3>Customers</h3>
      <div class="value"><?= number_format($totalUsers); ?></div>
      <div class="meta">Registered users</div>
    </div>
    <div class="stat-card">
      <h3>Low Stock Items</h3>
      <div class="value"><?= number_format($lowStockCount); ?></div>
      <div class="meta">Items at or below threshold</div>
    </div>
    <div class="stat-card">
      <h3>Top Product</h3>
      <div class="value" style="font-size: 22px;"><?= htmlspecialchars($topProduct['Name'] ?? 'No data'); ?></div>
      <div class="meta">Sold <?= number_format($topProduct['total_qty'] ?? 0); ?> pcs</div>
    </div>
    <div class="stat-card">
      <h3>Report Exports</h3>
      <div class="value">
        <a href="report.php" class="btn btn-primary" style="text-decoration:none;color:#fff;padding:10px 16px;">View Reports</a>
      </div>
      <div class="meta">Generate PDF summaries</div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top: 24px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Monthly Sales</h2>
      <canvas id="salesChart" height="220"></canvas>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Payment Method Breakdown</h2>
      <canvas id="paymentChart" height="220"></canvas>
    </div>
  </div>

  <div class="stats-grid columns-4" style="margin-top: 24px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Low Stock Alerts</h2>
      <?php if (empty($lowStockProducts)): ?>
        <p class="table-empty">All items have sufficient stock.</p>
      <?php else: ?>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:12px;">
          <?php foreach ($lowStockProducts as $item): ?>
            <li style="display:flex;justify-content:space-between;align-items:center;">
              <span><?= htmlspecialchars($item['Name']); ?></span>
              <span class="badge badge-danger">Stock: <?= is_null($item['Stock_Quantity']) ? 'Pre-order' : number_format($item['Stock_Quantity']); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Recent Orders</h2>
      <?php if (empty($recentOrders)): ?>
        <p class="table-empty">No recent orders found.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Total</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $order): ?>
              <tr>
                <td>#<?= str_pad($order['Order_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                <td><?= htmlspecialchars($order['Name'] ?? 'Walk-in'); ?></td>
                <td>₱<?= number_format($order['Total'] ?? 0, 2); ?></td>
                <td>
                  <span class="status-pill status-<?= strtolower($order['Status']); ?>">
                    <?= htmlspecialchars($order['Status']); ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($order['Order_Date']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
  const salesLabels = $salesLabels;
  const salesValues = $salesValues;
  const paymentLabels = $paymentLabels;
  const paymentValues = $paymentValues;

  if (document.getElementById('salesChart')) {
    const salesCanvas = document.getElementById('salesChart');
    const salesCtx = salesCanvas.getContext('2d');
    const gradient = salesCtx.createLinearGradient(0, 0, 0, salesCanvas.height || 400);
    gradient.addColorStop(0, 'rgba(231, 76, 60, 0.9)');
    gradient.addColorStop(1, 'rgba(241, 196, 15, 0.9)');

    new Chart(salesCtx, {
      type: 'line',
      data: {
        labels: salesLabels,
        datasets: [{
          label: 'Sales (₱)',
          data: salesValues,
          backgroundColor: gradient,
          borderColor: '#e74c3c',
          borderWidth: 3,
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#e74c3c',
          pointRadius: 5,
          pointHoverRadius: 7
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: true,
            labels: {
              color: '#2c3e50',
              font: { size: 14 }
            }
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const value = Number(context.raw || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                return '₱' + value;
              }
            }
          }
        },
        scales: {
          x: {
            ticks: { color: '#2c3e50' },
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            ticks: {
              color: '#2c3e50',
              callback: value => '₱' + Number(value).toLocaleString()
            },
            grid: { color: 'rgba(0,0,0,0.05)' }
          }
        }
      }
    });
  }

  if (document.getElementById('paymentChart')) {
    new Chart(document.getElementById('paymentChart'), {
      type: 'doughnut',
      data: {
        labels: paymentLabels,
        datasets: [{
          data: paymentValues,
          backgroundColor: ['#e74c3c', '#f1c40f', '#3498db', '#2ecc71', '#9b59b6']
        }]
      },
      options: {
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }
</script>
JS;
include 'includes/footer.php';
