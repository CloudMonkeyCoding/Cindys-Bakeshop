<?php
require_once '../PHP/db_connect.php';
require_once '../PHP/blacklist_functions.php';

$activePage = 'users';
$pageTitle = "Users - Cindy's Bakeshop";

$allUsers = [];
$blockedUsers = [];

if ($pdo) {
    $stmt = $pdo->query("SELECT User_ID, Name, Email FROM user");
    $allUsers = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    $sql = "SELECT b.Blacklist_ID, u.Name, u.Email, oc.Cancellation_Date AS Date_Blocked, b.Blacklist_reason AS Reason,
                   p.Name AS Product_Name, b.User_ID
            FROM blacklist b
            JOIN user u ON b.User_ID = u.User_ID
            LEFT JOIN order_cancellation oc ON b.User_ID = oc.User_ID
            LEFT JOIN order_item oi ON oc.Order_ID = oi.Order_ID
            LEFT JOIN product p ON oi.Product_ID = p.Product_ID
            GROUP BY b.Blacklist_ID";
    $stmtBlocked = $pdo->query($sql);
    $blockedUsers = $stmtBlocked ? $stmtBlocked->fetchAll(PDO::FETCH_ASSOC) : [];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Users</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <div class="table-container">
    <div class="table-actions">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <button class="btn btn-secondary" id="showAll">All Users</button>
        <button class="btn btn-muted" id="showBlocked">Blocked Users</button>
      </div>
      <input type="text" id="userSearch" placeholder="🔍 Search user...">
      <select id="blockedFilter" style="display:none;">
        <option value="all">All Cancelled Products</option>
        <option value="Chocolate Cake">Chocolate Cake</option>
        <option value="Red Velvet Cake">Red Velvet Cake</option>
        <option value="Cheesecake">Cheesecake</option>
        <option value="Ube Macapuno Cake">Ube Macapuno Cake</option>
      </select>
    </div>

    <table id="allUsersTable">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($allUsers)): ?>
          <tr><td colspan="2" class="table-empty">No users found.</td></tr>
        <?php else: ?>
          <?php foreach ($allUsers as $user): ?>
            <tr>
              <td><?= htmlspecialchars($user['Name'] ?? ''); ?></td>
              <td><?= htmlspecialchars($user['Email'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <table id="blockedUsersTable" style="display:none;margin-top:20px;">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Date Blocked</th>
          <th>Reason</th>
          <th>Cancelled Product</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($blockedUsers)): ?>
          <tr><td colspan="6" class="table-empty">No blocked users.</td></tr>
        <?php else: ?>
          <?php foreach ($blockedUsers as $user): ?>
            <tr data-blacklist-id="<?= $user['Blacklist_ID']; ?>" data-product="<?= htmlspecialchars($user['Product_Name'] ?? ''); ?>">
              <td><?= htmlspecialchars($user['Name'] ?? ''); ?></td>
              <td><?= htmlspecialchars($user['Email'] ?? ''); ?></td>
              <td><?= htmlspecialchars($user['Date_Blocked'] ?? ''); ?></td>
              <td><?= htmlspecialchars($user['Reason'] ?? ''); ?></td>
              <td><?= htmlspecialchars($user['Product_Name'] ?? ''); ?></td>
              <td><button class="btn btn-primary btn-unblock">Unblock</button></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$extraScripts = <<<JS
<script>
  const showAllBtn = document.getElementById('showAll');
  const showBlockedBtn = document.getElementById('showBlocked');
  const allTable = document.getElementById('allUsersTable');
  const blockedTable = document.getElementById('blockedUsersTable');
  const searchInput = document.getElementById('userSearch');
  const blockedFilter = document.getElementById('blockedFilter');

  function toggleView(showBlocked) {
    if (showBlocked) {
      allTable.style.display = 'none';
      blockedTable.style.display = '';
      blockedFilter.style.display = '';
      showBlockedBtn.classList.replace('btn-muted', 'btn-secondary');
      showAllBtn.classList.replace('btn-secondary', 'btn-muted');
    } else {
      allTable.style.display = '';
      blockedTable.style.display = 'none';
      blockedFilter.style.display = 'none';
      showAllBtn.classList.replace('btn-muted', 'btn-secondary');
      showBlockedBtn.classList.replace('btn-secondary', 'btn-muted');
    }
    searchUsers();
  }

  function searchUsers() {
    const query = searchInput.value.toLowerCase();
    if (blockedTable.style.display === 'none') {
      allTable.querySelectorAll('tbody tr').forEach(row => {
        const name = row.cells[0]?.textContent.toLowerCase() || '';
        const email = row.cells[1]?.textContent.toLowerCase() || '';
        row.style.display = (name.includes(query) || email.includes(query)) ? '' : 'none';
      });
    } else {
      const productFilter = blockedFilter.value;
      blockedTable.querySelectorAll('tbody tr').forEach(row => {
        const name = row.cells[0]?.textContent.toLowerCase() || '';
        const email = row.cells[1]?.textContent.toLowerCase() || '';
        const product = row.dataset.product || '';
        const matchesSearch = name.includes(query) || email.includes(query);
        const matchesProduct = productFilter === 'all' || product === productFilter;
        row.style.display = (matchesSearch && matchesProduct) ? '' : 'none';
      });
    }
  }

  searchInput.addEventListener('input', searchUsers);
  blockedFilter.addEventListener('change', searchUsers);
  showAllBtn.addEventListener('click', () => toggleView(false));
  showBlockedBtn.addEventListener('click', () => toggleView(true));

  toggleView(false);

  document.querySelectorAll('.btn-unblock').forEach(button => {
    button.addEventListener('click', async () => {
      const row = button.closest('tr');
      const id = row.dataset.blacklistId;
      const formData = new FormData();
      formData.append('action', 'unblock');
      formData.append('blacklist_id', id);
      try {
        const response = await fetch('../PHP/blacklist_api.php', { method: 'POST', body: formData });
        const result = await response.json();
        if (!result.success) throw new Error('Failed to unblock user');
        row.remove();
      } catch (error) {
        alert(error.message);
      }
    });
  });
</script>
JS;
include 'includes/footer.php';
