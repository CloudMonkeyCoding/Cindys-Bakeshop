<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cindy's Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
  <body class="dashboard-page">
  <div class="flex min-h-screen">
    <?php
    $activePage = 'dashboard';
    include '../sidebar.php';
    ?>


    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
      <div class="header-bar">
        <h1>Dashboard</h1>
        <div class="flex items-center gap-4">
          <div class="relative">
            <button onclick="toggleDropdown()" class="relative focus:outline-none">
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
          <img src="avatar.png" alt="User Avatar" class="h-10 w-10 rounded-full border border-gray-300" />
        </div>
      </div>
      <div class="header-stats-container">
        <a href="../ORDERS/ManageOrders.php" class="text-center hover:underline">
          <div class="font-semibold text-gray-800">Orders </div>
          <div class="text-lg font-bold">25</div>
        </a>
        <a href="../Reports/InventoryReport.php" class="text-center hover:underline">
          <div class="font-semibold text-gray-800">Low Stock</div>
          <div class="text-red-600 font-bold text-lg">19</div>
          <div class="text-xs text-red-500">Out of stock: 19</div>
        </a>
        <a href="Ratings.php" class="text-center hover:underline">
          <div class="font-semibold text-gray-800">Product Ratings</div>
          <div class="text-lg font-bold">25</div>
        </a>
      </div>

      <!-- Welcome Message -->
      <div class="px-6 mt-6">
        <h1 class="text-2xl font-bold text-gray-800">Welcome back, Admin!</h1>
        <p class="text-sm text-gray-500">Here's a summary of today's store performance </p>
      </div>

      <!-- Dashboard Body -->
      <section class="p-6 flex flex-wrap gap-6 items-start">
        <!-- Sales Card with Filter -->
        <div class="bg-white p-4 rounded shadow w-full max-w-sm">
          <div class="flex justify-between items-center mb-2">
            <h2 class="text-base font-bold">Sales</h2>
            <select id="salesFilter" class="text-sm border rounded px-2 py-1">
              <option value="today">Today</option>
              <option value="7days">Last 7 Days</option>
              <option value="month">Last Month</option>
            </select>
          </div>
          <div id="salesAmount" class="text-2xl font-bold text-green-600 mt-3">₱ 4,572.84</div>
          <p class="text-sm text-gray-600 mt-1">Orders: <span id="orderCount">25</span></p>
        </div>

        <!-- Additional Cards -->
        <div class="bg-white p-4 rounded shadow w-full max-w-sm">
          <h2 class="text-base font-bold mb-2">Payment Method Breakdown</h2>
          <canvas id="paymentChart" height="120"></canvas>
        </div>

        <div class="bg-white p-4 rounded shadow w-full max-w-sm">
          <h2 class="text-base font-bold mb-2">Top-Selling Product</h2>
          <div class="flex items-center gap-4">
            <img src="bread1.png" class="w-16 h-16 rounded" alt="Top Product">
            <div>
              <div class="font-semibold">Cheesy Ensaymada</div>
              <div class="text-sm text-gray-500">Sold: 152 pcs</div>
            </div>
          </div>
        </div>

        <div class="bg-white p-4 rounded shadow w-full max-w-sm">
          <h2 class="text-base font-bold mb-2">Customer Feedback</h2>
          <p class="text-sm text-gray-700">“Laging fresh at super sarap! 😋”</p>
          <p class="text-xs text-gray-400 mt-2">- Ana M., July 6</p>
        </div>

        <div class="bg-white p-4 rounded shadow w-full max-w-sm">
          <h2 class="text-base font-bold mb-2">New Customers</h2>
          <div class="text-3xl font-bold text-blue-500">12</div>
          <p class="text-sm text-gray-600">This week</p>
        </div>

        <div class="bg-white p-4 rounded shadow w-full">
          <h2 class="text-base font-bold mb-2">Monthly Total Sales (₱)</h2>
          <canvas id="salesChart" height="130"></canvas>
        </div>
      </section>
    </main>
  </div>

  <!-- Script Section -->
  <script>
    function toggleDropdown() {
      document.getElementById('notificationDropdown').classList.toggle('hidden');
    }

    window.addEventListener('click', function (e) {
      const bell = document.querySelector('button[onclick="toggleDropdown()"]');
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

    // Sales filter simulation
    const salesAmount = document.getElementById("salesAmount");
    const orderCount = document.getElementById("orderCount");
    const salesData = {
      today: { amount: "₱ 4,572.84", orders: 25 },
      "7days": { amount: "₱ 32,100.25", orders: 180 },
      month: { amount: "₱ 140,810.00", orders: 960 }
    };

    document.getElementById("salesFilter").addEventListener("change", function () {
      const selected = this.value;
      salesAmount.textContent = salesData[selected].amount;
      orderCount.textContent = salesData[selected].orders;
    });

    new Chart(document.getElementById("paymentChart"), {
      type: 'doughnut',
      data: {
        labels: ['Gcash', 'COD'],
        datasets: [{ data: [60, 40], backgroundColor: ['#00C49F', '#FF8042'] }]
      },
      options: { plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById("salesChart"), {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
          label: 'Sales (₱)',
          data: [1000, 1100, 950, 1200, 1300, 1250],
          backgroundColor: '#6366F1',
          borderRadius: 6,
          barPercentage: 0.5
        }]
      },
      options: {
        responsive: true,
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: value => `₱${value}`
            }
          }
        }
      }
    });
  </script>
</body>
</html>
