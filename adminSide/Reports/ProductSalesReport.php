<?php
$activePage = 'reports';
require_once '../../PHP/db_connect.php';

$dateFilter = $_GET['date_filter'] ?? 'last30';
$allowedDateFilters = ['today', 'last7', 'last30', 'last90', 'month', 'year', 'all'];
if (!in_array($dateFilter, $allowedDateFilters, true)) {
    $dateFilter = 'last30';
}

$sortOption = $_GET['sort'] ?? 'quantity_desc';
$sortMappings = [
    'quantity_desc' => 'total_quantity DESC, total_revenue DESC',
    'quantity_asc'  => 'total_quantity ASC, total_revenue ASC',
    'revenue_desc'  => 'total_revenue DESC, total_quantity DESC',
    'revenue_asc'   => 'total_revenue ASC, total_quantity ASC',
    'name_asc'      => 'p.Name ASC',
    'name_desc'     => 'p.Name DESC',
    'recent'        => 'last_sold DESC',
    'stale'         => 'last_sold ASC'
];
if (!array_key_exists($sortOption, $sortMappings)) {
    $sortOption = 'quantity_desc';
}

$searchTerm = trim($_GET['search'] ?? '');
$categoryFilter = $_GET['category'] ?? 'all';

$categoryOptions = [];
$salesRows = [];
$errorMessage = null;

if ($pdo) {
    try {
        $categoryStmt = $pdo->query("SELECT DISTINCT Category FROM Product WHERE Category IS NOT NULL ORDER BY Category");
        $categoryOptions = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log('Failed to fetch categories for ProductSalesReport: ' . $e->getMessage());
    }

    if ($categoryFilter !== 'all' && !in_array($categoryFilter, $categoryOptions, true)) {
        $categoryFilter = 'all';
    }

    $clauses = [];
    $params = [];

    switch ($dateFilter) {
        case 'today':
            $clauses[] = 'DATE(o.Order_Date) = CURDATE()';
            break;
        case 'last7':
            $clauses[] = 'o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
            break;
        case 'last30':
            $clauses[] = 'o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
            break;
        case 'last90':
            $clauses[] = 'o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)';
            break;
        case 'month':
            $clauses[] = 'MONTH(o.Order_Date) = MONTH(CURDATE()) AND YEAR(o.Order_Date) = YEAR(CURDATE())';
            break;
        case 'year':
            $clauses[] = 'o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)';
            break;
        case 'all':
        default:
            // No additional clause
            break;
    }

    if ($categoryFilter !== 'all') {
        $clauses[] = 'p.Category = :category';
        $params[':category'] = $categoryFilter;
    }

    if ($searchTerm !== '') {
        $clauses[] = 'p.Name LIKE :search';
        $params[':search'] = '%' . $searchTerm . '%';
    }

    $whereClause = '';
    if ($clauses) {
        $whereClause = 'WHERE ' . implode(' AND ', $clauses);
    }

    $orderClause = 'ORDER BY ' . $sortMappings[$sortOption];

    $sql = "SELECT 
                p.Product_ID,
                p.Name,
                COALESCE(p.Category, 'Uncategorized') AS Category,
                COALESCE(SUM(oi.Quantity), 0) AS total_quantity,
                COALESCE(SUM(oi.Subtotal), 0) AS total_revenue,
                COUNT(DISTINCT o.Order_ID) AS orders_count,
                COALESCE(AVG(oi.Quantity), 0) AS avg_quantity,
                MAX(o.Order_Date) AS last_sold
            FROM Order_Item oi
            INNER JOIN Product p ON oi.Product_ID = p.Product_ID
            INNER JOIN `Order` o ON oi.Order_ID = o.Order_ID
            $whereClause
            GROUP BY p.Product_ID, p.Name, Category
            $orderClause";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $salesRows = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Failed to fetch product sales data: ' . $e->getMessage());
        $errorMessage = 'Unable to load product sales data right now. Please try again later.';
    }
} else {
    $errorMessage = 'Unable to connect to the database. Please try again later.';
}

foreach ($salesRows as &$row) {
    $row['Product_ID'] = (int)$row['Product_ID'];
    $row['total_quantity'] = (int)$row['total_quantity'];
    $row['total_revenue'] = (float)$row['total_revenue'];
    $row['orders_count'] = (int)$row['orders_count'];
    $row['avg_quantity'] = (float)$row['avg_quantity'];
    $row['last_sold'] = $row['last_sold'] ?? null;
}
unset($row);

$totalQuantity = array_sum(array_column($salesRows, 'total_quantity'));
$totalRevenue = array_sum(array_column($salesRows, 'total_revenue'));
$totalOrders = array_sum(array_column($salesRows, 'orders_count'));
$distinctProducts = count($salesRows);
$averageUnitsPerOrder = $totalOrders > 0 ? $totalQuantity / $totalOrders : 0;
$averageRevenuePerOrder = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

$topProduct = null;
$topRevenueProduct = null;
$categoryBreakdown = [];

foreach ($salesRows as $row) {
    if ($topProduct === null || $row['total_quantity'] > $topProduct['total_quantity']) {
        $topProduct = $row;
    }
    if ($topRevenueProduct === null || $row['total_revenue'] > $topRevenueProduct['total_revenue']) {
        $topRevenueProduct = $row;
    }

    $categoryKey = $row['Category'] ?: 'Uncategorized';
    if (!isset($categoryBreakdown[$categoryKey])) {
        $categoryBreakdown[$categoryKey] = ['quantity' => 0, 'revenue' => 0.0];
    }
    $categoryBreakdown[$categoryKey]['quantity'] += $row['total_quantity'];
    $categoryBreakdown[$categoryKey]['revenue'] += $row['total_revenue'];
}

if ($totalQuantity > 0) {
    foreach ($salesRows as &$row) {
        $row['share'] = round(($row['total_quantity'] / $totalQuantity) * 100, 2);
    }
    unset($row);
} else {
    foreach ($salesRows as &$row) {
        $row['share'] = 0.0;
    }
    unset($row);
}

uasort($categoryBreakdown, static function ($a, $b) {
    return $b['quantity'] <=> $a['quantity'];
});

$categoryList = [];
foreach ($categoryBreakdown as $name => $data) {
    $share = $totalQuantity > 0 ? round(($data['quantity'] / $totalQuantity) * 100, 2) : 0.0;
    $categoryList[] = [
        'name' => $name,
        'quantity' => $data['quantity'],
        'revenue' => $data['revenue'],
        'share' => $share
    ];
}

$sortedForChart = $salesRows;
usort($sortedForChart, static function ($a, $b) {
    return $b['total_quantity'] <=> $a['total_quantity'];
});
$topFiveProducts = array_slice($sortedForChart, 0, 5);
$chartData = [
    'labels' => array_values(array_map(static function ($row) {
        return $row['Name'];
    }, $topFiveProducts)),
    'values' => array_values(array_map(static function ($row) {
        return $row['total_quantity'];
    }, $topFiveProducts))
];

$categoryChartSlice = array_slice($categoryList, 0, 6);
$categoryChartData = [
    'labels' => array_values(array_map(static function ($row) {
        return $row['name'];
    }, $categoryChartSlice)),
    'values' => array_values(array_map(static function ($row) {
        return $row['quantity'];
    }, $categoryChartSlice))
];

$pageTitle = 'Product Sales Report';
$headerTitle = 'Product Sales Report';
$bodyClass = 'product-sales-report';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>

  <main class="main flex-1 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <div class="px-6 py-6 space-y-6">
      <p class="text-sm text-gray-600">Track which products are selling the most and understand how categories contribute to total sales.</p>

      <form method="get" class="psr-filters">
        <div class="filter-field">
          <label for="date_filter">Time range</label>
          <select id="date_filter" name="date_filter" onchange="this.form.submit()">
            <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="last7" <?= $dateFilter === 'last7' ? 'selected' : '' ?>>Last 7 days</option>
            <option value="last30" <?= $dateFilter === 'last30' ? 'selected' : '' ?>>Last 30 days</option>
            <option value="last90" <?= $dateFilter === 'last90' ? 'selected' : '' ?>>Last 90 days</option>
            <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>This month</option>
            <option value="year" <?= $dateFilter === 'year' ? 'selected' : '' ?>>Past year</option>
            <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All time</option>
          </select>
        </div>
        <div class="filter-field">
          <label for="category">Category</label>
          <select id="category" name="category" onchange="this.form.submit()">
            <option value="all" <?= $categoryFilter === 'all' ? 'selected' : '' ?>>All categories</option>
            <?php foreach ($categoryOptions as $category): ?>
              <option value="<?= htmlspecialchars($category) ?>" <?= $categoryFilter === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-field">
          <label for="sort">Sort by</label>
          <select id="sort" name="sort" onchange="this.form.submit()">
            <option value="quantity_desc" <?= $sortOption === 'quantity_desc' ? 'selected' : '' ?>>Top sellers (units)</option>
            <option value="quantity_asc" <?= $sortOption === 'quantity_asc' ? 'selected' : '' ?>>Lowest sellers (units)</option>
            <option value="revenue_desc" <?= $sortOption === 'revenue_desc' ? 'selected' : '' ?>>Top revenue</option>
            <option value="revenue_asc" <?= $sortOption === 'revenue_asc' ? 'selected' : '' ?>>Lowest revenue</option>
            <option value="recent" <?= $sortOption === 'recent' ? 'selected' : '' ?>>Recently sold</option>
            <option value="stale" <?= $sortOption === 'stale' ? 'selected' : '' ?>>Stale inventory</option>
            <option value="name_asc" <?= $sortOption === 'name_asc' ? 'selected' : '' ?>>Product name A-Z</option>
            <option value="name_desc" <?= $sortOption === 'name_desc' ? 'selected' : '' ?>>Product name Z-A</option>
          </select>
        </div>
        <div class="filter-field search-field">
          <label for="search">Search</label>
          <div class="search-input">
            <input id="search" type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search product name">
            <button type="submit">Search</button>
          </div>
        </div>
        <div class="filter-actions">
          <a href="ProductSalesReport.php" class="reset-link">Reset filters</a>
          <button type="button" id="exportCsvBtn" class="export-btn">Export CSV</button>
        </div>
      </form>

      <?php if ($errorMessage): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          <?= htmlspecialchars($errorMessage) ?>
        </div>
      <?php else: ?>
        <div class="summary-grid">
          <div class="summary-card">
            <p class="label">Total units sold</p>
            <p class="value"><?= number_format($totalQuantity) ?></p>
          </div>
          <div class="summary-card">
            <p class="label">Total revenue</p>
            <p class="value">₱<?= number_format($totalRevenue, 2) ?></p>
          </div>
          <div class="summary-card">
            <p class="label">Products sold</p>
            <p class="value"><?= number_format($distinctProducts) ?></p>
          </div>
          <div class="summary-card">
            <p class="label">Orders counted</p>
            <p class="value"><?= number_format($totalOrders) ?></p>
          </div>
        </div>

        <div class="summary-grid">
          <?php if ($topProduct && $topProduct['total_quantity'] > 0): ?>
            <div class="insight-card">
              <h3>Top-selling product</h3>
              <p class="product-name"><?= htmlspecialchars($topProduct['Name']) ?></p>
              <p class="metric">Units sold: <?= number_format($topProduct['total_quantity']) ?></p>
              <p class="sub-metric">Revenue generated: ₱<?= number_format($topProduct['total_revenue'], 2) ?></p>
              <p class="sub-metric">Last sold: <?= $topProduct['last_sold'] ? date('M j, Y', strtotime($topProduct['last_sold'])) : '—' ?></p>
            </div>
          <?php endif; ?>

          <?php if ($topRevenueProduct && $topRevenueProduct['Product_ID'] !== ($topProduct['Product_ID'] ?? null)): ?>
            <div class="insight-card">
              <h3>Highest revenue driver</h3>
              <p class="product-name"><?= htmlspecialchars($topRevenueProduct['Name']) ?></p>
              <p class="metric">Revenue: ₱<?= number_format($topRevenueProduct['total_revenue'], 2) ?></p>
              <p class="sub-metric">Units sold: <?= number_format($topRevenueProduct['total_quantity']) ?></p>
            </div>
          <?php endif; ?>

          <?php if ($averageUnitsPerOrder > 0): ?>
            <div class="insight-card">
              <h3>Average units per order</h3>
              <p class="metric"><?= number_format($averageUnitsPerOrder, 2) ?></p>
              <p class="sub-metric">Average revenue per order: ₱<?= number_format($averageRevenuePerOrder, 2) ?></p>
            </div>
          <?php endif; ?>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
          <div class="chart-card">
            <h3>Top products by units</h3>
            <?php if (!empty($chartData['values'])): ?>
              <canvas id="topProductsChart"></canvas>
            <?php else: ?>
              <p class="empty-state">No sales recorded for the selected filters.</p>
            <?php endif; ?>
          </div>
          <div class="chart-card">
            <h3>Category share of units</h3>
            <?php if (!empty($categoryChartData['values'])): ?>
              <canvas id="categoryChart"></canvas>
            <?php else: ?>
              <p class="empty-state">No category breakdown available.</p>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($categoryList)): ?>
          <div class="insight-card">
            <h3>Category contribution</h3>
            <ul class="category-list">
              <?php foreach ($categoryList as $categoryRow): ?>
                <li>
                  <span class="category-name"><?= htmlspecialchars($categoryRow['name']) ?></span>
                  <span class="category-metrics">
                    <?= number_format($categoryRow['quantity']) ?> units · ₱<?= number_format($categoryRow['revenue'], 2) ?> · <?= number_format($categoryRow['share'], 1) ?>%
                  </span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($salesRows): ?>
          <div class="table-wrapper">
            <table id="productSalesTable">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Category</th>
                  <th>Units sold</th>
                  <th>Revenue</th>
                  <th>Orders</th>
                  <th>Avg units / order</th>
                  <th>Share of units</th>
                  <th>Last sold</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($salesRows as $row): ?>
                  <tr>
                    <td><?= htmlspecialchars($row['Name']) ?></td>
                    <td><?= htmlspecialchars($row['Category']) ?></td>
                    <td><?= number_format($row['total_quantity']) ?></td>
                    <td>₱<?= number_format($row['total_revenue'], 2) ?></td>
                    <td><?= number_format($row['orders_count']) ?></td>
                    <td><?= number_format($row['avg_quantity'], 2) ?></td>
                    <td><?= number_format($row['share'], 2) ?>%</td>
                    <td><?= $row['last_sold'] ? date('M j, Y', strtotime($row['last_sold'])) : '—' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="bg-white border border-gray-200 text-gray-600 px-6 py-10 rounded-xl text-center shadow-sm">
            No sales matched the selected filters.
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<script>
  const topProductsData = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>;
  const categoryData = <?= json_encode($categoryChartData, JSON_UNESCAPED_UNICODE) ?>;

  function initTopProductsChart() {
    if (!topProductsData.values || topProductsData.values.length === 0) {
      return;
    }
    const ctx = document.getElementById('topProductsChart');
    if (!ctx) {
      return;
    }
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: topProductsData.labels,
        datasets: [{
          label: 'Units sold',
          data: topProductsData.values,
          backgroundColor: '#f97316',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }

  function initCategoryChart() {
    if (!categoryData.values || categoryData.values.length === 0) {
      return;
    }
    const ctx = document.getElementById('categoryChart');
    if (!ctx) {
      return;
    }
    new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: categoryData.labels,
        datasets: [{
          data: categoryData.values,
          backgroundColor: ['#ef4444', '#f97316', '#facc15', '#22c55e', '#3b82f6', '#a855f7', '#14b8a6']
        }]
      },
      options: {
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  function exportTableToCSV() {
    const table = document.getElementById('productSalesTable');
    if (!table) {
      return;
    }
    const rows = table.querySelectorAll('tr');
    const csv = [];

    rows.forEach(row => {
      const cols = row.querySelectorAll('th, td');
      const rowData = [];
      cols.forEach(col => {
        let text = col.textContent.replace(/\s+/g, ' ').trim();
        if (text.includes('"')) {
          text = text.replace(/"/g, '""');
        }
        if (text.includes(',') || text.includes('"')) {
          text = '"' + text + '"';
        }
        rowData.push(text);
      });
      csv.push(rowData.join(','));
    });

    const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
    link.download = `product-sales-report-${timestamp}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  }

  document.addEventListener('DOMContentLoaded', () => {
    initTopProductsChart();
    initCategoryChart();

    const exportBtn = document.getElementById('exportCsvBtn');
    if (exportBtn) {
      exportBtn.addEventListener('click', exportTableToCSV);
    }
  });
</script>
</body>
</html>
