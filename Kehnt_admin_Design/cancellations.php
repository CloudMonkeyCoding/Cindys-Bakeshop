<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../PHP/db_connect.php';
require_once '../PHP/order_cancellation_functions.php';

$activePage = 'cancellations';
$pageTitle = "Order Cancellations - Cindy's Bakeshop";

$cancellations = [];
if ($pdo) {
    $cancellations = getAllOrderCancellations($pdo);
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Order Cancellations</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <div class="table-container">
    <div class="table-actions">
      <input type="text" id="searchCancel" placeholder="🔍 Search cancellation...">
      <select id="filterCancelStatus">
        <option value="all">All Status</option>
        <option value="Pending">Pending</option>
        <option value="Approved">Approved</option>
        <option value="Rejected">Rejected</option>
      </select>
    </div>
    <table id="cancelTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Reason</th>
          <th>Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($cancellations)): ?>
          <tr>
            <td colspan="7" class="table-empty">No cancellation requests.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($cancellations as $cancel): ?>
            <tr data-cancel-id="<?= $cancel['Cancellation_ID']; ?>" data-status="<?= htmlspecialchars($cancel['Status']); ?>">
              <td>CN<?= str_pad($cancel['Cancellation_ID'], 4, '0', STR_PAD_LEFT); ?></td>
              <td>#<?= str_pad($cancel['Order_ID'], 5, '0', STR_PAD_LEFT); ?></td>
              <td><?= htmlspecialchars($cancel['Customer'] ?? 'Customer ' . $cancel['User_ID']); ?></td>
              <td><?= htmlspecialchars($cancel['Reason']); ?></td>
              <td><?= htmlspecialchars($cancel['Cancellation_Date']); ?></td>
              <td>
                <span class="status-pill status-<?= strtolower($cancel['Status']); ?>">
                  <?= htmlspecialchars($cancel['Status']); ?>
                </span>
              </td>
              <td style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php if (strtolower($cancel['Status']) === 'pending'): ?>
                  <button class="btn btn-primary btn-approve" data-id="<?= $cancel['Cancellation_ID']; ?>">Approve</button>
                  <button class="btn btn-muted btn-reject" data-id="<?= $cancel['Cancellation_ID']; ?>">Reject</button>
                <?php else: ?>
                  <span class="badge badge-success">Resolved</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
$csrfToken = json_encode($_SESSION['csrf_token']);
$extraScripts = <<<JS
<script>
  const csrfToken = $csrfToken;
  const searchCancel = document.getElementById('searchCancel');
  const filterStatus = document.getElementById('filterCancelStatus');

  function applyCancellationFilters() {
    const query = searchCancel.value.toLowerCase();
    const status = filterStatus.value;
    document.querySelectorAll('#cancelTable tbody tr').forEach(row => {
      if (row.classList.contains('hidden-row')) return;
      const cells = row.querySelectorAll('td');
      if (!cells.length) return;
      const id = cells[0].textContent.toLowerCase();
      const orderId = cells[1].textContent.toLowerCase();
      const customer = cells[2].textContent.toLowerCase();
      const rowStatus = row.dataset.status;
      const matchesSearch = id.includes(query) || orderId.includes(query) || customer.includes(query);
      const matchesStatus = status === 'all' || rowStatus === status;
      row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
  }

  searchCancel.addEventListener('input', applyCancellationFilters);
  filterStatus.addEventListener('change', applyCancellationFilters);
  applyCancellationFilters();

  async function updateCancellation(id, action) {
    try {
      const response = await fetch('api/order_actions.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: new URLSearchParams({
          action,
          cancel_id: id,
          csrf_token: csrfToken
        })
      });
      const result = await response.json();
      if (!response.ok || !result.success) {
        throw new Error(result.message || 'Unable to update cancellation');
      }
      const row = document.querySelector(`tr[data-cancel-id="\${id}"]`);
      if (!row) return;
      row.dataset.status = result.status;
      const pill = row.querySelector('.status-pill');
      pill.textContent = result.status;
      pill.className = 'status-pill status-' + result.status.toLowerCase();
      const actionsCell = row.querySelector('td:last-child');
      actionsCell.innerHTML = '<span class="badge badge-success">Resolved</span>';
    } catch (error) {
      alert(error.message);
    }
  }

  document.querySelectorAll('.btn-approve').forEach(button => {
    button.addEventListener('click', () => updateCancellation(button.dataset.id, 'approve_cancellation'));
  });

  document.querySelectorAll('.btn-reject').forEach(button => {
    button.addEventListener('click', () => updateCancellation(button.dataset.id, 'reject_cancellation'));
  });
</script>
JS;
include 'includes/footer.php';
