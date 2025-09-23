<?php
session_start();

require_once '../PHP/db_connect.php';
require_once '../PHP/order_functions.php';
require_once '../PHP/order_item_functions.php';
require_once '../PHP/transaction_functions.php';
require_once '../PHP/inventory_functions.php';
require_once '../PHP/user_functions.php';

$activePage = 'dashboard';
$pageTitle = "Dashboard - Cindy's Bakeshop";
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

$timeframeOptions = [
    'last_7_days' => 'Last 7 Days',
    'last_30_days' => 'Last 30 Days',
    'custom' => 'Custom Range'
];

function sanitizeDateValue($value)
{
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

    return ($date && $date->format('Y-m-d') === $value) ? $value : null;
}

function resolveDashboardDateRange($timeframe, $customStart, $customEnd)
{
    $today = new DateTimeImmutable('today');
    $lastThirtyStart = $today->modify('-29 days');

    switch ($timeframe) {
        case 'last_7_days':
            return [
                'start' => $today->modify('-6 days'),
                'end' => $today,
                'timeframe' => 'last_7_days',
                'customStart' => null,
                'customEnd' => null,
            ];
        case 'last_30_days':
            return [
                'start' => $lastThirtyStart,
                'end' => $today,
                'timeframe' => 'last_30_days',
                'customStart' => null,
                'customEnd' => null,
            ];
        case 'custom':
            $start = $customStart ? DateTimeImmutable::createFromFormat('!Y-m-d', $customStart) : null;
            $end = $customEnd ? DateTimeImmutable::createFromFormat('!Y-m-d', $customEnd) : null;

            if ($start && $end) {
                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }

                return [
                    'start' => $start,
                    'end' => $end,
                    'timeframe' => 'custom',
                    'customStart' => $start->format('Y-m-d'),
                    'customEnd' => $end->format('Y-m-d'),
                ];
            }
            break;
    }

    return [
        'start' => $lastThirtyStart,
        'end' => $today,
        'timeframe' => 'last_30_days',
        'customStart' => null,
        'customEnd' => null,
    ];
}

function getDateColumnIfExists(PDO $pdo, $table, array $candidates)
{
    static $cache = [];

    $cacheKey = $table . '|' . implode(',', $candidates);
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    if (empty($candidates)) {
        $cache[$cacheKey] = null;
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($candidates), '?'));
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN ($placeholders) LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge([$table], $candidates));
    $column = $stmt->fetchColumn() ?: null;

    $cache[$cacheKey] = $column;

    return $column;
}

$selectedTimeframe = $_SESSION['dashboard_timeframe'] ?? 'last_30_days';
if (isset($_GET['timeframe']) && is_string($_GET['timeframe']) && isset($timeframeOptions[$_GET['timeframe']])) {
    $selectedTimeframe = $_GET['timeframe'];
}

$requestedCustomStart = isset($_GET['start_date']) ? sanitizeDateValue($_GET['start_date']) : null;
$requestedCustomEnd = isset($_GET['end_date']) ? sanitizeDateValue($_GET['end_date']) : null;

$sessionCustomStart = isset($_SESSION['dashboard_custom_start']) ? sanitizeDateValue($_SESSION['dashboard_custom_start']) : null;
$sessionCustomEnd = isset($_SESSION['dashboard_custom_end']) ? sanitizeDateValue($_SESSION['dashboard_custom_end']) : null;

$customStartInput = $requestedCustomStart ?? $sessionCustomStart;
$customEndInput = $requestedCustomEnd ?? $sessionCustomEnd;

$range = resolveDashboardDateRange($selectedTimeframe, $customStartInput, $customEndInput);

$rangeStart = $range['start'];
$rangeEnd = $range['end'];
$selectedTimeframe = $range['timeframe'];
$customRangeStart = $range['customStart'];
$customRangeEnd = $range['customEnd'];

$_SESSION['dashboard_timeframe'] = $selectedTimeframe;
$_SESSION['dashboard_range_start'] = $rangeStart->format('Y-m-d');
$_SESSION['dashboard_range_end'] = $rangeEnd->format('Y-m-d');
if ($selectedTimeframe === 'custom') {
    $_SESSION['dashboard_custom_start'] = $customRangeStart;
    $_SESSION['dashboard_custom_end'] = $customRangeEnd;
}

$rangeStartFormatted = $rangeStart->format('Y-m-d');
$rangeEndFormatted = $rangeEnd->format('Y-m-d');
$rangeDisplay = $rangeStart->format('M d, Y') . ' – ' . $rangeEnd->format('M d, Y');
$rangeDays = (int)$rangeStart->diff($rangeEnd)->format('%a') + 1;
$salesGranularity = $rangeDays <= 31 ? 'daily' : 'monthly';
$salesGranularityLabel = $salesGranularity === 'daily' ? 'Daily' : 'Monthly';
$salesChartTitle = $salesGranularity === 'daily' ? 'Sales Trend (Daily)' : 'Sales Trend (Monthly)';
$timeframeLabel = $timeframeOptions[$selectedTimeframe] ?? 'Custom Range';
$customStartValue = $customRangeStart ?? ($sessionCustomStart ?? '');
$customEndValue = $customRangeEnd ?? ($sessionCustomEnd ?? '');
$showCustomRange = $selectedTimeframe === 'custom';

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
$userFilterApplied = false;
$inventoryFilterApplied = false;

if ($pdo) {
    $totalOrders = countOrders($pdo, $rangeStartFormatted, $rangeEndFormatted);
    $pendingOrders = count(getOrdersByStatus($pdo, 'Pending', $rangeStartFormatted, $rangeEndFormatted));
    $deliveredOrders = count(getOrdersByStatus($pdo, 'Delivered', $rangeStartFormatted, $rangeEndFormatted));

    $stmtRevenue = $pdo->prepare("SELECT COALESCE(SUM(Amount_Paid), 0) FROM transaction WHERE Payment_Date IS NOT NULL AND Payment_Date BETWEEN :start_date AND :end_date");
    $stmtRevenue->execute([
        ':start_date' => $rangeStartFormatted,
        ':end_date' => $rangeEndFormatted,
    ]);
    $totalRevenue = (float)$stmtRevenue->fetchColumn();

    $userDateColumn = $pdo ? getDateColumnIfExists($pdo, 'user', [
        'Created_At',
        'CreatedAt',
        'Registration_Date',
        'Registered_On',
        'Date_Created',
        'DateCreated',
        'Joined_At',
    ]) : null;
    $totalUsers = (int)countUsers($pdo, $rangeStartFormatted, $rangeEndFormatted, $userDateColumn);
    $userFilterApplied = (bool)$userDateColumn;

    $inventoryThreshold = 'i.Stock_Quantity IS NULL OR i.Stock_Quantity <= 10';
    $inventoryDateColumn = $pdo ? getDateColumnIfExists($pdo, 'inventory', [
        'Updated_At',
        'UpdatedAt',
        'Last_Updated',
        'LastUpdated',
        'Created_At',
        'CreatedAt',
        'Date_Added',
        'DateAdded',
    ]) : null;

    if ($inventoryDateColumn) {
        $inventoryDateClause = sprintf('i.`%s` BETWEEN :start_date AND :end_date', $inventoryDateColumn);
        $stmtLowStockCount = $pdo->prepare("SELECT COUNT(*) FROM inventory i WHERE ($inventoryThreshold) AND $inventoryDateClause");
        $stmtLowStockCount->execute([
            ':start_date' => $rangeStartFormatted,
            ':end_date' => $rangeEndFormatted,
        ]);
        $lowStockCount = (int)$stmtLowStockCount->fetchColumn();

        $stmtLowStock = $pdo->prepare("
            SELECT p.Name, i.Stock_Quantity
            FROM inventory i
            JOIN product p ON i.Product_ID = p.Product_ID
            WHERE ($inventoryThreshold) AND $inventoryDateClause
            ORDER BY i.Stock_Quantity ASC, p.Name ASC
            LIMIT 6
        ");
        $stmtLowStock->execute([
            ':start_date' => $rangeStartFormatted,
            ':end_date' => $rangeEndFormatted,
        ]);
        $lowStockProducts = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);
        $inventoryFilterApplied = true;
    } else {
        $stmtLowStockCount = $pdo->query("SELECT COUNT(*) FROM inventory i WHERE $inventoryThreshold");
        $lowStockCount = (int)($stmtLowStockCount ? $stmtLowStockCount->fetchColumn() : 0);

        $stmtLowStock = $pdo->query("
            SELECT p.Name, i.Stock_Quantity
            FROM inventory i
            JOIN product p ON i.Product_ID = p.Product_ID
            WHERE $inventoryThreshold
            ORDER BY i.Stock_Quantity ASC, p.Name ASC
            LIMIT 6
        ");
        $lowStockProducts = $stmtLowStock ? $stmtLowStock->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    $stmtTopProduct = $pdo->prepare("
        SELECT p.Name, SUM(oi.Quantity) AS total_qty
        FROM order_item oi
        JOIN product p ON oi.Product_ID = p.Product_ID
        JOIN `order` o ON oi.Order_ID = o.Order_ID
        WHERE o.Order_Date BETWEEN :start_date AND :end_date
        GROUP BY p.Product_ID, p.Name
        ORDER BY total_qty DESC
        LIMIT 1
    ");
    $stmtTopProduct->execute([
        ':start_date' => $rangeStartFormatted,
        ':end_date' => $rangeEndFormatted,
    ]);
    $topProduct = $stmtTopProduct->fetch(PDO::FETCH_ASSOC) ?: null;
    $topProductName = is_array($topProduct) ? ($topProduct['Name'] ?? null) : null;
    $topProductQty = is_array($topProduct) && isset($topProduct['total_qty']) ? (int)$topProduct['total_qty'] : null;

    if ($salesGranularity === 'daily') {
        $stmtDaily = $pdo->prepare("
            SELECT DATE(Payment_Date) AS period, COALESCE(SUM(Amount_Paid), 0) AS total
            FROM transaction
            WHERE Payment_Date IS NOT NULL
              AND Payment_Date BETWEEN :start_date AND :end_date
            GROUP BY DATE(Payment_Date)
            ORDER BY period ASC
        ");
        $stmtDaily->execute([
            ':start_date' => $rangeStartFormatted,
            ':end_date' => $rangeEndFormatted,
        ]);
        $dailyTotals = [];
        foreach ($stmtDaily->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $dailyTotals[$row['period']] = (float)$row['total'];
        }

        for ($date = $rangeStart; $date <= $rangeEnd; $date = $date->modify('+1 day')) {
            $key = $date->format('Y-m-d');
            $monthlySales[] = [
                'label' => $date->format('M j'),
                'value' => round($dailyTotals[$key] ?? 0, 2),
            ];
        }
    } else {
        $stmtMonthly = $pdo->prepare("
            SELECT DATE_FORMAT(Payment_Date, '%Y-%m') AS period, COALESCE(SUM(Amount_Paid), 0) AS total
            FROM transaction
            WHERE Payment_Date IS NOT NULL
              AND Payment_Date BETWEEN :start_date AND :end_date
            GROUP BY DATE_FORMAT(Payment_Date, '%Y-%m')
            ORDER BY period ASC
        ");
        $stmtMonthly->execute([
            ':start_date' => $rangeStartFormatted,
            ':end_date' => $rangeEndFormatted,
        ]);
        $monthlyTotals = [];
        foreach ($stmtMonthly->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $monthlyTotals[$row['period']] = (float)$row['total'];
        }

        $monthCursor = new DateTimeImmutable($rangeStart->format('Y-m-01'));
        $monthEndCursor = new DateTimeImmutable($rangeEnd->format('Y-m-01'));

        while ($monthCursor <= $monthEndCursor) {
            $key = $monthCursor->format('Y-m');
            $monthlySales[] = [
                'label' => $monthCursor->format('M Y'),
                'value' => round($monthlyTotals[$key] ?? 0, 2),
            ];
            $monthCursor = $monthCursor->modify('+1 month');
        }
    }

    $categoryExpression = "COALESCE(NULLIF(TRIM(p.Category), ''), 'Uncategorized')";
    $statusFilter = ['Delivered', 'Completed'];
    $statusPlaceholders = [];
    $categoryParams = [
        ':start_date' => $rangeStartFormatted,
        ':end_date' => $rangeEndFormatted,
    ];

    foreach ($statusFilter as $index => $status) {
        $placeholder = ':status_' . $index;
        $statusPlaceholders[] = $placeholder;
        $categoryParams[$placeholder] = $status;
    }

    $categoryQuery = "
        SELECT
            $categoryExpression AS category_name,
            COALESCE(SUM(oi.Subtotal), 0) AS total_revenue
        FROM order_item oi
        JOIN product p ON oi.Product_ID = p.Product_ID
        JOIN `order` o ON o.Order_ID = oi.Order_ID
        WHERE o.Order_Date BETWEEN :start_date AND :end_date
    ";
    if ($statusPlaceholders) {
        $categoryQuery .= ' AND o.Status IN (' . implode(',', $statusPlaceholders) . ')';
    }
    $categoryQuery .= "
        GROUP BY $categoryExpression
        ORDER BY total_revenue DESC
        LIMIT 5
    ";

    $stmtCategory = $pdo->prepare($categoryQuery);
    $stmtCategory->execute($categoryParams);
    $categoryRevenueShare = $stmtCategory->fetchAll(PDO::FETCH_ASSOC) ?: [];

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
            WHERE o.Order_Date BETWEEN :start_date AND :end_date
            GROUP BY $categoryExpression
            ORDER BY total_revenue DESC
            LIMIT 5
        ";
        $stmtFallback = $pdo->prepare($fallbackQuery);
        $stmtFallback->execute([
            ':start_date' => $rangeStartFormatted,
            ':end_date' => $rangeEndFormatted,
        ]);
        $categoryRevenueShare = $stmtFallback->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $categoryRevenueShare = array_values(array_filter($categoryRevenueShare, function ($row) {
            return isset($row['total_revenue']) && (float)$row['total_revenue'] > 0;
        }));
    }

    $stmtRecent = $pdo->prepare("
        SELECT o.Order_ID, o.Order_Date, o.Status, u.Name, COALESCE(SUM(oi.Subtotal), 0) AS Total
        FROM `order` o
        LEFT JOIN user u ON o.User_ID = u.User_ID
        LEFT JOIN order_item oi ON oi.Order_ID = o.Order_ID
        WHERE o.Order_Date BETWEEN :start_date AND :end_date
        GROUP BY o.Order_ID, o.Order_Date, o.Status, u.Name
        ORDER BY o.Order_Date DESC, o.Order_ID DESC
        LIMIT 6
    ");
    $stmtRecent->execute([
        ':start_date' => $rangeStartFormatted,
        ':end_date' => $rangeEndFormatted,
    ]);
    $recentOrders = $stmtRecent->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

$salesLabels = json_encode(!empty($monthlySales) ? array_column($monthlySales, 'label') : ['No Data']);
$salesValues = json_encode(!empty($monthlySales) ? array_map(function ($item) { return round($item['value'], 2); }, $monthlySales) : [0]);
$categoryLabels = json_encode(!empty($categoryRevenueShare) ? array_map(function ($item) {
    return $item['category_name'] ?? 'Uncategorized';
}, $categoryRevenueShare) : ['No Data']);
$categoryValues = json_encode(!empty($categoryRevenueShare) ? array_map(function ($item) {
    return round((float)($item['total_revenue'] ?? 0), 2);
}, $categoryRevenueShare) : [0]);

$ordersCardSubtitle = 'Orders placed in range';
$pendingCardSubtitle = 'Pending orders in range';
$deliveredCardSubtitle = 'Delivered orders in range';
$revenueCardSubtitle = 'Revenue collected in range';
$userCardSubtitle = $userFilterApplied ? 'Customers added in range' : 'Registered customers';
$inventoryCardSubtitle = $inventoryFilterApplied ? 'Low stock flagged in range' : 'Items at or below threshold';
$topProductSubtitle = $topProductQty !== null ? 'Sold ' . number_format($topProductQty) . ' pcs in range' : 'No product sales in range';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <div class="header-controls">
      <h1>Welcome back!</h1>
      <form id="dashboard-timeframe-form" class="timeframe-form" method="get" action="">
        <select name="timeframe" id="dashboard-timeframe" aria-label="Select timeframe">
          <?php foreach ($timeframeOptions as $key => $label): ?>
            <option value="<?= htmlspecialchars($key); ?>" <?= $selectedTimeframe === $key ? 'selected' : ''; ?>>
              <?= htmlspecialchars($label); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <div id="dashboard-custom-range" class="timeframe-custom-fields" style="<?= $showCustomRange ? '' : 'display:none;'; ?>">
          <input type="date" name="start_date" id="dashboard-start-date" value="<?= htmlspecialchars($customStartValue); ?>" <?= $showCustomRange ? '' : 'disabled'; ?> aria-label="Custom range start">
          <span class="range-separator">to</span>
          <input type="date" name="end_date" id="dashboard-end-date" value="<?= htmlspecialchars($customEndValue); ?>" <?= $showCustomRange ? '' : 'disabled'; ?> aria-label="Custom range end">
          <button type="submit" class="btn btn-primary">Apply</button>
        </div>
      </form>
      <p class="timeframe-summary">Showing <?= htmlspecialchars($timeframeLabel); ?> &middot; <?= htmlspecialchars($rangeDisplay); ?></p>
    </div>
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
        <p><?= htmlspecialchars($ordersCardSubtitle); ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($pendingOrders); ?></h3>
        <p><?= htmlspecialchars($pendingCardSubtitle); ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-truck" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($deliveredOrders); ?></h3>
        <p><?= htmlspecialchars($deliveredCardSubtitle); ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3>₱<?= number_format($totalRevenue, 2); ?></h3>
        <p><?= htmlspecialchars($revenueCardSubtitle); ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($totalUsers); ?></h3>
        <p><?= htmlspecialchars($userCardSubtitle); ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-box-open" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= number_format($lowStockCount); ?></h3>
        <p><?= htmlspecialchars($inventoryCardSubtitle); ?></p>
      </div>
    </div>
    <div class="card">
      <div class="card-icon"><i class="fa-solid fa-crown" aria-hidden="true"></i></div>
      <div class="card-info">
        <h3><?= $topProductName ? htmlspecialchars($topProductName) : 'No data'; ?></h3>
        <p><?= htmlspecialchars($topProductSubtitle); ?></p>
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
      <h2 class="chart-title"><?= htmlspecialchars($salesChartTitle); ?></h2>
      <canvas id="salesChart" height="220"></canvas>
    </div>
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
  </div>

  <div class="stats-grid columns-4" style="margin-top: 24px; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
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
  const timeframeForm = document.getElementById('dashboard-timeframe-form');
  if (timeframeForm) {
    const timeframeSelect = document.getElementById('dashboard-timeframe');
    const customContainer = document.getElementById('dashboard-custom-range');
    const startInput = document.getElementById('dashboard-start-date');
    const endInput = document.getElementById('dashboard-end-date');

    const toggleCustomFields = () => {
      const isCustom = timeframeSelect && timeframeSelect.value === 'custom';
      if (customContainer) {
        customContainer.style.display = isCustom ? 'flex' : 'none';
      }
      if (startInput) {
        startInput.disabled = !isCustom;
      }
      if (endInput) {
        endInput.disabled = !isCustom;
      }
    };

    if (timeframeSelect) {
      timeframeSelect.addEventListener('change', () => {
        const isCustom = timeframeSelect.value === 'custom';
        toggleCustomFields();
        if (!isCustom) {
          timeframeForm.submit();
        }
      });
      toggleCustomFields();
    }
  }

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
