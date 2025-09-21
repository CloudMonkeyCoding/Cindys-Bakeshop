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
$topProductName = null;
$topProductQty = null;
$monthlySales = [];
$categoryRevenueShare = [];
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
    $topProductName = is_array($topProduct) ? ($topProduct['Name'] ?? null) : null;
    $topProductQty = is_array($topProduct) && isset($topProduct['total_qty']) ? (int)$topProduct['total_qty'] : null;

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

    $categoryExpression = "COALESCE(NULLIF(TRIM(p.Category), ''), 'Uncategorized')";
    $statusFilter = ['Delivered', 'Completed'];
    $statusList = implode(',', array_map([$pdo, 'quote'], $statusFilter));

    $categoryQuery = "
        SELECT
            $categoryExpression AS category_name,
            COALESCE(SUM(oi.Subtotal), 0) AS total_revenue
        FROM order_item oi
        JOIN product p ON oi.Product_ID = p.Product_ID
        JOIN `order` o ON o.Order_ID = oi.Order_ID
        WHERE o.Status IN ($statusList)
        GROUP BY $categoryExpression
        ORDER BY total_revenue DESC
        LIMIT 5
    ";

    $stmtCategory = $pdo->query($categoryQuery);
    if ($stmtCategory) {
        $categoryRevenueShare = $stmtCategory->fetchAll(PDO::FETCH_ASSOC);
    }

    $categoryRevenueShare = array_values(array_filter($categoryRevenueShare, function ($row) {
        return isset($row['total_revenue']) && (float)$row['total_revenue'] > 0;
    }));

    if (empty($categoryRevenueShare)) {
        $fallbackQuery = "
            SELECT
                $categoryExpression AS category_name,
                COALESCE(SUM(oi.Subtotal), 0) AS total_revenue
            FROM order_item oi
            JOIN product p ON oi.Product_ID = p.Product_ID
            JOIN `order` o ON o.Order_ID = oi.Order_ID
            GROUP BY $categoryExpression
            ORDER BY total_revenue DESC
            LIMIT 5
        ";
        $stmtFallback = $pdo->query($fallbackQuery);
        if ($stmtFallback) {
            $categoryRevenueShare = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
            $categoryRevenueShare = array_values(array_filter($categoryRevenueShare, function ($row) {
                return isset($row['total_revenue']) && (float)$row['total_revenue'] > 0;
            }));
        }
    }

    $stmtRecent = $pdo->query("SELECT o.Order_ID, o.Order_Date, o.Status, u.Name, COALESCE(SUM(oi.Subtotal),0) AS Total FROM `order` o LEFT JOIN user u ON o.User_ID = u.User_ID LEFT JOIN order_item oi ON oi.Order_ID = o.Order_ID GROUP BY o.Order_ID, o.Order_Date, o.Status, u.Name ORDER BY o.Order_Date DESC, o.Order_ID DESC LIMIT 6");
    if ($stmtRecent) {
        $recentOrders = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);
    }
}

$salesLabels = json_encode(!empty($monthlySales) ? array_column($monthlySales, 'label') : ['No Data']);
$salesValues = json_encode(!empty($monthlySales) ? array_map(function ($item) { return round($item['value'], 2); }, $monthlySales) : [0]);
$categoryLabels = json_encode(!empty($categoryRevenueShare) ? array_map(function ($item) {
    return $item['category_name'] ?? 'Uncategorized';
}, $categoryRevenueShare) : ['No Data']);
$categoryValues = json_encode(!empty($categoryRevenueShare) ? array_map(function ($item) {
    return round((float)($item['total_revenue'] ?? 0), 2);
}, $categoryRevenueShare) : [0]);

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

  <section class="cards">
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($totalOrders); ?></h3>
        <p>Total orders recorded</p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($pendingOrders); ?></h3>
        <p>Orders awaiting preparation</p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-truck" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($deliveredOrders); ?></h3>
        <p>Completed deliveries</p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3>₱<?= number_format($totalRevenue, 2); ?></h3>
        <p>Gross revenue to date</p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($totalUsers); ?></h3>
        <p>Registered customers</p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-box-open" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($lowStockCount); ?></h3>
        <p>Items at or below threshold</p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-crown" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= $topProductName ? htmlspecialchars($topProductName) : 'No data'; ?></h3>
        <p><?= $topProductQty !== null ? 'Sold ' . number_format($topProductQty) . ' pcs' : 'Top product performance'; ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-file-export" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3>Reports</h3>
        <p>Generate PDF summaries</p>
        <a href="report.php" class="card-link">View reports <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
      </div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top: 24px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Monthly Sales</h2>
      <canvas id="salesChart" height="220"></canvas>
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
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Top Category Revenue Share</h2>
      <?php if (empty($categoryRevenueShare)): ?>
        <p class="table-empty">No revenue recorded by category yet.</p>
      <?php else: ?>
        <canvas id="categoryChart" height="220"></canvas>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
  const salesLabels = $salesLabels;
  const salesValues = $salesValues;
  const categoryLabels = $categoryLabels;
  const categoryValues = $categoryValues;

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

  if (document.getElementById('categoryChart')) {
    const categoryCanvas = document.getElementById('categoryChart');
    const categoryCtx = categoryCanvas.getContext('2d');
    const labelsData = Array.isArray(categoryLabels) ? categoryLabels : [];
    const valuesData = Array.isArray(categoryValues) ? categoryValues : [];
    const categoryChartLabels = labelsData.length ? labelsData : ['No Data'];
    const categoryChartValues = valuesData.length ? valuesData : [0];
    const palette = ['#e74c3c', '#f39c12', '#3498db', '#2ecc71', '#9b59b6', '#16a085', '#e67e22'];
    const barColors = categoryChartLabels.map((_, index) => palette[index % palette.length]);
    const totalCategoryRevenue = categoryChartValues.reduce((sum, value) => sum + Number(value || 0), 0);

    new Chart(categoryCtx, {
      type: 'bar',
      data: {
        labels: categoryChartLabels,
        datasets: [{
          label: 'Revenue (₱)',
          data: categoryChartValues,
          backgroundColor: barColors,
          borderRadius: 8,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (context) {
                const numericValue = Number(context.raw || 0);
                const formattedValue = numericValue.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                if (totalCategoryRevenue > 0) {
                  const percentage = (numericValue / totalCategoryRevenue) * 100;
                  return '₱' + formattedValue + ' (' + percentage.toFixed(1) + '%)';
                }
                return '₱' + formattedValue;
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
</script>
JS;
include 'includes/footer.php';
