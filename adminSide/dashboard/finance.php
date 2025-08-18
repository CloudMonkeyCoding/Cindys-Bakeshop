<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <title>Finance Dashboard - Cindy's Bakeshop</title>
      <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
      <link rel="stylesheet" href="../css/admin.css">
      <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  </head>
      <body class="finance-page">
      <div class="flex h-screen overflow-hidden">
        <?php
        $activePage = 'finance';
        include '../sidebar.php';
        ?>
        <main class="flex-1 overflow-y-auto">
          <div class="header-bar">
            <h1>Finance Transaction Records</h1>
            <div>🔔 👤</div>
          </div>

        <div class="chart-section">
        <div class="chart-wrapper">
          <canvas id="barChart"></canvas>
        </div>
        <div class="chart-wrapper">
          <canvas id="pieChart"></canvas>
        </div>
      </div>

      <div class="filter-row">
        <input type="text" placeholder="Search Order ID" />
        <select>
          <option>All Status</option>
          <option>Paid</option>
          <option>Pending</option>
          <option>Failed</option>
        </select>
        <select id="paymentFilter">
          <option value="All">All Payment Methods</option>
          <option value="GCash">GCash</option>
          <option value="COD">COD</option>
        </select>

        <input type="date" /> to <input type="date" />
        <button class="export-btn">Export CSV</button>
      </div>

      <table>
        <thead>
          <tr>
            <th>Transaction ID</th>
            <th>Order ID</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Payment Date</th>
            <th>Amount Paid</th>
            <th>Reference Number</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>TXN-0001</td>
            <td>ORD-1025</td>
            <td>COD</td>
            <td>Paid</td>
            <td>2025-06-23</td>
            <td>₱250.00</td>
            <td>--</td>
          </tr>
          <tr>
            <td>TXN-0003</td>
            <td>ORD-1027</td>
            <td>GCash</td>
            <td>Pending</td>
            <td>2025-06-23</td>
            <td>₱800.00</td>
            <td>GC987654321</td>
          </tr>
        </tbody>
      </table>
        </main>
      </div>

    <script>
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
