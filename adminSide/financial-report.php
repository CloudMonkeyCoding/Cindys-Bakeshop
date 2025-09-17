<?php
require_once '../PHP/db_connect.php';

$activePage = 'financial-report';
$pageTitle = "Financial Report - Cindy's Bakeshop";

$totalRevenue = 0.0;
$totalTransactions = 0;
$uniqueOrders = 0;
$averageOrderValue = 0.0;
$lastPaymentDate = null;
$settledRevenue = 0.0;
$pendingReceivables = 0.0;
$monthlyTotals = [];
$paymentMethods = [];
$statusBreakdown = [];
$reportRows = [];

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

    $stmtMonthly = $pdo->query("SELECT DATE_FORMAT(Payment_Date, '%Y-%m') AS period, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction WHERE Payment_Date IS NOT NULL GROUP BY period ORDER BY period DESC LIMIT 12");
    if ($stmtMonthly) {
        $rows = $stmtMonthly->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows);
        foreach ($rows as $row) {
            $monthlyTotals[] = [
                'label' => date('M Y', strtotime($row['period'] . '-01')),
                'value' => (float)$row['total']
            ];
        }
    }

    $stmtMethods = $pdo->query("SELECT COALESCE(Payment_Method, 'Unknown') AS method, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction GROUP BY method ORDER BY total DESC");
    if ($stmtMethods) {
        $paymentMethods = $stmtMethods->fetchAll(PDO::FETCH_ASSOC);
    }

    $stmtStatus = $pdo->query("SELECT COALESCE(Payment_Status, 'Unknown') AS status, COUNT(*) AS count, COALESCE(SUM(Amount_Paid),0) AS total FROM transaction GROUP BY status ORDER BY total DESC");
    if ($stmtStatus) {
        $statusBreakdown = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
        $pendingStates = ['pending', 'processing', 'awaiting payment', 'unpaid', 'on hold'];
        $settledStates = ['paid', 'completed', 'settled', 'success', 'received', 'fulfilled'];

        foreach ($statusBreakdown as $row) {
            $normalized = strtolower($row['status']);
            $amount = (float)($row['total'] ?? 0);
            if (in_array($normalized, $pendingStates, true)) {
                $pendingReceivables += $amount;
            } elseif (in_array($normalized, $settledStates, true)) {
                $settledRevenue += $amount;
            }
        }
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

$monthlyLabelsJson = json_encode(array_column($monthlyTotals, 'label'));
$monthlyValuesJson = json_encode(array_map(function ($item) {
    return round($item['value'], 2);
}, $monthlyTotals));
$methodLabelsJson = json_encode(array_map(function ($item) {
    return $item['method'];
}, $paymentMethods));
$methodValuesJson = json_encode(array_map(function ($item) {
    return round((float)$item['total'], 2);
}, $paymentMethods));
$statusLabelsJson = json_encode(array_map(function ($item) {
    return $item['status'];
}, $statusBreakdown));
$statusValuesJson = json_encode(array_map(function ($item) {
    return round((float)$item['total'], 2);
}, $statusBreakdown));

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
      <button class="btn btn-primary" id="exportFinance">Export Finance PDF</button>
    </div>
  </div>

  <section class="stats-grid columns-4" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
    <div class="stat-card">
      <h3>Total Revenue</h3>
      <div class="value">₱<?= number_format($totalRevenue, 2); ?></div>
      <div class="meta">Across <?= number_format($totalTransactions); ?> payments</div>
    </div>
    <div class="stat-card">
      <h3>Settled Revenue</h3>
      <div class="value">₱<?= number_format($settledRevenue, 2); ?></div>
      <div class="meta">Paid/Completed statuses</div>
    </div>
    <div class="stat-card">
      <h3>Pending Receivables</h3>
      <div class="value">₱<?= number_format($pendingReceivables, 2); ?></div>
      <div class="meta">Awaiting confirmation</div>
    </div>
    <div class="stat-card">
      <h3>Average Order Value</h3>
      <div class="value">₱<?= number_format($averageOrderValue, 2); ?></div>
      <div class="meta">Based on <?= number_format($uniqueOrders); ?> orders</div>
    </div>
  </section>

  <div class="stats-grid columns-4" style="margin-top:24px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));">
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Monthly Revenue</h2>
      <canvas id="revenueChart" height="220"></canvas>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Payment Methods</h2>
      <canvas id="methodChart" height="220"></canvas>
    </div>
    <div class="card">
      <h2 style="font-size:18px;margin-bottom:16px;">Revenue by Status</h2>
      <canvas id="statusChart" height="220"></canvas>
    </div>
  </div>

  <div class="card" style="margin-top:24px;">
    <div class="table-actions">
      <input type="text" id="financeSearch" placeholder="🔍 Search payment record...">
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
              <tr>
                <td>#<?= str_pad((int)$row['Transaction_ID'], 5, '0', STR_PAD_LEFT); ?></td>
                <td><?= $row['Order_ID'] ? '#' . str_pad((int)$row['Order_ID'], 5, '0', STR_PAD_LEFT) : '—'; ?></td>
                <td><?= htmlspecialchars($row['Order_Date'] ?? '—'); ?></td>
                <td><?= htmlspecialchars($row['Payment_Date'] ?? '—'); ?></td>
                <td><?= htmlspecialchars($row['Customer'] ?? 'Walk-in'); ?></td>
                <td>₱<?= number_format((float)($row['Product_Total'] ?? 0), 2); ?></td>
                <td>₱<?= number_format((float)($row['Amount_Paid'] ?? 0), 2); ?></td>
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
$extraScripts = <<<'JS'
<script>
  const monthlyLabels = $monthlyLabelsJson.length ? $monthlyLabelsJson : ['No Data'];
  const monthlyValues = $monthlyValuesJson.length ? $monthlyValuesJson : [0];
  const methodLabels = $methodLabelsJson.length ? $methodLabelsJson : ['No Data'];
  const methodValues = $methodValuesJson.length ? $methodValuesJson : [0];
  const statusLabels = $statusLabelsJson.length ? $statusLabelsJson : ['No Data'];
  const statusValues = $statusValuesJson.length ? $statusValuesJson : [0];

  if (document.getElementById('revenueChart')) {
    new Chart(document.getElementById('revenueChart'), {
      type: 'line',
      data: {
        labels: monthlyLabels,
        datasets: [{
          label: 'Revenue (₱)',
          data: monthlyValues,
          fill: false,
          borderColor: '#e67e22',
          tension: 0.25,
          backgroundColor: '#e67e22'
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => `₱${Number(value).toLocaleString()}`
            }
          }
        }
      }
    });
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
              callback: value => `₱${Number(value).toLocaleString()}`
            }
          }
        }
      }
    });
  }

  const searchInput = document.getElementById('financeSearch');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase();
      document.querySelectorAll('#financialTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  const exportBtn = document.getElementById('exportFinance');
  if (exportBtn) {
    exportBtn.addEventListener('click', () => {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'landscape' });
      doc.setFontSize(16);
      doc.text('Financial Report', 14, 18);

      const rows = [];
      document.querySelectorAll('#financialTable tbody tr').forEach(tr => {
        const cells = Array.from(tr.cells).map(td => td.textContent.trim());
        rows.push(cells);
      });

      doc.autoTable({
        startY: 26,
        head: [['Transaction ID', 'Order ID', 'Order Date', 'Payment Date', 'Customer', 'Product Total', 'Amount Paid', 'Method', 'Status', 'Reference']],
        body: rows,
        theme: 'grid',
        styles: { fontSize: 9 }
      });

      doc.save('financial-report.pdf');
    });
  }
</script>
JS;
include 'includes/footer.php';
