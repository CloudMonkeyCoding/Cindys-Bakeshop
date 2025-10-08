<?php
session_start();

require_once '../PHP/db_connect.php';
require_once '../PHP/shift_functions.php';

$activePage = 'shifts';
$pageTitle = "Shift Schedule - Cindy's Bakeshop";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$messages = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $errors[] = 'Invalid request token. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_shift') {
            $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
            $shiftDate = trim($_POST['shift_date'] ?? '');
            $scheduledStart = trim($_POST['scheduled_start'] ?? '');
            $scheduledEnd = trim($_POST['scheduled_end'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            if ($userId <= 0) {
                $errors[] = 'Select a staff member to assign the shift to.';
            }

            $dateValid = DateTime::createFromFormat('Y-m-d', $shiftDate) !== false;
            if (!$dateValid) {
                $errors[] = 'Provide a valid shift date.';
            }

            $startValid = DateTime::createFromFormat('H:i', $scheduledStart) !== false;
            $endValid = DateTime::createFromFormat('H:i', $scheduledEnd) !== false;
            if (!$startValid || !$endValid) {
                $errors[] = 'Enter valid start and end times (24-hour format).';
            }

            if ($startValid && $endValid && $scheduledStart >= $scheduledEnd) {
                $errors[] = 'Shift end time must be later than the start time.';
            }

            if (!$errors) {
                try {
                    createShiftSchedule($pdo, $userId, $shiftDate, $scheduledStart, $scheduledEnd, $notes ?: null);
                    $messages[] = 'Shift scheduled successfully.';
                } catch (PDOException $e) {
                    $errors[] = 'Failed to create shift schedule. Please try again.';
                }
            }
        } elseif ($action === 'start_shift') {
            $shiftId = isset($_POST['shift_id']) ? (int) $_POST['shift_id'] : 0;
            if ($shiftId <= 0) {
                $errors[] = 'Shift not found.';
            } else {
                if (startShift($pdo, $shiftId)) {
                    $messages[] = 'Shift started.';
                } else {
                    $errors[] = 'Unable to start the shift. It may already be in progress or completed.';
                }
            }
        } elseif ($action === 'end_shift') {
            $shiftId = isset($_POST['shift_id']) ? (int) $_POST['shift_id'] : 0;
            if ($shiftId <= 0) {
                $errors[] = 'Shift not found.';
            } else {
                if (endShift($pdo, $shiftId)) {
                    $messages[] = 'Shift completed.';
                } else {
                    $errors[] = 'Unable to end the shift. Start the shift before ending it.';
                }
            }
        }
    }
}

$staffMembers = [];
$shiftSchedules = [];

if ($pdo) {
    try {
        $pdo->exec('
            UPDATE shift_schedule
            SET Status = "missed"
            WHERE Shift_Date < CURRENT_DATE()
              AND Actual_Start IS NULL
              AND Status <> "missed"
        ');
    } catch (PDOException $e) {
        // Ignore if the table is not yet created.
    }

    $staffStmt = $pdo->query('
        SELECT ss.Store_Staff_ID, u.User_ID, u.Name
        FROM store_staff ss
        INNER JOIN user u ON ss.User_ID = u.User_ID
        ORDER BY u.Name ASC
    ');
    $staffMembers = $staffStmt ? $staffStmt->fetchAll(PDO::FETCH_ASSOC) : [];

    try {
        $shiftSchedules = getShiftSchedules($pdo);
    } catch (PDOException $e) {
        $errors[] = 'Unable to load shift schedules.';
    }
} else {
    $errors[] = 'Database connection unavailable. Shift management is disabled.';
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="page-header">
    <h1>Shift Schedule</h1>
    <a href="edit-profile.php" class="user-info">
      <span>Admin</span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <?php if ($messages): ?>
    <div class="alert alert-success" role="status">
      <ul>
        <?php foreach ($messages as $message): ?>
          <li><?= htmlspecialchars($message); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert alert-error" role="alert">
      <ul>
        <?php foreach ($errors as $error): ?>
          <li><?= htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card shift-form-card">
    <h2>Schedule a Shift</h2>
    <form method="post" class="shift-form">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
      <input type="hidden" name="action" value="create_shift">
      <div class="form-grid">
        <label>
          Staff Member
          <select name="user_id" required>
            <option value="">Select staff</option>
            <?php foreach ($staffMembers as $staff): ?>
              <option value="<?= (int) $staff['User_ID']; ?>">
                <?= htmlspecialchars($staff['Name'] ?? 'Staff #' . $staff['User_ID']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>
          Shift Date
          <input type="date" name="shift_date" required>
        </label>
        <label>
          Start Time
          <input type="time" name="scheduled_start" required>
        </label>
        <label>
          End Time
          <input type="time" name="scheduled_end" required>
        </label>
        <label class="full-width">
          Notes (optional)
          <input type="text" name="notes" placeholder="Additional details for this shift">
        </label>
      </div>
      <button type="submit" class="btn btn-primary">Add Shift</button>
    </form>
  </div>

  <div class="table-container">
    <div class="table-actions">
      <h2>Upcoming &amp; Recent Shifts</h2>
    </div>
    <table>
      <thead>
        <tr>
          <th>Staff</th>
          <th>Date</th>
          <th>Scheduled</th>
          <th>Actual</th>
          <th>Status</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($shiftSchedules)): ?>
          <tr>
            <td colspan="7" class="table-empty">No shifts scheduled yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($shiftSchedules as $shift): ?>
            <?php
              $statusLabel = ucfirst(str_replace('_', ' ', $shift['Status'] ?? 'scheduled'));
              $scheduledRange = htmlspecialchars(($shift['Scheduled_Start'] ?? '') . ' - ' . ($shift['Scheduled_End'] ?? ''));
              $actualStart = $shift['Actual_Start'] ? date('M d, Y g:i A', strtotime($shift['Actual_Start'])) : '—';
              $actualEnd = $shift['Actual_End'] ? date('M d, Y g:i A', strtotime($shift['Actual_End'])) : '—';
            ?>
            <tr>
              <td><?= htmlspecialchars($shift['Name'] ?? ''); ?></td>
              <td><?= htmlspecialchars(date('M d, Y', strtotime($shift['Shift_Date']))); ?></td>
              <td><?= $scheduledRange; ?></td>
              <td>
                <div class="actual-times">
                  <span>Start: <?= htmlspecialchars($actualStart); ?></span><br>
                  <span>End: <?= htmlspecialchars($actualEnd); ?></span>
                </div>
              </td>
              <td><span class="status-badge status-<?= htmlspecialchars($shift['Status']); ?>"><?= htmlspecialchars($statusLabel); ?></span></td>
              <td><?= htmlspecialchars($shift['Notes'] ?? '—'); ?></td>
              <td>
                <div class="shift-action-buttons">
                  <?php if (empty($shift['Actual_Start'])): ?>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                      <input type="hidden" name="action" value="start_shift">
                      <input type="hidden" name="shift_id" value="<?= (int) $shift['Shift_ID']; ?>">
                      <button type="submit" class="btn btn-secondary">Start Shift</button>
                    </form>
                  <?php elseif (!empty($shift['Actual_Start']) && empty($shift['Actual_End'])): ?>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                      <input type="hidden" name="action" value="end_shift">
                      <input type="hidden" name="shift_id" value="<?= (int) $shift['Shift_ID']; ?>">
                      <button type="submit" class="btn btn-primary">End Shift</button>
                    </form>
                  <?php else: ?>
                    <span class="muted">No actions available</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
