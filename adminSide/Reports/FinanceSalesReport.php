<?php
$activePage = 'reports';
require_once '../../PHP/db_connect.php';

$reportData = [];
if ($pdo) {
    $stmt = $pdo->query(
        "SELECT t.Transaction_ID, o.Order_ID, o.Order_Date,
               u.Name AS Customer, IFNULL(SUM(oi.Subtotal), 0) AS Product_Total,
               t.Amount_Paid, t.Payment_Method, t.Payment_Status,
               t.Payment_Date, t.Reference_Number
        FROM `Transaction` t
        JOIN `Order` o ON t.Order_ID = o.Order_ID
        LEFT JOIN `User` u ON o.User_ID = u.User_ID
        LEFT JOIN `Order_Item` oi ON o.Order_ID = oi.Order_ID
        GROUP BY t.Transaction_ID, o.Order_ID, o.Order_Date, u.Name,
                 t.Amount_Paid, t.Payment_Method, t.Payment_Status,
                 t.Payment_Date, t.Reference_Number
        ORDER BY o.Order_Date DESC"
    );
    $reportData = $stmt->fetchAll();
} else {
    error_log('Database connection failed in FinanceSalesReport.php');
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
    <div class="filter-bar">
      <select>
        <option>Today</option>
        <option>Last 7 days</option>
        <option>Last 30 days</option>
        <option>This Month</option>
      </select>
      <button class="export-btn" onclick="exportTableToCSV()">Export CSV</button>
    </div>

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

    <table id="reportTable">
      <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Order ID</th>
          <th>Date</th>
          <th>Customer</th>
          <th>Product Total</th>
          <th>Amount Paid</th>
          <th>Payment Method</th>
          <th>Status</th>
          <th>Payment Date</th>
          <th>Reference Number</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reportData as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['Transaction_ID']); ?></td>
            <td><?php echo htmlspecialchars($row['Order_ID']); ?></td>
            <td><?php echo htmlspecialchars($row['Order_Date']); ?></td>
            <td><?php echo htmlspecialchars($row['Customer'] ?? ''); ?></td>
            <td>₱<?php echo number_format($row['Product_Total'], 2); ?></td>
            <td>₱<?php echo number_format($row['Amount_Paid'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['Payment_Method']); ?></td>
            <td><?php echo htmlspecialchars($row['Payment_Status']); ?></td>
            <td><?php echo htmlspecialchars($row['Payment_Date']); ?></td>
            <td><?php echo htmlspecialchars($row['Reference_Number'] ?? '--'); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </main>

  <script>
    const notifications = [
      "⚠️ Low Stock: Chocolate Cake (2 left)",
      "🛒 New Order #1245 from Bulacan",
      "💬 New customer feedback received"
    ];

    const notifList = document.getElementById("notifList");
    notifications.forEach(note => {
      const li = document.createElement("li");
      li.className = "px-4 py-2 hover:bg-gray-100 cursor-pointer";
      li.textContent = note;
      notifList.appendChild(li);
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

    function sanitizeCSVCell(value) {
      value = value.replace(/"/g, '""');
      if (/^[=+\-@]/.test(value)) {
        value = "'" + value;
      }
      return `"${value}"`;
    }

    function exportTableToCSV() {
      const table = document.getElementById("reportTable");
      let csv = [];
      for (let row of table.rows) {
        let cols = Array.from(row.cells).map(cell => sanitizeCSVCell(cell.innerText.trim()));
        csv.push(cols.join(","));
      }
      const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
      const link = document.createElement("a");
      link.setAttribute("href", csvContent);
      link.setAttribute("download", "finance_sales_report.csv");
      link.click();
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
