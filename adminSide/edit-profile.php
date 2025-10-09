<?php
require_once __DIR__ . '/includes/require_admin_login.php';
require_once '../PHP/db_connect.php';

$activePage = 'settings';
$pageTitle = "Edit Profile - Cindy's Bakeshop";
$message = '';

if ($pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?: '';
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($name && $email) {
            $stmt = $pdo->prepare('UPDATE user SET Name = :name, Email = :email WHERE User_ID = :id');
            $success = $stmt->execute([':name' => $name, ':email' => $email, ':id' => $userId]);
            $message = $success ? 'Profile updated successfully.' : 'Failed to update profile.';
        } else {
            $message = 'Please provide a valid name and email.';
        }
    }
    $stmt = $pdo->query('SELECT User_ID, Name, Email FROM user ORDER BY User_ID ASC LIMIT 1');
    $profile = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
} else {
    $profile = null;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Edit Profile</h1>
    <a href="settings.php" class="btn btn-muted" style="text-decoration:none;">← Back to Settings</a>
  </div>

  <div class="card" style="max-width:480px;">
    <?php if ($message): ?>
      <p style="color:#1e8449;font-weight:600;"><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if ($profile): ?>
      <form method="post" style="display:grid;gap:16px;">
        <input type="hidden" name="user_id" value="<?= $profile['User_ID']; ?>">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input type="text" id="name" name="name" value="<?= htmlspecialchars($profile['Name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['Email'] ?? ''); ?>" required>
        </div>
        <div>
          <button type="submit" class="btn btn-primary">Save Profile</button>
        </div>
      </form>
    <?php else: ?>
      <p class="table-empty">No profile data available.</p>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
