<?php
$activePage = 'reports';
require_once '../../PHP/db_connect.php';

$reportData = [];
$dateFilter = $_GET['date_filter'] ?? 'today';
$sortBy = $_GET['sort_by'] ?? 'Transaction_ID';
$allowedSorts = ['Transaction_ID','Order_ID','Order_Date','Customer','Product_Total','Amount_Paid','Payment_Method','Payment_Status','Payment_Date','Reference_Number'];
if (!in_array($sortBy, $allowedSorts, true)) {
    $sortBy = 'Transaction_ID';
}
$sortDir = strtolower($_GET['sort_dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$orderClause = "ORDER BY $sortBy $sortDir";
if ($sortBy !== 'Transaction_ID') {
    $orderClause .= ", Transaction_ID DESC";
}
$queryParams = $_GET;
function build_sort_link($column, $label, $sortBy, $sortDir, $queryParams) {
    $queryParams['sort_by'] = $column;
    $queryParams['sort_dir'] = ($sortBy === $column && $sortDir === 'ASC') ? 'desc' : 'asc';
    $url = '?' . http_build_query($queryParams);
    $url = htmlspecialchars($url, ENT_QUOTES);
    return "<a href=\"$url\" class=\"sort-link\">$label</a>";
}

function render_report_table($reportData, $sortBy, $sortDir, $queryParams) {
    ob_start();
    ?>
    <table id="reportTable">
      <thead>
        <tr>
          <th><?= build_sort_link('Transaction_ID', 'Transaction ID', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Order_ID', 'Order ID', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Order_Date', 'Date', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Customer', 'Customer', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Product_Total', 'Product Total', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Amount_Paid', 'Amount Paid', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Payment_Method', 'Payment Method', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Payment_Status', 'Status', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Payment_Date', 'Payment Date', $sortBy, $sortDir, $queryParams) ?></th>
          <th><?= build_sort_link('Reference_Number', 'Reference Number', $sortBy, $sortDir, $queryParams) ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reportData as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['Transaction_ID']) ?></td>
            <td><?= htmlspecialchars($row['Order_ID']) ?></td>
            <td><?= htmlspecialchars($row['Order_Date']) ?></td>
            <td><?= htmlspecialchars($row['Customer'] ?? '') ?></td>
            <td>₱<?= number_format($row['Product_Total'], 2) ?></td>
            <td>₱<?= number_format($row['Amount_Paid'], 2) ?></td>
            <td><?= htmlspecialchars($row['Payment_Method']) ?></td>
            <td><?= htmlspecialchars($row['Payment_Status']) ?></td>
            <td><?= htmlspecialchars($row['Payment_Date']) ?></td>
            <td><?= htmlspecialchars($row['Reference_Number'] ?? '--') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php
    return ob_get_clean();
}
if ($pdo) {
    $filterClause = '';
    switch ($dateFilter) {
        case 'today':
            $filterClause = "AND DATE(o.Order_Date) = CURDATE()";
            break;
        case 'last7':
            $filterClause = "AND o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case 'last30':
            $filterClause = "AND o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case 'last90':
            $filterClause = "AND o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
            break;
        case 'month':
            $filterClause = "AND MONTH(o.Order_Date) = MONTH(CURDATE()) AND YEAR(o.Order_Date) = YEAR(CURDATE())";
            break;
        case 'year':
            $filterClause = "AND o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            break;
        case 'all':
            // no additional filter
            break;
    }

    $stmt = $pdo->query(
        "SELECT t.Transaction_ID, o.Order_ID, o.Order_Date,
               u.Name AS Customer, IFNULL(SUM(oi.Subtotal), 0) AS Product_Total,
               t.Amount_Paid, t.Payment_Method, t.Payment_Status,
               t.Payment_Date, t.Reference_Number
        FROM `Transaction` t
        JOIN `Order` o ON t.Order_ID = o.Order_ID
        LEFT JOIN `User` u ON o.User_ID = u.User_ID
        LEFT JOIN `Order_Item` oi ON o.Order_ID = oi.Order_ID
        WHERE 1=1 $filterClause
        GROUP BY t.Transaction_ID, o.Order_ID, o.Order_Date, u.Name,
                 t.Amount_Paid, t.Payment_Method, t.Payment_Status,
                 t.Payment_Date, t.Reference_Number
        {$orderClause}"
    );
    $reportData = $stmt->fetchAll();
} else {
    error_log('Database connection failed in FinanceSalesReport.php');
}

$tableHtml = render_report_table($reportData, $sortBy, $sortDir, $queryParams);
if (isset($_GET['ajax'])) {
    echo $tableHtml;
    exit;
}

$pageTitle = 'Finance & Sales Report - Admin';
$headerTitle = 'Finance & Sales Report';
$bodyClass = 'sales-report finance-page';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="main flex-1 overflow-y-auto">
    <?php include $prefix . 'topbar.php'; ?>
    <p class="px-6 py-2 text-sm text-gray-700">Overview of store sales and finance performance</p>

    <!-- Sales Filter -->
    <form method="get" class="filter-bar">
      <select name="date_filter" onchange="this.form.submit()">
        <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
        <option value="last7" <?= $dateFilter === 'last7' ? 'selected' : '' ?>>Last 7 days</option>
        <option value="last30" <?= $dateFilter === 'last30' ? 'selected' : '' ?>>Last 30 days</option>
        <option value="last90" <?= $dateFilter === 'last90' ? 'selected' : '' ?>>Last 3 months</option>
        <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>This Month</option>
        <option value="year" <?= $dateFilter === 'year' ? 'selected' : '' ?>>Past Year</option>
        <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
      </select>
      <button type="button" class="export-btn" onclick="exportTableToPDF()">Export PDF</button>
    </form>

    <!-- Sales Summary Cards -->
    <div class="summary-cards">
      <div class="card"><h3>Total Sales</h3><p>₱4,572.84</p></div>
      <div class="card"><h3>Total Orders</h3><p>25</p></div>
      <div class="card"><h3>Shipping Fees</h3><p>₱4,000</p></div>
    </div>

    <!-- Sales Chart -->
    <div class="chart-container">
      <h3>Monthly Sales Chart</h3>
      <canvas id="salesChart"></canvas>
    </div>

    <h2 class="px-6 py-4 text-xl font-semibold">Finance & Sales Records</h2>
    <div class="chart-section">
      <div class="chart-wrapper">
        <canvas id="barChart"></canvas>
      </div>
      <div class="chart-wrapper">
        <canvas id="pieChart"></canvas>
      </div>
    </div>

    <?= $tableHtml ?>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script>
      console.log('Initializing finance report notifications');
      const notifications = [
        "⚠️ Low Stock: Chocolate Cake (2 left)",
        "🛒 New Order #1245 from Bulacan",
        "💬 New customer feedback received"
      ];

      const notifList = document.getElementById("notifList");
      if (!notifList) {
        console.warn('notifList element not found in finance report');
      } else {
        console.log('notifList element found in finance report');
      }

      notifications.forEach((note, index) => {
        console.log(`Appending notification ${index + 1}:`, note);
        const li = document.createElement("li");
        li.className = "px-4 py-2 hover:bg-gray-100 cursor-pointer";
        li.textContent = note;
        notifList.appendChild(li);
      });
      console.log('Finished populating finance report notifications');

      document.addEventListener('click', function(e) {
        const link = e.target.closest('a.sort-link');
        if (link) {
          e.preventDefault();
          fetch(link.href + '&ajax=1')
            .then(resp => resp.text())
            .then(html => {
              const table = document.getElementById('reportTable');
              table.outerHTML = html;
              window.history.replaceState({}, '', link.href);
            })
            .catch(err => console.error('Sort fetch failed', err));
        }
      });

    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jun 1', 'Jun 5', 'Jun 10', 'Jun 15', 'Jun 20', 'Jun 25', 'Jun 30'],
        datasets: [{
          label: 'Daily Sales (₱)',
          data: [3500, 5000, 6500, 7200, 5800, 6100, 7300],
          backgroundColor: '#4dabf7',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => '₱' + value
            }
          }
        }
      }
    });

    function exportTableToPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF({ orientation: 'landscape' });
      doc.setFontSize(10);
      doc.text('Finance & Sales Report', 14, 15);

      const headers = [
        'Transaction ID',
        'Order ID',
        'Date',
        'Customer',
        'Product Total',
        'Amount Paid',
        'Payment Method',
        'Status',
        'Payment Date',
        'Reference Number'
      ];

      const rows = [];
      document.querySelectorAll('#reportTable tbody tr').forEach(row => {
        const cells = Array.from(row.cells).map(cell => cell.innerText.trim());
        rows.push(cells);
      });

      doc.autoTable({
        head: [headers],
        body: rows,
        startY: 20,
        styles: { fontSize: 8 }
      });

      doc.save('finance_sales_report.pdf');
    }

    const barCtx = document.getElementById("barChart").getContext("2d");
    new Chart(barCtx, {
      type: "bar",
      data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
        datasets: [
          {
            label: "Monthly Total Sales (₱)",
            data: [1200, 1900, 3000, 2500, 3200, 2800],
            backgroundColor: "#007bff",
          },
        ],
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
        },
      },
    });

    const pieCtx = document.getElementById("pieChart").getContext("2d");
    new Chart(pieCtx, {
      type: "pie",
      data: {
        labels: ["COD", "GCash"],
        datasets: [
          {
            label: "Payment Method Breakdown",
            data: [1, 2],
            backgroundColor: ["#ffce56", "#36a2eb"],
          },
        ],
      },
      options: {
        responsive: true,
      },
    });
  </script>
</div>
</body>
</html>
