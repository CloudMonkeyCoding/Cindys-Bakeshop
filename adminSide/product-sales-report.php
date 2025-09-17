<?php
require_once '../PHP/db_connect.php';

$activePage = 'product-sales-report';
$pageTitle = "Product Sales Report - Cindy's Bakeshop";

$totalRevenue = 0.0;
$totalUnits = 0;
$totalOrders = 0;
$averageOrderValue = 0.0;
$averageUnitsPerOrder = 0.0;
$averageSellingPrice = 0.0;
$productsWithSales = 0;
$catalogSize = 0;
$topProductName = 'No sales recorded yet';
$topProductRevenue = 0.0;
$topProductUnits = 0;
$topCategoryName = 'No category sales yet';
$topCategoryRevenue = 0.0;
$recentSaleTimestamp = null;
$recentSaleDisplay = 'No sales recorded yet';

$productSales = [];
$statusBreakdown = [];
$categoryRevenue = [];
$categoryUnits = [];
$timeRanges = [
    '7d' => ['label' => 'Last 7 Days', 'days' => 7],
    '30d' => ['label' => 'Last 30 Days', 'days' => 30],
    '90d' => ['label' => 'Last 90 Days', 'days' => 90],
];
if (function_exists('array_key_first')) {
    $salesTrendDefaultRange = array_key_first($timeRanges) ?? '7d';
} else {
    reset($timeRanges);
    $salesTrendDefaultRange = key($timeRanges);
    if ($salesTrendDefaultRange === null) {
        $salesTrendDefaultRange = '7d';
    }
}
$salesTrendDefaultLabel = $timeRanges[$salesTrendDefaultRange]['label'] ?? 'Last 7 Days';
$dailyRevenueMap = [];
$salesTrendByRange = [];
$maxDays = 1;
foreach ($timeRanges as $config) {
    $days = isset($config['days']) ? (int)$config['days'] : 0;
    if ($days > $maxDays) {
        $maxDays = $days;
    }
}

if ($pdo) {
    $allowedStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered'];
    $statusList = implode(',', array_map([$pdo, 'quote'], $allowedStatuses));

    $daysInterval = max($maxDays - 1, 0);

    $productSql = "
        SELECT
            p.Product_ID,
            p.Name,
            p.Category,
            COALESCE(SUM(CASE WHEN o.Status IN ($statusList) THEN oi.Quantity ELSE 0 END), 0) AS units_sold,
            COALESCE(SUM(CASE WHEN o.Status IN ($statusList) THEN oi.Subtotal ELSE 0 END), 0) AS revenue,
            COUNT(DISTINCT CASE WHEN o.Status IN ($statusList) THEN o.Order_ID END) AS order_count,
            MIN(CASE WHEN o.Status IN ($statusList) THEN o.Order_Date END) AS first_sale,
            MAX(CASE WHEN o.Status IN ($statusList) THEN o.Order_Date END) AS last_sale
        FROM product p
        LEFT JOIN order_item oi ON oi.Product_ID = p.Product_ID
        LEFT JOIN `order` o ON o.Order_ID = oi.Order_ID
        GROUP BY p.Product_ID, p.Name, p.Category
        ORDER BY revenue DESC, units_sold DESC, p.Name ASC
    ";
    $stmtProducts = $pdo->query($productSql);
    if ($stmtProducts) {
        $productSales = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);
    }

    $ordersSql = "
        SELECT COUNT(DISTINCT CASE WHEN o.Status IN ($statusList) THEN o.Order_ID END) AS order_count
        FROM order_item oi
        JOIN `order` o ON o.Order_ID = oi.Order_ID
    ";
    $stmtOrders = $pdo->query($ordersSql);
    if ($stmtOrders) {
        $totalOrders = (int)($stmtOrders->fetchColumn() ?? 0);
    }

    $trendSql = "
        SELECT
            DATE(o.Order_Date) AS sale_date,
            COALESCE(SUM(oi.Subtotal), 0) AS revenue
        FROM order_item oi
        JOIN `order` o ON o.Order_ID = oi.Order_ID
        WHERE o.Status IN ($statusList)
          AND o.Order_Date IS NOT NULL
          AND DATE(o.Order_Date) >= DATE_SUB(CURDATE(), INTERVAL $daysInterval DAY)
        GROUP BY sale_date
        ORDER BY sale_date ASC
    ";
    $stmtTrend = $pdo->query($trendSql);
    if ($stmtTrend) {
        while ($row = $stmtTrend->fetch(PDO::FETCH_ASSOC)) {
            $saleDate = $row['sale_date'] ?? null;
            if ($saleDate) {
                $dailyRevenueMap[$saleDate] = (float)($row['revenue'] ?? 0);
            }
        }
    }

    $statusSql = "
        SELECT
            COALESCE(o.Status, 'Unknown') AS status,
            COALESCE(SUM(oi.Quantity), 0) AS units,
            COALESCE(SUM(oi.Subtotal), 0) AS revenue
        FROM order_item oi
        JOIN `order` o ON o.Order_ID = oi.Order_ID
        WHERE o.Status IN ($statusList)
        GROUP BY status
        ORDER BY revenue DESC
    ";
    $stmtStatus = $pdo->query($statusSql);
    if ($stmtStatus) {
        $statusBreakdown = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
    }
}

$catalogSize = count($productSales);

foreach ($productSales as &$product) {
    $product['Name'] = $product['Name'] ?? 'Unnamed Product';
    $product['Category'] = $product['Category'] ?? 'Uncategorized';
    $product['revenue'] = (float)($product['revenue'] ?? 0);
    $product['units_sold'] = (int)($product['units_sold'] ?? 0);
    $product['order_count'] = (int)($product['order_count'] ?? 0);
    $product['first_sale'] = $product['first_sale'] ?? null;
    $product['last_sale'] = $product['last_sale'] ?? null;

    $totalRevenue += $product['revenue'];
    $totalUnits += $product['units_sold'];

    $category = $product['Category'] ?: 'Uncategorized';
    $categoryRevenue[$category] = ($categoryRevenue[$category] ?? 0) + $product['revenue'];
    $categoryUnits[$category] = ($categoryUnits[$category] ?? 0) + $product['units_sold'];

    if ($product['units_sold'] > 0) {
        $productsWithSales++;
    }

    if ($product['revenue'] > $topProductRevenue || ($product['revenue'] === $topProductRevenue && $product['units_sold'] > $topProductUnits)) {
        if ($product['revenue'] > 0 || $product['units_sold'] > 0) {
            $topProductRevenue = $product['revenue'];
            $topProductUnits = $product['units_sold'];
            $topProductName = $product['Name'];
        }
    }

    if ($product['last_sale']) {
        $timestamp = strtotime($product['last_sale']);
        if ($timestamp !== false && ($recentSaleTimestamp === null || $timestamp > $recentSaleTimestamp)) {
            $recentSaleTimestamp = $timestamp;
        }
    }
}
unset($product);

if ($recentSaleTimestamp) {
    $recentSaleDisplay = date('M d, Y', $recentSaleTimestamp);
}

$sortedCategoryRevenue = $categoryRevenue;
arsort($sortedCategoryRevenue);
if (!empty($sortedCategoryRevenue)) {
    $topCategoryName = array_key_first($sortedCategoryRevenue) ?? 'No category sales yet';
    $topCategoryRevenue = $sortedCategoryRevenue[$topCategoryName] ?? 0.0;
}

$averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;
$averageUnitsPerOrder = $totalOrders > 0 ? $totalUnits / $totalOrders : 0.0;
$averageSellingPrice = $totalUnits > 0 ? $totalRevenue / max($totalUnits, 1) : 0.0;

$currentTimestamp = time();
foreach ($timeRanges as $rangeKey => $config) {
    $days = isset($config['days']) ? (int)$config['days'] : 0;
    if ($days <= 0) {
        continue;
    }
    $labels = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $timestamp = strtotime("-{$i} day", $currentTimestamp);
        if ($timestamp === false) {
            continue;
        }
        $dateKey = date('Y-m-d', $timestamp);
        $labels[] = $days <= 7 ? date('D', $timestamp) : date('M d', $timestamp);
        $values[] = round($dailyRevenueMap[$dateKey] ?? 0, 2);
    }
    if (!empty($labels)) {
        $salesTrendByRange[$rangeKey] = [
            'labels' => $labels,
            'values' => $values,
            'rangeLabel' => $config['label'] ?? 'Selected Range',
        ];
    }
}
if (empty($salesTrendByRange) && !empty($timeRanges)) {
    $salesTrendByRange[$salesTrendDefaultRange] = [
        'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        'values' => array_fill(0, 7, 0),
        'rangeLabel' => $salesTrendDefaultLabel,
    ];
}

$topProductChartData = array_slice(array_values(array_filter($productSales, function ($product) {
    return ($product['revenue'] ?? 0) > 0;
})), 0, 5);
$topProductLabels = array_map(function ($product) {
    return $product['Name'];
}, $topProductChartData);
$topProductRevenueValues = array_map(function ($product) {
    return round($product['revenue'], 2);
}, $topProductChartData);
$topProductUnitValues = array_map(function ($product) {
    return (int)$product['units_sold'];
}, $topProductChartData);

$categoryLabels = [];
$categoryRevenueValues = [];
$categoryUnitValues = [];
foreach ($sortedCategoryRevenue as $category => $revenue) {
    if ($revenue <= 0) {
        continue;
    }
    $categoryLabels[] = $category;
    $categoryRevenueValues[] = round($revenue, 2);
    $categoryUnitValues[] = (int)($categoryUnits[$category] ?? 0);
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
$salesTrendDataJson = json_encode($salesTrendByRange, $jsonFlags);
if ($salesTrendDataJson === false) {
    $salesTrendDataJson = '{}';
}
$salesTrendDefaultRangeJson = json_encode($salesTrendDefaultRange, $jsonFlags);
if ($salesTrendDefaultRangeJson === false) {
    $salesTrendDefaultRangeJson = 'null';
}
$topProductLabelsJson = json_encode($topProductLabels, $jsonFlags) ?: '[]';
$topProductRevenueJson = json_encode($topProductRevenueValues, $jsonFlags) ?: '[]';
$topProductUnitsJson = json_encode($topProductUnitValues, $jsonFlags) ?: '[]';
$categoryLabelsJson = json_encode($categoryLabels, $jsonFlags) ?: '[]';
$categoryRevenueJson = json_encode($categoryRevenueValues, $jsonFlags) ?: '[]';
$categoryUnitsJson = json_encode($categoryUnitValues, $jsonFlags) ?: '[]';

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Product Sales Report</h1>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <span style="font-size:14px;color:#7f8c8d;">Last sale: <?= htmlspecialchars($recentSaleDisplay); ?></span>
      <button class="btn btn-primary" id="exportProductSales">Export Sales PDF</button>
    </div>
  </div>

  <section class="stats-grid columns-4" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-card">
      <h3>Total Product Revenue</h3>
      <div class="value">₱<?= number_format($totalRevenue, 2); ?></div>
      <div class="meta">
        <?php if ($topCategoryRevenue > 0): ?>
          Top category: <?= htmlspecialchars($topCategoryName); ?> (₱<?= number_format($topCategoryRevenue, 2); ?>)
        <?php else: ?>
          No category sales yet
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-card">
      <h3>Units Sold</h3>
      <div class="value"><?= number_format($totalUnits); ?></div>
      <div class="meta">
        <?php if ($topProductRevenue > 0 || $topProductUnits > 0): ?>
          Top product: <?= htmlspecialchars($topProductName); ?> (<?= number_format($topProductUnits); ?> sold) &bull; Avg units/order: <?= number_format($averageUnitsPerOrder, 1); ?>
        <?php else: ?>
          No product sales yet (Avg units/order: <?= number_format($averageUnitsPerOrder, 1); ?>)
        <?php endif; ?>
      </div>
    </div>
    <div class="stat-card">
      <h3>Orders with Sales</h3>
      <div class="value"><?= number_format($totalOrders); ?></div>
      <div class="meta">Average order value: ₱<?= number_format($averageOrderValue, 2); ?></div>
    </div>
    <div class="stat-card">
      <h3>Products Sold</h3>
      <div class="value"><?= number_format($productsWithSales); ?></div>
      <div class="meta">Avg item price: ₱<?= number_format($averageSellingPrice, 2); ?> &bull; Catalog: <?= number_format($catalogSize); ?> items</div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top:24px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <div class="card">
      <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:16px;">
        <div>
          <h2 style="font-size:18px;margin:0;">Sales Trend</h2>
          <p id="salesTrendRangeCaption" style="margin:4px 0 0;font-size:13px;color:#7f8c8d;">
            <?= htmlspecialchars($salesTrendDefaultLabel); ?>
          </p>
        </div>
        <select id="salesTrendRange" aria-label="Change sales trend range" style="padding:8px 12px;border:1px solid #dcdde1;border-radius:6px;font-size:14px;min-width:180px;">
          <?php foreach ($timeRanges as $rangeKey => $rangeConfig): ?>
            <option value="<?= htmlspecialchars($rangeKey); ?>" <?= $rangeKey === $salesTrendDefaultRange ? 'selected' : ''; ?>>
              <?= htmlspecialchars($rangeConfig['label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <canvas id="salesChart" height="220"></canvas>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Top Selling Products</h2>
      <canvas id="topProductChart" height="220"></canvas>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Revenue by Category</h2>
      <canvas id="categoryChart" height="220"></canvas>
    </div>
  </div>

  <div class="card" style="margin-top:24px;">
    <h2 style="font-size:18px;margin-bottom:16px;">Sales by Order Status</h2>
    <div class="table-responsive">
      <?php if (empty($statusBreakdown)): ?>
        <p class="table-empty">No order activity recorded.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Status</th>
              <th>Units Sold</th>
              <th>Revenue</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($statusBreakdown as $statusRow): ?>
              <?php
                $statusName = $statusRow['status'] ?? 'Unknown';
                $statusUnits = (int)($statusRow['units'] ?? 0);
                $statusRevenue = (float)($statusRow['revenue'] ?? 0);
                $statusClass = strtolower(str_replace(' ', '-', $statusName));
              ?>
              <tr>
                <td>
                  <span class="status-pill status-<?= htmlspecialchars($statusClass); ?>">
                    <?= htmlspecialchars($statusName); ?>
                  </span>
                </td>
                <td><?= number_format($statusUnits); ?></td>
                <td>₱<?= number_format($statusRevenue, 2); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-container" style="margin-top:24px;">
    <div class="table-actions">
      <input type="text" id="productSalesSearch" placeholder="🔍 Search product or category...">
    </div>
    <?php if (empty($productSales)): ?>
      <p class="table-empty">No products found.</p>
    <?php else: ?>
      <table id="productSalesTable">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Units Sold</th>
            <th>Revenue</th>
            <th>Orders</th>
            <th>First Sale</th>
            <th>Last Sale</th>
            <th>Avg Item Price</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productSales as $product): ?>
            <?php
              $unitsSold = $product['units_sold'];
              $revenue = $product['revenue'];
              $avgPrice = $unitsSold > 0 ? $revenue / max($unitsSold, 1) : 0;
              $firstSale = $product['first_sale'] ? strtotime($product['first_sale']) : false;
              $lastSale = $product['last_sale'] ? strtotime($product['last_sale']) : false;
            ?>
            <tr>
              <td><?= htmlspecialchars($product['Name']); ?></td>
              <td><?= htmlspecialchars($product['Category']); ?></td>
              <td><?= number_format($unitsSold); ?></td>
              <td>₱<?= number_format($revenue, 2); ?></td>
              <td><?= number_format($product['order_count']); ?></td>
              <td><?= $firstSale ? htmlspecialchars(date('M d, Y', $firstSale)) : '—'; ?></td>
              <td><?= $lastSale ? htmlspecialchars(date('M d, Y', $lastSale)) : '—'; ?></td>
              <td><?= $unitsSold > 0 ? '₱' . number_format($avgPrice, 2) : '—'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
  const salesTrendDataByRange = $salesTrendDataJson;
  const salesTrendDefaultRange = $salesTrendDefaultRangeJson;
  const topProductLabels = $topProductLabelsJson;
  const topProductRevenue = $topProductRevenueJson;
  const topProductUnits = $topProductUnitsJson;
  const categoryLabels = $categoryLabelsJson;
  const categoryRevenue = $categoryRevenueJson;
  const categoryUnits = $categoryUnitsJson;

  const salesChartCanvas = document.getElementById('salesChart');
  const salesTrendRangeSelect = document.getElementById('salesTrendRange');
  const salesTrendRangeCaption = document.getElementById('salesTrendRangeCaption');
  if (salesChartCanvas) {
    const ctx = salesChartCanvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, salesChartCanvas.height || 400);
    gradient.addColorStop(0, 'rgba(231, 76, 60, 0.4)');
    gradient.addColorStop(1, 'rgba(231, 76, 60, 0)');

    const rangeOptions = (salesTrendDataByRange && typeof salesTrendDataByRange === 'object' && !Array.isArray(salesTrendDataByRange)) ? salesTrendDataByRange : {};
    const rangeKeys = Object.keys(rangeOptions);
    const fallbackLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const fallbackValues = new Array(fallbackLabels.length).fill(0);
    const getPointStyling = labelCount => {
      const isDense = labelCount > 31;
      return {
        radius: isDense ? 3 : 6,
        hoverRadius: isDense ? 5 : 8,
      };
    };
    const getDatasetForRange = rangeKey => {
      const dataset = rangeKey && Object.prototype.hasOwnProperty.call(rangeOptions, rangeKey) ? rangeOptions[rangeKey] : null;
      const labels = dataset && Array.isArray(dataset.labels) ? dataset.labels.slice() : fallbackLabels.slice();
      const rawValues = dataset && Array.isArray(dataset.values) ? dataset.values.slice() : fallbackValues.slice();
      const sanitizedValues = labels.map((_, index) => {
        const value = rawValues[index] ?? 0;
        const numericValue = Number.parseFloat(value);
        return Number.isFinite(numericValue) ? numericValue : 0;
      });
      const rangeLabel = dataset && typeof dataset.rangeLabel === 'string' ? dataset.rangeLabel : 'No sales recorded yet';
      return { labels, values: sanitizedValues, rangeLabel };
    };

    const defaultRangeKey = typeof salesTrendDefaultRange === 'string' && Object.prototype.hasOwnProperty.call(rangeOptions, salesTrendDefaultRange)
      ? salesTrendDefaultRange
      : (rangeKeys.length > 0 ? rangeKeys[0] : null);

    const initialDataset = getDatasetForRange(defaultRangeKey);
    const initialPointStyle = getPointStyling(initialDataset.labels.length);

    const salesChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: initialDataset.labels,
        datasets: [{
          label: 'Sales (₱)',
          data: initialDataset.values,
          borderColor: '#e74c3c',
          borderWidth: 3,
          backgroundColor: gradient,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#e74c3c',
          pointRadius: initialPointStyle.radius,
          pointHoverRadius: initialPointStyle.hoverRadius,
          tension: 0.4,
          fill: true,
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: context => {
                const value = context.parsed.y ?? 0;
                return 'Sales: ₱' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
              }
            }
          }
        },
        scales: {
          x: {
            ticks: {
              autoSkip: true,
              maxRotation: 0,
              maxTicksLimit: 15,
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + Number(value).toLocaleString()
            }
          }
        }
      }
    });

    if (salesTrendRangeCaption) {
      salesTrendRangeCaption.textContent = initialDataset.rangeLabel;
    }

    const updateSalesChartRange = rangeKey => {
      const dataset = getDatasetForRange(rangeKey);
      const pointStyle = getPointStyling(dataset.labels.length);
      salesChart.data.labels = dataset.labels;
      salesChart.data.datasets[0].data = dataset.values;
      salesChart.data.datasets[0].pointRadius = pointStyle.radius;
      salesChart.data.datasets[0].pointHoverRadius = pointStyle.hoverRadius;
      salesChart.update();
      if (salesTrendRangeCaption) {
        salesTrendRangeCaption.textContent = dataset.rangeLabel;
      }
    };

    if (salesTrendRangeSelect) {
      if (defaultRangeKey) {
        salesTrendRangeSelect.value = defaultRangeKey;
      }
      salesTrendRangeSelect.addEventListener('change', event => {
        updateSalesChartRange(event.target.value);
      });
    }
  }

  const topProductChartEl = document.getElementById('topProductChart');
  if (topProductChartEl) {
    const hasTopProducts = Array.isArray(topProductLabels) && topProductLabels.length > 0;
    const labels = hasTopProducts ? topProductLabels : ['No Sales'];
    const revenueData = hasTopProducts ? topProductRevenue : [0];
    const unitData = hasTopProducts ? topProductUnits : [0];

    new Chart(topProductChartEl, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            label: 'Revenue (₱)',
            data: revenueData,
            backgroundColor: '#3498db',
            borderRadius: 6
          }
        ]
      },
      options: {
        plugins: {
          tooltip: {
            callbacks: {
              label: context => {
                const revenue = context.parsed.y ?? 0;
                const units = unitData[context.dataIndex] ?? 0;
                const revenueLabel = 'Revenue: ₱' + Number(revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const unitsLabel = 'Units sold: ' + Number(units).toLocaleString();
                return [revenueLabel, unitsLabel];
              }
            }
          },
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + Number(value).toLocaleString()
            }
          }
        }
      }
    });
  }

  const categoryChartEl = document.getElementById('categoryChart');
  if (categoryChartEl) {
    const hasCategoryData = Array.isArray(categoryLabels) && categoryLabels.length > 0;
    const labels = hasCategoryData ? categoryLabels : ['No Sales'];
    const revenueData = hasCategoryData ? categoryRevenue : [0];
    const unitData = hasCategoryData ? categoryUnits : [0];

    new Chart(categoryChartEl, {
      type: 'doughnut',
      data: {
        labels,
        datasets: [
          {
            data: revenueData,
            backgroundColor: ['#9b59b6', '#1abc9c', '#f1c40f', '#e74c3c', '#2ecc71', '#34495e']
          }
        ]
      },
      options: {
        plugins: {
          tooltip: {
            callbacks: {
              label: context => {
                const revenue = context.parsed ?? 0;
                const units = unitData[context.dataIndex] ?? 0;
                const revenueLabel = 'Revenue: ₱' + Number(revenue).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const unitsLabel = 'Units sold: ' + Number(units).toLocaleString();
                return context.label + ': ' + revenueLabel + ' • ' + unitsLabel;
              }
            }
          },
          legend: { position: 'bottom' }
        }
      }
    });
  }

  const searchInput = document.getElementById('productSalesSearch');
  if (searchInput) {
    const rows = Array.from(document.querySelectorAll('#productSalesTable tbody tr'));
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.trim().toLowerCase();
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  const exportBtn = document.getElementById('exportProductSales');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'landscape' });
      doc.setFontSize(16);
      doc.text('Product Sales Report', 14, 18);

      const rows = [];
      document.querySelectorAll('#productSalesTable tbody tr').forEach(tr => {
        if (tr.style.display === 'none') {
          return;
        }
        const cells = Array.from(tr.cells).map(td => td.textContent.trim());
        rows.push(cells);
      });

      doc.autoTable({
        startY: 26,
        head: [['Product', 'Category', 'Units Sold', 'Revenue', 'Orders', 'First Sale', 'Last Sale', 'Avg Item Price']],
        body: rows,
        theme: 'grid',
        styles: { fontSize: 9 }
      });

      doc.save('product-sales-report.pdf');
    });
  }
</script>
JS;
include 'includes/footer.php';
