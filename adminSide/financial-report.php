<?php
require_once __DIR__ . '/includes/require_super_admin.php';
require_once '../PHP/db_connect.php';

$activePage = 'financial-report';
$pageTitle = "Financial Report - Cindy's Bakeshop";

$totalRevenue = 0.0;
$totalTransactions = 0;
$uniqueOrders = 0;
$averageOrderValue = 0.0;
$lastPaymentDate = null;
$dailyAverageRevenue = 0.0;
$paymentMethods = [];
$statusBreakdown = [];
$reportRows = [];

$timeRanges = [
    '7d' => ['label' => 'Last 7 Days', 'days' => 7],
    '30d' => ['label' => 'Last 30 Days', 'days' => 30],
    '90d' => ['label' => 'Last 90 Days', 'days' => 90],
];

if (function_exists('array_key_first')) {
    $revenueTrendDefaultRange = array_key_first($timeRanges) ?? '7d';
} else {
    reset($timeRanges);
    $revenueTrendDefaultRange = key($timeRanges);
    if ($revenueTrendDefaultRange === null) {
        $revenueTrendDefaultRange = '7d';
    }
}

$revenueTrendDefaultLabel = $timeRanges[$revenueTrendDefaultRange]['label'] ?? 'Last 7 Days';
$dailyRevenueMap = [];
$revenueTrendByRange = [];
$maxDays = 1;

foreach ($timeRanges as $config) {
    $days = isset($config['days']) ? (int)$config['days'] : 0;
    if ($days > $maxDays) {
        $maxDays = $days;
    }
}

if ($pdo) {
    $stmtSummary = $pdo->query("SELECT COALESCE(SUM(Amount_Paid),0) AS revenue, COUNT(*) AS transactions, COUNT(DISTINCT Order_ID) AS orders, MAX(Payment_Date) AS last_payment FROM transaction");
    if ($stmtSummary) {
        $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC) ?: [];
        $totalRevenue = (float)($summary['revenue'] ?? 0);
        $totalTransactions = (int)($summary['transactions'] ?? 0);
        $uniqueOrders = (int)($summary['orders'] ?? 0);
        $lastPaymentDate = $summary['last_payment'] ?? null;
        if ($uniqueOrders > 0) {
            $averageOrderValue = $totalRevenue / $uniqueOrders;
        }
    }

    $daysInterval = max($maxDays - 1, 0);

    $trendSql = "
        SELECT
            DATE(Payment_Date) AS payment_date,
            COALESCE(SUM(Amount_Paid), 0) AS revenue
        FROM transaction
        WHERE Payment_Date IS NOT NULL
          AND DATE(Payment_Date) >= DATE_SUB(CURDATE(), INTERVAL $daysInterval DAY)
        GROUP BY payment_date
        ORDER BY payment_date ASC
    ";
    $stmtTrend = $pdo->query($trendSql);
    if ($stmtTrend) {
        while ($row = $stmtTrend->fetch(PDO::FETCH_ASSOC)) {
            $paymentDate = $row['payment_date'] ?? null;
            if ($paymentDate) {
                $dailyRevenueMap[$paymentDate] = (float)($row['revenue'] ?? 0);
            }
        }
    }

    $daysForAverage = 30;
    if ($daysForAverage > 0) {
        $totalRevenueWindow = 0.0;
        $todayTimestamp = time();
        for ($i = 0; $i < $daysForAverage; $i++) {
            $timestamp = strtotime("-{$i} day", $todayTimestamp);
            if ($timestamp === false) {
                continue;
            }
            $dateKey = date('Y-m-d', $timestamp);
            $totalRevenueWindow += (float)($dailyRevenueMap[$dateKey] ?? 0);
        }
        $dailyAverageRevenue = $totalRevenueWindow / $daysForAverage;
    }

    $stmtMethods = $pdo->query("SELECT COALESCE(Payment_Method, 'Unknown') AS method, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction GROUP BY method ORDER BY total DESC");
    if ($stmtMethods) {
        $paymentMethods = $stmtMethods->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtStatus = $pdo->query("SELECT COALESCE(Payment_Status, 'Unknown') AS status, COUNT(*) AS count, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction GROUP BY status ORDER BY total DESC");
    if ($stmtStatus) {
        $statusBreakdown = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtReport = $pdo->query(
        "SELECT t.Transaction_ID, t.Order_ID, t.Payment_Method, t.Payment_Status, t.Payment_Date, t.Amount_Paid, t.Reference_Number,\n" .
        "       o.Order_Date, u.Name AS Customer, COALESCE(SUM(oi.Subtotal),0) AS Product_Total\n" .
        "FROM transaction t\n" .
        "LEFT JOIN `order` o ON t.Order_ID = o.Order_ID\n" .
        "LEFT JOIN user u ON o.User_ID = u.User_ID\n" .
        "LEFT JOIN order_item oi ON oi.Order_ID = o.Order_ID\n" .
        "GROUP BY t.Transaction_ID, t.Order_ID, t.Payment_Method, t.Payment_Status, t.Payment_Date, t.Amount_Paid, t.Reference_Number, o.Order_Date, u.Name\n" .
        "ORDER BY COALESCE(t.Payment_Date, o.Order_Date) DESC, t.Transaction_ID DESC"
    );
    if ($stmtReport) {
        $reportRows = $stmtReport->fetchAll(PDO::FETCH_ASSOC);
    }
}

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
        $values[] = (int)round($dailyRevenueMap[$dateKey] ?? 0);
    }
    if (!empty($labels)) {
        $revenueTrendByRange[$rangeKey] = [
            'labels' => $labels,
            'values' => $values,
            'rangeLabel' => $config['label'] ?? 'Selected Range',
        ];
    }
}

if (empty($revenueTrendByRange) && !empty($timeRanges)) {
    $revenueTrendByRange[$revenueTrendDefaultRange] = [
        'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        'values' => array_fill(0, 7, 0),
        'rangeLabel' => $revenueTrendDefaultLabel,
    ];
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;

$revenueTrendDataJson = json_encode($revenueTrendByRange, $jsonFlags);
if ($revenueTrendDataJson === false) {
    $revenueTrendDataJson = '{}';
}

$revenueTrendDefaultRangeJson = json_encode($revenueTrendDefaultRange, $jsonFlags);
if ($revenueTrendDefaultRangeJson === false) {
    $revenueTrendDefaultRangeJson = 'null';
}

$methodLabelsJson = json_encode(array_map(function ($item) {
    return $item['method'];
}, $paymentMethods), $jsonFlags);
if ($methodLabelsJson === false) {
    $methodLabelsJson = '[]';
}

$methodValuesJson = json_encode(array_map(function ($item) {
    return (int)round((float)$item['total']);
}, $paymentMethods), $jsonFlags);
if ($methodValuesJson === false) {
    $methodValuesJson = '[]';
}

$statusLabelsJson = json_encode(array_map(function ($item) {
    return $item['status'];
}, $statusBreakdown), $jsonFlags);
if ($statusLabelsJson === false) {
    $statusLabelsJson = '[]';
}

$statusValuesJson = json_encode(array_map(function ($item) {
    return (int)round((float)$item['total']);
}, $statusBreakdown), $jsonFlags);
if ($statusValuesJson === false) {
    $statusValuesJson = '[]';
}

$lastPaymentDisplay = $lastPaymentDate ? date('M d, Y', strtotime($lastPaymentDate)) : 'No payments recorded yet';

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Financial Report</h1>
    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <span style="font-size:14px;color:#7f8c8d;">Last payment: <?= htmlspecialchars($lastPaymentDisplay); ?></span>
    </div>
  </div>

  <section class="stats-grid columns-4" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-card">
      <h3>Total Revenue</h3>
      <div class="value">₱<?= number_format($totalRevenue, 0); ?></div>
      <div class="meta">Across <?= number_format($totalTransactions); ?> payments</div>
    </div>
    <div class="stat-card">
      <h3>Daily Avg Revenue</h3>
      <div class="value">₱<?= number_format($dailyAverageRevenue, 0); ?></div>
      <div class="meta">Average per day over last 30 days</div>
    </div>
    <div class="stat-card">
      <h3>Average Order Value</h3>
      <div class="value">₱<?= number_format($averageOrderValue, 0); ?></div>
      <div class="meta">Based on <?= number_format($uniqueOrders); ?> orders</div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top:24px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <div class="card">
      <div style="display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:16px;">
        <div>
          <h2 style="font-size:18px;margin:0;">Sales Trend</h2>
          <p id="revenueTrendRangeCaption" style="margin:4px 0 0;font-size:13px;color:#7f8c8d;">
            <?= htmlspecialchars($revenueTrendDefaultLabel); ?>
          </p>
        </div>
        <select id="revenueTrendRange" aria-label="Change sales trend range" style="padding:8px 12px;border:1px solid #dcdde1;border-radius:6px;font-size:14px;min-width:180px;">
          <?php foreach ($timeRanges as $rangeKey => $rangeConfig): ?>
            <option value="<?= htmlspecialchars($rangeKey); ?>" <?= $rangeKey === $revenueTrendDefaultRange ? 'selected' : ''; ?>>
              <?= htmlspecialchars($rangeConfig['label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <canvas id="revenueTrendChart" height="220"></canvas>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Payment Methods</h2>
      <canvas id="methodChart" height="220"></canvas>
    </div>
  </div>

  <div class="card" style="margin-top:24px;">
    <div class="table-actions">
      <input
        type="text"
        id="financeSearch"
        placeholder="🔍 Search payment record..."
        aria-label="Search finance transactions"
      >
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <select
          id="financeTimeRange"
          aria-label="Filter finance records by time period"
          style="padding:8px 12px;border:1px solid #dcdde1;border-radius:6px;font-size:14px;min-width:180px;"
        >
          <option value="all" data-days="0" selected>All Time</option>
          <?php foreach ($timeRanges as $rangeKey => $rangeConfig): ?>
            <option value="<?= htmlspecialchars($rangeKey); ?>" data-days="<?= (int)($rangeConfig['days'] ?? 0); ?>">
              <?= htmlspecialchars($rangeConfig['label']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" id="exportFinance">Export Finance PDF</button>
      </div>
    </div>
    <div class="table-responsive">
      <?php if (empty($reportRows)): ?>
        <p class="table-empty">No payment records available.</p>
      <?php else: ?>
        <table id="financialTable">
          <thead>
            <tr>
              <th>Transaction ID</th>
              <th>Order ID</th>
              <th>Order Date</th>
              <th>Payment Date</th>
              <th>Customer</th>
              <th>Product Total</th>
              <th>Amount Paid</th>
              <th>Method</th>
              <th>Status</th>
              <th>Reference</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reportRows as $row): ?>
              <?php
                $rawPaymentDate = $row['Payment_Date'] ?? null;
                $rawOrderDate = $row['Order_Date'] ?? null;
                $transactionDateForFilter = $rawPaymentDate ?: $rawOrderDate;
                $transactionTimestamp = $transactionDateForFilter ? strtotime($transactionDateForFilter) : false;
                $transactionTimestampAttr = $transactionTimestamp !== false ? (string)$transactionTimestamp : '';
              ?>
              <tr data-transaction-ts="<?= htmlspecialchars($transactionTimestampAttr); ?>">
                <td>#<?= str_pad((int)$row['Transaction_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                <td><?= $row['Order_ID'] ? '#' . str_pad((int)$row['Order_ID'], 5, '0', STR_PAD_LEFT) : '—'; ?></td>
                <td><?= htmlspecialchars($row['Order_Date'] ?? '—'); ?></td>
                <td><?= htmlspecialchars($row['Payment_Date'] ?? '—'); ?></td>
                <td><?= htmlspecialchars($row['Customer'] ?? 'Walk-in'); ?></td>
                <td>₱<?= number_format((float)($row['Product_Total'] ?? 0), 0); ?></td>
                <td>₱<?= number_format((float)($row['Amount_Paid'] ?? 0), 0); ?></td>
                <td><?= htmlspecialchars($row['Payment_Method'] ?? 'Unknown'); ?></td>
                <td>
                  <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $row['Payment_Status'] ?? 'unknown')); ?>">
                    <?= htmlspecialchars($row['Payment_Status'] ?? 'Unknown'); ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($row['Reference_Number'] ?? '—'); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php
ob_start();
?>
<script>
  const revenueTrendDataByRange = <?= $revenueTrendDataJson; ?>;
  const revenueTrendDefaultRange = <?= $revenueTrendDefaultRangeJson; ?>;
  const methodLabelsRaw = <?= $methodLabelsJson; ?>;
  const methodLabels = methodLabelsRaw.length ? methodLabelsRaw : ['No Data'];
  const methodValuesRaw = <?= $methodValuesJson; ?>;
  const methodValues = methodValuesRaw.length ? methodValuesRaw : [0];
  const statusLabelsRaw = <?= $statusLabelsJson; ?>;
  const statusLabels = statusLabelsRaw.length ? statusLabelsRaw : ['No Data'];
  const statusValuesRaw = <?= $statusValuesJson; ?>;
  const statusValues = statusValuesRaw.length ? statusValuesRaw : [0];

  const revenueTrendCanvas = document.getElementById('revenueTrendChart');
  const revenueTrendRangeSelect = document.getElementById('revenueTrendRange');
  const revenueTrendRangeCaption = document.getElementById('revenueTrendRangeCaption');

  if (revenueTrendCanvas) {
    const ctx = revenueTrendCanvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, revenueTrendCanvas.height || 400);
    gradient.addColorStop(0, 'rgba(230, 126, 34, 0.4)');
    gradient.addColorStop(1, 'rgba(230, 126, 34, 0)');

    const rangeOptions = (revenueTrendDataByRange && typeof revenueTrendDataByRange === 'object' && !Array.isArray(revenueTrendDataByRange)) ? revenueTrendDataByRange : {};
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
        return Number.isFinite(numericValue) ? Math.round(numericValue) : 0;
      });
      const rangeLabel = dataset && typeof dataset.rangeLabel === 'string' ? dataset.rangeLabel : 'No revenue recorded yet';
      return { labels, values: sanitizedValues, rangeLabel };
    };

    const defaultRangeKey = typeof revenueTrendDefaultRange === 'string' && Object.prototype.hasOwnProperty.call(rangeOptions, revenueTrendDefaultRange)
      ? revenueTrendDefaultRange
      : (rangeKeys.length > 0 ? rangeKeys[0] : null);

    const initialDataset = getDatasetForRange(defaultRangeKey);
    const initialPointStyle = getPointStyling(initialDataset.labels.length);

    const revenueTrendChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: initialDataset.labels,
        datasets: [{
          label: 'Revenue (₱)',
          data: initialDataset.values,
          borderColor: '#e67e22',
          borderWidth: 3,
          backgroundColor: gradient,
          pointBackgroundColor: '#fff',
          pointBorderColor: '#e67e22',
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
                return 'Revenue: ₱' + Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
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
              callback: value => '₱' + Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })
            }
          }
        }
      }
    });

    if (revenueTrendRangeCaption) {
      revenueTrendRangeCaption.textContent = initialDataset.rangeLabel;
    }

    const updateRevenueTrendRange = rangeKey => {
      const dataset = getDatasetForRange(rangeKey);
      const pointStyle = getPointStyling(dataset.labels.length);
      revenueTrendChart.data.labels = dataset.labels;
      revenueTrendChart.data.datasets[0].data = dataset.values;
      revenueTrendChart.data.datasets[0].pointRadius = pointStyle.radius;
      revenueTrendChart.data.datasets[0].pointHoverRadius = pointStyle.hoverRadius;
      revenueTrendChart.update();
      if (revenueTrendRangeCaption) {
        revenueTrendRangeCaption.textContent = dataset.rangeLabel;
      }
    };

    if (revenueTrendRangeSelect) {
      if (defaultRangeKey) {
        revenueTrendRangeSelect.value = defaultRangeKey;
      }
      revenueTrendRangeSelect.addEventListener('change', event => {
        updateRevenueTrendRange(event.target.value);
      });
    }
  }

  if (document.getElementById('methodChart')) {
    new Chart(document.getElementById('methodChart'), {
      type: 'doughnut',
      data: {
        labels: methodLabels,
        datasets: [{
          data: methodValues,
          backgroundColor: ['#3498db', '#9b59b6', '#1abc9c', '#f1c40f', '#e74c3c']
        }]
      },
      options: {
        plugins: { legend: { position: 'bottom' } }
      }
    });
  }

  if (document.getElementById('statusChart')) {
    new Chart(document.getElementById('statusChart'), {
      type: 'bar',
      data: {
        labels: statusLabels,
        datasets: [{
          label: 'Revenue (₱)',
          data: statusValues,
          backgroundColor: '#2ecc71'
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => `₱${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}`
            }
          }
        }
      }
    });
  }

  const financeSearchInput = document.getElementById('financeSearch');
  const financeTimeRangeSelect = document.getElementById('financeTimeRange');
  const financeTableBody = document.querySelector('#financialTable tbody');

  const getSelectedFinanceRangeDays = () => {
    if (!financeTimeRangeSelect) {
      return null;
    }
    const selectedOption = financeTimeRangeSelect.options[financeTimeRangeSelect.selectedIndex];
    if (!selectedOption) {
      return null;
    }
    const dayValueRaw = selectedOption.dataset ? selectedOption.dataset.days : undefined;
    const parsedDays = dayValueRaw !== undefined ? Number.parseInt(dayValueRaw, 10) : NaN;
    if (!Number.isFinite(parsedDays) || parsedDays <= 0) {
      return null;
    }
    return parsedDays;
  };

  const getFinanceTimeRangeLabel = () => {
    if (!financeTimeRangeSelect) {
      return 'All Time';
    }
    const selectedOption = financeTimeRangeSelect.options[financeTimeRangeSelect.selectedIndex];
    if (!selectedOption) {
      return 'All Time';
    }
    const optionText = typeof selectedOption.textContent === 'string' ? selectedOption.textContent.trim() : '';
    if (optionText) {
      return optionText;
    }
    const optionLabel = typeof selectedOption.label === 'string' ? selectedOption.label.trim() : '';
    if (optionLabel) {
      return optionLabel;
    }
    return 'All Time';
  };

  const applyFinanceFilters = () => {
    if (!financeTableBody) {
      return;
    }
    const rows = Array.from(financeTableBody.querySelectorAll('tr'));
    if (!rows.length) {
      return;
    }
    const searchQuery = financeSearchInput ? financeSearchInput.value.trim().toLowerCase() : '';
    const rangeDays = getSelectedFinanceRangeDays();
    const hasRangeFilter = typeof rangeDays === 'number';
    const cutoffMs = hasRangeFilter ? Date.now() - rangeDays * 24 * 60 * 60 * 1000 : null;

    rows.forEach(row => {
      const matchesSearch = !searchQuery || row.textContent.toLowerCase().includes(searchQuery);
      let matchesRange = true;
      if (hasRangeFilter && cutoffMs !== null) {
        const tsAttr = row.getAttribute('data-transaction-ts');
        const tsSeconds = tsAttr ? Number.parseInt(tsAttr, 10) : NaN;
        if (Number.isFinite(tsSeconds)) {
          matchesRange = tsSeconds * 1000 >= cutoffMs;
        } else {
          matchesRange = false;
        }
      }
      row.style.display = matchesSearch && matchesRange ? '' : 'none';
    });
  };

  if (financeSearchInput) {
    financeSearchInput.addEventListener('input', applyFinanceFilters);
  }

  if (financeTimeRangeSelect) {
    financeTimeRangeSelect.addEventListener('change', applyFinanceFilters);
  }

  applyFinanceFilters();

  const hiddenClassTokens = ['hidden', 'is-hidden', 'd-none'];
  const rowIsHidden = row => {
    if (!row) {
      return true;
    }
    if (row.hidden) {
      return true;
    }
    if (row.getAttribute && row.getAttribute('aria-hidden') === 'true') {
      return true;
    }
    if (row.style && row.style.display === 'none') {
      return true;
    }
    if (row.classList && hiddenClassTokens.some(token => row.classList.contains(token))) {
      return true;
    }
    if (typeof window.getComputedStyle === 'function') {
      const computedStyle = window.getComputedStyle(row);
      if (computedStyle && computedStyle.display === 'none') {
        return true;
      }
    }
    return false;
  };

  const showFinancePdfPreview = (pdfDoc, filename) => {
    if (!pdfDoc || typeof pdfDoc.output !== 'function') {
      if (pdfDoc && typeof pdfDoc.save === 'function') {
        pdfDoc.save(filename);
      }
      return;
    }

    const supportsObjectUrl = typeof URL !== 'undefined' && typeof URL.createObjectURL === 'function';
    let blob = null;
    let blobUrl = null;

    if (supportsObjectUrl) {
      try {
        blob = pdfDoc.output('blob');
      } catch (error) {
        console.error('Failed to build PDF blob for preview:', error);
      }
      if (blob instanceof Blob) {
        blobUrl = URL.createObjectURL(blob);
      }
    }

    if (!blobUrl) {
      try {
        const dataUrl = pdfDoc.output('dataurlstring');
        if (dataUrl) {
          const previewWindow = window.open(dataUrl, '_blank', 'noopener');
          if (!previewWindow) {
            window.alert('Unable to open preview window. The PDF will be downloaded instead.');
            pdfDoc.save(filename);
          }
        } else {
          pdfDoc.save(filename);
        }
      } catch (error) {
        console.error('Failed to open PDF preview window:', error);
        pdfDoc.save(filename);
      }
      return;
    }

    const existingOverlay = document.getElementById('financePdfPreviewOverlay');
    if (existingOverlay) {
      existingOverlay.remove();
    }

    if (!document.body) {
      pdfDoc.save(filename);
      return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'financePdfPreviewOverlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Financial report PDF preview');
    overlay.style.cssText = [
      'position:fixed',
      'inset:0',
      'background:rgba(0,0,0,0.65)',
      'display:flex',
      'align-items:center',
      'justify-content:center',
      'padding:24px',
      'z-index:9999'
    ].join(';');

    const modal = document.createElement('div');
    modal.style.cssText = [
      'background:#ffffff',
      'max-width:960px',
      'width:100%',
      'height:85vh',
      'display:flex',
      'flex-direction:column',
      'border-radius:8px',
      'box-shadow:0 12px 40px rgba(0,0,0,0.25)',
      'overflow:hidden'
    ].join(';');

    const frame = document.createElement('iframe');
    frame.src = blobUrl;
    frame.title = 'Financial report preview';
    frame.style.cssText = ['flex:1', 'border:0'].join(';');

    const actions = document.createElement('div');
    actions.style.cssText = [
      'display:flex',
      'justify-content:flex-end',
      'gap:12px',
      'padding:12px 16px',
      'background:#f5f6fa',
      'border-top:1px solid #dcdde1'
    ].join(';');

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = 'Close';
    closeBtn.className = 'btn btn-secondary';
    closeBtn.style.cssText = [
      'padding:10px 18px',
      'border:none',
      'border-radius:4px',
      'background:#7f8c8d',
      'color:#fff',
      'cursor:pointer',
      'font-size:14px'
    ].join(';');

    const downloadBtn = document.createElement('button');
    downloadBtn.type = 'button';
    downloadBtn.textContent = 'Download PDF';
    downloadBtn.className = 'btn btn-primary';
    downloadBtn.style.cssText = [
      'padding:10px 18px',
      'border:none',
      'border-radius:4px',
      'background:#e67e22',
      'color:#fff',
      'cursor:pointer',
      'font-size:14px'
    ].join(';');

    actions.append(closeBtn, downloadBtn);
    modal.append(frame, actions);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    const cleanup = () => {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
      if (blobUrl) {
        URL.revokeObjectURL(blobUrl);
      }
      document.removeEventListener('keydown', handleKeyDown);
    };

    const handleKeyDown = event => {
      if (event.key === 'Escape') {
        cleanup();
      }
    };

    document.addEventListener('keydown', handleKeyDown);

    overlay.addEventListener('click', event => {
      if (event.target === overlay) {
        cleanup();
      }
    });

    closeBtn.addEventListener('click', cleanup);

    downloadBtn.addEventListener('click', () => {
      pdfDoc.save(filename);
      cleanup();
    });
  };

  const warnOnEmptyFilteredExport = true;
  const exportBtn = document.getElementById('exportFinance');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
        window.alert('PDF generator is not ready yet. Please try again in a moment.');
        return;
      }
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'landscape' });

      const rows = [];
      const visibleTimestamps = [];
      document.querySelectorAll('#financialTable tbody tr').forEach(tr => {
        if (rowIsHidden(tr)) {
          return;
        }
        const cells = Array.from(tr.cells).map(td => td.textContent.trim());
        rows.push(cells);
        const tsAttr = tr.getAttribute('data-transaction-ts');
        const tsSeconds = tsAttr ? Number.parseInt(tsAttr, 10) : NaN;
        if (Number.isFinite(tsSeconds)) {
          visibleTimestamps.push(tsSeconds * 1000);
        }
      });

      if (warnOnEmptyFilteredExport && rows.length === 0) {
        window.alert('No visible rows to export. Please adjust your filters and try again.');
        return;
      }

      const formatCoverageDate = timestampMs => {
        if (!Number.isFinite(timestampMs)) {
          return null;
        }
        const date = new Date(timestampMs);
        if (Number.isNaN(date.getTime())) {
          return null;
        }
        return date.toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        });
      };

      const timeRangeLabel = getFinanceTimeRangeLabel();
      let coverageDetails = '';
      if (visibleTimestamps.length > 0) {
        let earliestTimestamp = visibleTimestamps[0];
        let latestTimestamp = visibleTimestamps[0];
        for (let index = 1; index < visibleTimestamps.length; index += 1) {
          const currentTimestamp = visibleTimestamps[index];
          if (currentTimestamp < earliestTimestamp) {
            earliestTimestamp = currentTimestamp;
          }
          if (currentTimestamp > latestTimestamp) {
            latestTimestamp = currentTimestamp;
          }
        }
        const earliestLabel = formatCoverageDate(earliestTimestamp);
        const latestLabel = formatCoverageDate(latestTimestamp);
        if (earliestLabel && latestLabel) {
          coverageDetails = earliestLabel === latestLabel
            ? ` (${earliestLabel})`
            : ` (${earliestLabel} - ${latestLabel})`;
        }
      }
      const coverageText = `Time Range: ${timeRangeLabel}${coverageDetails}`;

      doc.setFontSize(16);
      doc.text('Financial Report', 14, 18);
      doc.setFontSize(11);
      doc.setTextColor(80);
      doc.text(coverageText, 14, 26);
      doc.setTextColor(0);

      doc.autoTable({
        startY: 34,
        head: [['Transaction ID', 'Order ID', 'Order Date', 'Payment Date', 'Customer', 'Product Total', 'Amount Paid', 'Method', 'Status', 'Reference']],
        body: rows,
        theme: 'grid',
        styles: { fontSize: 9 }
      });

      showFinancePdfPreview(doc, 'financial-report.pdf');
    });
  }
</script>
<?php
$extraScripts = ob_get_clean();
include 'includes/footer.php';
