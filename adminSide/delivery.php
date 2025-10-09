<?php
require_once __DIR__ . '/includes/require_admin_login.php';
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../PHP/db_connect.php';
require_once '../PHP/delivery_functions.php';

$activePage = 'delivery';
$pageTitle = "Delivery Management - Cindy's Bakeshop";

$deliveries = [];
if ($pdo) {
    $sql = "SELECT d.*, o.Status AS Order_Status, o.Order_Date, u.Name AS Customer
            FROM delivery d
            LEFT JOIN `order` o ON d.Order_ID = o.Order_ID
            LEFT JOIN user u ON o.User_ID = u.User_ID
            ORDER BY d.Delivery_Date DESC, d.Delivery_ID DESC";
    $stmt = $pdo->query($sql);
    $deliveries = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Delivery Tracking</h1>
    <a href="profile.php" class="user-info">
      <span><?= htmlspecialchars($adminSession['name']); ?></span>
      <img src="<?= htmlspecialchars($adminSession['avatar_url']); ?>" alt="<?= htmlspecialchars($adminSession['name']); ?> avatar">
    </a>
  </div>

  <div class="table-container">
    <div class="table-actions">
      <input type="text" id="searchDelivery" placeholder="🔍 Search delivery...">
      <select id="deliveryStatusFilter">
        <option value="all">All Status</option>
        <option value="Pending">Pending</option>
        <option value="Out for Delivery">Out for Delivery</option>
        <option value="Delivered">Delivered</option>
      </select>
    </div>
    <table id="deliveryTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Status</th>
          <th>Delivery Date</th>
          <th>Personnel</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($deliveries)): ?>
          <tr>
            <td colspan="7" class="table-empty">No deliveries recorded.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($deliveries as $delivery): ?>
            <tr data-delivery-id="<?= $delivery['Delivery_ID']; ?>" data-status="<?= htmlspecialchars($delivery['Status']); ?>">
              <td>DL<?= str_pad($delivery['Delivery_ID'], 4, '0', STR_PAD_LEFT); ?></td>
              <td>#<?= str_pad($delivery['Order_ID'], 5, '0', STR_PAD_LEFT); ?></td>
              <td><?= htmlspecialchars($delivery['Customer'] ?? 'Customer ' . $delivery['Order_ID']); ?></td>
              <td>
                <span class="status-pill status-<?= strtolower(str_replace(' ', '-', $delivery['Status'])); ?>">
                  <?= htmlspecialchars($delivery['Status']); ?>
                </span>
              </td>
              <td><?= htmlspecialchars($delivery['Delivery_Date']); ?></td>
              <td><?= htmlspecialchars($delivery['Delivery_Personnel'] ?? 'Unassigned'); ?></td>
              <td style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="btn btn-secondary btn-edit" data-delivery="<?= $delivery['Delivery_ID']; ?>">Update</button>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal" id="deliveryModal">
  <div class="modal-content">
    <h2>Update Delivery</h2>
    <form id="deliveryForm">
      <input type="hidden" name="delivery_id" id="deliveryIdField">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
      <div class="form-group">
        <label for="deliveryStatus">Status</label>
        <select name="status" id="deliveryStatus">
          <option value="Pending">Pending</option>
          <option value="Out for Delivery">Out for Delivery</option>
          <option value="Delivered">Delivered</option>
        </select>
      </div>
      <div class="form-group">
        <label for="deliveryDate">Delivery Date</label>
        <input type="date" name="delivery_date" id="deliveryDate">
      </div>
      <div class="form-group">
        <label for="deliveryPersonnel">Personnel</label>
        <input type="text" name="delivery_personnel" id="deliveryPersonnel" placeholder="Assign delivery personnel">
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <button type="button" class="btn btn-muted" id="closeDeliveryModal">Close</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<?php
$csrfToken = json_encode($_SESSION['csrf_token']);
$extraScripts = <<<JS
<script>
  const csrfToken = $csrfToken;
  const searchDelivery = document.getElementById('searchDelivery');
  const statusFilter = document.getElementById('deliveryStatusFilter');
  const modal = document.getElementById('deliveryModal');
  const closeModal = document.getElementById('closeDeliveryModal');
  const deliveryForm = document.getElementById('deliveryForm');
  const deliveryIdField = document.getElementById('deliveryIdField');
  const deliveryStatusField = document.getElementById('deliveryStatus');
  const deliveryDateField = document.getElementById('deliveryDate');
  const deliveryPersonnelField = document.getElementById('deliveryPersonnel');

  document.querySelectorAll('.btn-edit').forEach(button => {
    button.addEventListener('click', () => {
      const row = button.closest('tr');
      const deliveryId = button.dataset.delivery;
      deliveryIdField.value = deliveryId;
      deliveryStatusField.value = row.dataset.status || 'Pending';
      deliveryDateField.value = row.children[4].textContent.trim();
      deliveryPersonnelField.value = row.children[5].textContent.trim() === 'Unassigned' ? '' : row.children[5].textContent.trim();
      modal.classList.add('active');
    });
  });

  closeModal.addEventListener('click', () => modal.classList.remove('active'));
  modal.addEventListener('click', event => {
    if (event.target === modal) modal.classList.remove('active');
  });

  async function updateDelivery(data) {
    const response = await fetch('../PHP/delivery_actions.php', {
      method: 'POST',
      headers: { 'Accept': 'application/json' },
      body: data
    });
    const result = await response.json();
    if (!response.ok || !result.success) {
      throw new Error(result.message || 'Unable to update delivery');
    }
    return result.delivery;
  }

  deliveryForm.addEventListener('submit', async event => {
    event.preventDefault();
    const formData = new FormData(deliveryForm);
    formData.append('action', 'update');
    formData.append('csrf_token', csrfToken);
    try {
      const updated = await updateDelivery(new URLSearchParams(formData));
      const row = document.querySelector(`tr[data-delivery-id="\${updated.Delivery_ID}"]`);
      if (row) {
        row.dataset.status = updated.Status;
        row.children[3].querySelector('.status-pill').textContent = updated.Status;
        row.children[3].querySelector('.status-pill').className = 'status-pill status-' + updated.Status.toLowerCase().replace(/\s+/g, '-');
        row.children[4].textContent = updated.Delivery_Date || '';
        row.children[5].textContent = updated.Delivery_Personnel || 'Unassigned';
      }
      modal.classList.remove('active');
    } catch (error) {
      alert(error.message);
    }
  });

  function applyFilters() {
    const query = searchDelivery.value.toLowerCase();
    const status = statusFilter.value;
    document.querySelectorAll('#deliveryTable tbody tr').forEach(row => {
      const cells = row.querySelectorAll('td');
      if (!cells.length) return;
      const deliveryId = cells[0].textContent.toLowerCase();
      const orderId = cells[1].textContent.toLowerCase();
      const customer = cells[2].textContent.toLowerCase();
      const rowStatus = row.dataset.status;
      const matchesSearch = deliveryId.includes(query) || orderId.includes(query) || customer.includes(query);
      const matchesStatus = status === 'all' || rowStatus === status;
      row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
  }

  searchDelivery.addEventListener('input', applyFilters);
  statusFilter.addEventListener('change', applyFilters);
  applyFilters();
</script>
JS;
include 'includes/footer.php';
