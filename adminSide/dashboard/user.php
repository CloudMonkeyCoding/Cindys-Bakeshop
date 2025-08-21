<?php
$activePage = 'users';
$pageTitle = 'Users - Cindy’s Bakeshop';
$bodyClass = 'users-page';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto">
      <div class="header-bar">
        <h1>Users</h1>
        <div class="flex gap-4 items-center">
          <svg class="w-6 h-6 text-black" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <img src="avatar.png" alt="User Avatar" class="h-10 w-10 rounded-full border border-gray-300" />
        </div>
      </div>
      <div class="p-6">
        <div class="tabs mb-4">
          <button class="tab-button" onclick="showAllUsers()">All Users</button>
          <button class="tab-button active" onclick="showBlockedUsers()">Blocked Users</button>
        </div>
        <div class="filter-bar flex gap-4 mb-4">
          <input type="text" id="searchInput" placeholder="Search by name or email..." class="border rounded px-2 py-1 text-sm">

          <select id="cakeFilter" class="border rounded px-2 py-1 text-sm">
            <option value="All">All Cancelled Cakes</option>
            <option value="Chocolate Cake">Chocolate Cake</option>
            <option value="Red Velvet Cake">Red Velvet Cake</option>
            <option value="Cheesecake">Cheesecake</option>
            <option value="Ube Macapuno Cake">Ube Macapuno Cake</option>
          </select>
        </div>
        <table id="usersTable" class="table w-full text-sm text-left bg-white rounded shadow">
          <thead class="border-b">
            <tr>
              <th class="py-2">Name</th>
              <th>Email</th>
              <th>Date Blocked</th>
              <th>Reason</th>
              <th>Cancelled Product</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Maria Andoks</td>
              <td>maria@example.com</td>
              <td>2025-07-05</td>
              <td>Violation of terms</td>
              <td>Red Velvet Cake</td>
              <td><button class="unblock-btn bg-green-500 text-white px-3 py-1 rounded">Unblock</button></td>
            </tr>
            <tr>
              <td>Juan Dela Cruz</td>
              <td>juancruz@example.com</td>
              <td>2025-06-22</td>
              <td>Spam activity</td>
              <td>Chocolate Cake</td>
              <td><button class="unblock-btn bg-green-500 text-white px-3 py-1 rounded">Unblock</button></td>
            </tr>
            <tr>
              <td>Ana Reyes</td>
              <td>ana.reyes@example.com</td>
              <td>2025-07-03</td>
              <td>Fake orders</td>
              <td>Cheesecake</td>
              <td><button class="unblock-btn bg-green-500 text-white px-3 py-1 rounded">Unblock</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
  </div>

<script>
  const searchInput = document.getElementById('searchInput');
  const cakeFilter = document.getElementById('cakeFilter');
  const rows = document.querySelectorAll("#usersTable tbody tr");

  function filterTable() {
    const query = searchInput.value.toLowerCase();
    const selectedCake = cakeFilter.value;

    rows.forEach(row => {
      const name = row.cells[0].textContent.toLowerCase();
      const email = row.cells[1].textContent.toLowerCase();
      const cake = row.cells[4].textContent;

      const matchesSearch = name.includes(query) || email.includes(query);
      const matchesCake = selectedCake === "All" || cake === selectedCake;

      row.style.display = (matchesSearch && matchesCake) ? "" : "none";
    });
  }

  searchInput.addEventListener('input', filterTable);
  cakeFilter.addEventListener('change', filterTable);

  function showAllUsers() {}
  function showBlockedUsers() {}
</script>


</body>
</html>
