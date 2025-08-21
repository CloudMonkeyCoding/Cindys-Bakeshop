<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$activePage = 'orders';
require_once '../../PHP/db_connect.php';
require_once '../../PHP/order_cancellation_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        error_log('CSRF token mismatch in ManageCancel.php');
    } else {
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
        $cancelId = filter_input(INPUT_POST, 'cancel_id', FILTER_VALIDATE_INT);
        if ($action && $cancelId !== false) {
            $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
            updateOrderCancellationStatus($pdo, $cancelId, $newStatus);
            header('Location: ManageCancel.php');
            exit;
        }
    }
}

$cancellations = [];
if ($pdo) {
    $cancellations = getAllOrderCancellations($pdo);
} else {
    error_log('Database connection failed in ManageCancel.php');
}

$pageTitle = 'Manage Cancellations';
$headerTitle = 'Cancellations';
include '../header.php';
?>
<div class="flex h-screen overflow-hidden">
  <?php include $prefix . 'sidebar.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto">
      <?php include $prefix . 'topbar.php'; ?>

      <!-- Cancellations Section -->
      <div class="p-6">
        <h2 class="text-lg font-semibold mb-4">Manage Cancellations</h2>
        <div class="bg-white p-4 rounded shadow">
          <div class="flex items-center gap-4 mb-4">
            <input type="text" id="searchCancel" placeholder="Search Order ID" class="border rounded px-2 py-1 text-sm">
            <button class="bg-gray-300 px-4 py-1 rounded text-sm" onclick="filterCancellations('all')">All</button>
            <button class="bg-yellow-200 px-4 py-1 rounded text-sm" onclick="filterCancellations('pending')">Pending</button>
            <button class="bg-green-200 px-4 py-1 rounded text-sm" onclick="filterCancellations('approved')">Approved</button>
            <button class="bg-red-200 px-4 py-1 rounded text-sm" onclick="filterCancellations('rejected')">Rejected</button>
          </div>
          <table class="table w-full text-sm text-left">
            <thead class="border-b">
              <tr>
                <th>✓</th>
                <th>Cancel ID</th>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Reason</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="cancelTable">
            <?php foreach ($cancellations as $cancel):
              $status = strtolower($cancel['Status']);
              $statusClass = '';
              if ($status === 'pending') { $statusClass = 'text-yellow-500'; }
              elseif ($status === 'approved') { $statusClass = 'text-green-500'; }
              elseif ($status === 'rejected') { $statusClass = 'text-red-500'; }
            ?>
              <tr data-status="<?= $status ?>">
                <td><input type="checkbox"></td>
                <td><?= 'CN' . sprintf('%03d', $cancel['Cancellation_ID']); ?></td>
                <td><?= sprintf('%05d', $cancel['Order_ID']); ?></td>
                <td><?= htmlspecialchars($cancel['Customer'] ?? 'User ' . $cancel['User_ID']); ?></td>
                <td><?= htmlspecialchars($cancel['Reason']); ?></td>
                <td><?= htmlspecialchars($cancel['Cancellation_Date']); ?></td>
                <td class="<?= $statusClass ?> font-medium"><?= htmlspecialchars($cancel['Status']); ?></td>
                <td>
                <?php if ($status === 'pending'): ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="cancel_id" value="<?= $cancel['Cancellation_ID']; ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="text-green-600 text-xs">Approve</button>
                  </form>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="cancel_id" value="<?= $cancel['Cancellation_ID']; ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="text-red-600 text-xs ml-2">Reject</button>
                  </form>
                <?php else: ?>
                  <span class="text-gray-500 italic text-xs">Completed</span>
                <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <!-- Scripts -->
  <script>
    function filterCancellations(status) {
      const rows = document.querySelectorAll('#cancelTable tr');
      rows.forEach(row => {
        const rowStatus = row.dataset.status;
        if (status === 'all' || rowStatus === status) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
