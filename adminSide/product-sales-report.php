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
$monthlySales = [];
$statusBreakdown = [];
$categoryRevenue = [];
$categoryUnits = [];

if ($pdo) {
    $allowedStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered'];
    $statusList = implode(',', array_map([$pdo, 'quote'], $allowedStatuses));

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

    $monthlySql = "
        SELECT
            DATE_FORMAT(o.Order_Date, '%Y-%m') AS period,
            COALESCE(SUM(oi.Subtotal), 0) AS revenue,
            COALESCE(SUM(oi.Quantity), 0) AS units
        FROM order_item oi
        JOIN `order` o ON o.Order_ID = oi.Order_ID
        WHERE o.Status IN ($statusList) AND o.Order_Date IS NOT NULL
        GROUP BY period
        ORDER BY period ASC
    ";
    $stmtMonthly = $pdo->query($monthlySql);
    if ($stmtMonthly) {
        while ($row = $stmtMonthly->fetch(PDO::FETCH_ASSOC)) {
            $period = $row['period'] ?? null;
            if ($period) {
                $labelDate = strtotime($period . '-01');
                $label = $labelDate ? date('M Y', $labelDate) : $period;
            } else {
                $label = 'Unknown';
            }

            $monthlySales[] = [
                'period' => $period,
                'label' => $label,
                'revenue' => (float)($row['revenue'] ?? 0),
                'units' => (int)($row['units'] ?? 0)
            ];
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

$monthlyLabels = [];
$monthlyRevenueValues = [];
$monthlyUnitValues = [];
foreach ($monthlySales as $month) {
    $monthlyLabels[] = $month['label'];
    $monthlyRevenueValues[] = round($month['revenue'], 2);
    $monthlyUnitValues[] = (int)$month['units'];
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
$monthlyLabelsJson = json_encode($monthlyLabels, $jsonFlags) ?: '[]';
$monthlyRevenueJson = json_encode($monthlyRevenueValues, $jsonFlags) ?: '[]';
$monthlyUnitsJson = json_encode($monthlyUnitValues, $jsonFlags) ?: '[]';
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
      <h2 style="font-size:18px;margin-bottom:16px;">Monthly Product Performance</h2>
      <canvas id="monthlySalesChart" height="220"></canvas>
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

  <div class="card" style="margin-top:24px;">
    <div class="table-actions">
      <input type="text" id="productSalesSearch" placeholder="🔍 Search product or category...">
    </div>
    <div class="table-responsive">
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
</div>

<?php
$extraScripts = <<<JS
<script>
  const monthlyLabels = $monthlyLabelsJson;
  const monthlyRevenue = $monthlyRevenueJson;
  const monthlyUnits = $monthlyUnitsJson;
  const topProductLabels = $topProductLabelsJson;
  const topProductRevenue = $topProductRevenueJson;
  const topProductUnits = $topProductUnitsJson;
  const categoryLabels = $categoryLabelsJson;
  const categoryRevenue = $categoryRevenueJson;
  const categoryUnits = $categoryUnitsJson;

  const monthlyChartEl = document.getElementById('monthlySalesChart');
  if (monthlyChartEl) {
    const hasMonthlyData = Array.isArray(monthlyLabels) && monthlyLabels.length > 0;
    const labels = hasMonthlyData ? monthlyLabels : ['No Data'];
    const revenueData = hasMonthlyData ? monthlyRevenue : [0];
    const unitData = hasMonthlyData ? monthlyUnits : [0];

    new Chart(monthlyChartEl, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            type: 'line',
            label: 'Revenue (₱)',
            data: revenueData,
            borderColor: '#e67e22',
            backgroundColor: 'rgba(230, 126, 34, 0.25)',
            tension: 0.3,
            yAxisID: 'y'
          },
          {
            type: 'bar',
            label: 'Units Sold',
            data: unitData,
            backgroundColor: 'rgba(46, 204, 113, 0.6)',
            borderRadius: 6,
            yAxisID: 'yUnits'
          }
        ]
      },
      options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
          y: {
            beginAtZero: true,
            position: 'left',
            ticks: {
              callback: value => '₱' + Number(value).toLocaleString()
            }
          },
          yUnits: {
            beginAtZero: true,
            position: 'right',
            grid: { drawOnChartArea: false },
            ticks: {
              callback: value => Number(value).toLocaleString()
            }
          }
        }
      }
    });
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
