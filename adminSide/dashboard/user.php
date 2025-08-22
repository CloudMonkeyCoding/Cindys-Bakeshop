<?php
require_once '../../PHP/db_connect.php';
require_once '../../PHP/blacklist_functions.php';
$activePage = 'users';
$pageTitle = 'Users - Cindy’s Bakeshop';
$headerTitle = 'Users';
$bodyClass = 'users-page';
include '../header.php';

$allUsers = [];
$blockedUsers = [];
if ($pdo) {
    $allStmt = $pdo->query("SELECT User_ID, Name, Email FROM user");
    $allUsers = $allStmt ? $allStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $sql = "SELECT b.Blacklist_ID, u.Name, u.Email, oc.Cancellation_Date AS Date_Blocked,
                   b.Blacklist_reason AS Reason, p.Name AS Product_Name
            FROM blacklist b
            JOIN user u ON b.User_ID = u.User_ID
            LEFT JOIN order_cancellation oc ON b.User_ID = oc.User_ID
            LEFT JOIN order_item oi ON oc.Order_ID = oi.Order_ID
            LEFT JOIN product p ON oi.Product_ID = p.Product_ID
            GROUP BY b.Blacklist_ID";
    $stmt = $pdo->query($sql);
    $blockedUsers = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>
  <main class="flex-1 overflow-y-auto">
      <?php include $prefix . 'topbar.php'; ?>
      <div class="p-6">
        <div class="tabs mb-4">
          <button id="allUsersBtn" class="tab-button active" onclick="showAllUsers()">All Users</button>
          <button id="blockedUsersBtn" class="tab-button" onclick="showBlockedUsers()">Blocked Users</button>
        </div>
        <div class="filter-bar flex gap-4 mb-4">
          <input type="text" id="searchInput" placeholder="Search by name or email..." class="border rounded px-2 py-1 text-sm">

          <select id="cakeFilter" class="border rounded px-2 py-1 text-sm hidden">
            <option value="All">All Cancelled Cakes</option>
            <option value="Chocolate Cake">Chocolate Cake</option>
            <option value="Red Velvet Cake">Red Velvet Cake</option>
            <option value="Cheesecake">Cheesecake</option>
            <option value="Ube Macapuno Cake">Ube Macapuno Cake</option>
          </select>
        </div>
        <table id="allUsersTable" class="table w-full text-sm text-left bg-white rounded shadow">
          <thead class="border-b">
            <tr>
              <th class="py-2">Name</th>
              <th>Email</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($allUsers as $user): ?>
            <tr>
              <td><?php echo htmlspecialchars($user['Name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($user['Email'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <table id="blockedUsersTable" class="table w-full text-sm text-left bg-white rounded shadow hidden">
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
            <?php foreach ($blockedUsers as $user): ?>
            <tr data-blacklist-id="<?php echo htmlspecialchars($user['Blacklist_ID']); ?>">
              <td><?php echo htmlspecialchars($user['Name'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($user['Email'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($user['Date_Blocked'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($user['Reason'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($user['Product_Name'] ?? ''); ?></td>
              <td><button class="unblock-btn bg-green-500 text-white px-3 py-1 rounded">Unblock</button></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

<script>
  const searchInput = document.getElementById('searchInput');
  const cakeFilter = document.getElementById('cakeFilter');
  const allUsersTable = document.getElementById('allUsersTable');
  const blockedUsersTable = document.getElementById('blockedUsersTable');
  const allUsersBtn = document.getElementById('allUsersBtn');
  const blockedUsersBtn = document.getElementById('blockedUsersBtn');

  function filterTable() {
    const query = searchInput.value.toLowerCase();
    if (!allUsersTable.classList.contains('hidden')) {
      allUsersTable.querySelectorAll('tbody tr').forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        const matchesSearch = name.includes(query) || email.includes(query);
        row.style.display = matchesSearch ? '' : 'none';
      });
    } else {
      const selectedCake = cakeFilter.value;
      blockedUsersTable.querySelectorAll('tbody tr').forEach(row => {
        const name = row.cells[0].textContent.toLowerCase();
        const email = row.cells[1].textContent.toLowerCase();
        const cake = row.cells[4].textContent;
        const matchesSearch = name.includes(query) || email.includes(query);
        const matchesCake = selectedCake === "All" || cake === selectedCake;
        row.style.display = (matchesSearch && matchesCake) ? '' : 'none';
      });
    }
  }

  searchInput.addEventListener('input', filterTable);
  cakeFilter.addEventListener('change', filterTable);

  document.querySelectorAll('#blockedUsersTable .unblock-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      const id = row.getAttribute('data-blacklist-id');
      const fd = new FormData();
      fd.append('action', 'unblock');
      fd.append('blacklist_id', id);
      fetch('../../PHP/blacklist_api.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            row.remove();
            filterTable();
          } else {
            alert('Unblock failed');
          }
        })
        .catch(() => alert('Unblock failed'));
    });
  });

  function showAllUsers() {
    allUsersTable.classList.remove('hidden');
    blockedUsersTable.classList.add('hidden');
    allUsersBtn.classList.add('active');
    blockedUsersBtn.classList.remove('active');
    cakeFilter.classList.add('hidden');
    filterTable();
  }

  function showBlockedUsers() {
    blockedUsersTable.classList.remove('hidden');
    allUsersTable.classList.add('hidden');
    blockedUsersBtn.classList.add('active');
    allUsersBtn.classList.remove('active');
    cakeFilter.classList.remove('hidden');
    filterTable();
  }
</script>


</body>
</html>
