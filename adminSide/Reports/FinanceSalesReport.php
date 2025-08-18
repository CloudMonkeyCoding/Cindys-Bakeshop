<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Finance & Sales Report - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="sales-report finance-page flex h-screen overflow-hidden">
  <?php
  $activePage = 'reports';
  include '../sidebar.php';
  require_once '../../PHP/db_connect.php';

    $reportData = [];
    if ($pdo) {
        $stmt = $pdo->query("
        SELECT t.Transaction_ID, o.Order_ID, o.Order_Date,
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
        ORDER BY o.Order_Date DESC
    ");
        $reportData = $stmt->fetchAll();
    } else {
        error_log('Database connection failed in FinanceSalesReport.php');
    }
  ?>

  <!-- Main Content -->
  <main class="main flex-1 overflow-y-auto">
    <div class="header-bar">
      <h1>Finance & Sales Report</h1>
      <div class="flex items-center gap-4">
        <div class="relative">
          <button onclick="toggleNotificationDropdown()" class="relative focus:outline-none">
            <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="absolute top-0 right-0 block h-2 w-2 bg-red-600 rounded-full"></span>
          </button>
          <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded shadow-lg z-50">
            <div class="p-3 border-b font-semibold text-gray-700">Notifications</div>
            <ul class="max-h-60 overflow-y-auto text-sm" id="notifList"></ul>
          </div>
        </div>
        <img src="https://i.imgur.com/1Q2Z1ZL.png" alt="User" class="h-10 w-10 rounded-full border border-gray-300" />
      </div>
    </div>
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
    function toggleNotificationDropdown() {
      document.getElementById('notificationDropdown').classList.toggle('hidden');
    }

    window.addEventListener('click', function (e) {
      const bell = document.querySelector('button[onclick="toggleNotificationDropdown()"]');
      const dropdown = document.getElementById('notificationDropdown');
      if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.add('hidden');
      }
    });

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
</body>
</html>
