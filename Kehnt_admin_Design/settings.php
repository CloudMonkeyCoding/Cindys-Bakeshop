<?php
session_start();
require_once '../PHP/db_connect.php';
require_once '../PHP/user_functions.php';

$activePage = 'settings';
$pageTitle = "Settings - Cindy's Bakeshop";
$message = '';

if ($pdo) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $language = filter_input(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'English';
        $theme = filter_input(INPUT_POST, 'theme', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'Light';
        $notifyOrder = isset($_POST['notify_order']) ? 1 : 0;
        $notifyPromo = isset($_POST['notify_promo']) ? 1 : 0;
        $notifyFeedback = isset($_POST['notify_feedback']) ? 1 : 0;
        $userId = (int)($_POST['user_id'] ?? 0);

        $stmt = $pdo->prepare("UPDATE user SET Language = :language, Theme = :theme, Notify_Order_Status = :order_status, Notify_Promotions = :promo, Notify_Feedback = :feedback WHERE User_ID = :id");
        $success = $stmt->execute([
            ':language' => $language,
            ':theme' => $theme,
            ':order_status' => $notifyOrder,
            ':promo' => $notifyPromo,
            ':feedback' => $notifyFeedback,
            ':id' => $userId
        ]);
        $message = $success ? 'Settings updated successfully.' : 'Failed to update settings.';
    }

    $stmt = $pdo->query("SELECT User_ID, Name, Email, Language, Theme, Notify_Order_Status, Notify_Promotions, Notify_Feedback FROM user ORDER BY User_ID ASC LIMIT 1");
    $userSettings = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
} else {
    $userSettings = null;
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main">
  <div class="header">
    <h1>Account Settings</h1>
    <a href="edit-profile.php" class="user-info">
      <span><?= htmlspecialchars($userSettings['Name'] ?? 'Admin'); ?></span>
      <img src="https://i.pravatar.cc/80" alt="Admin avatar">
    </a>
  </div>

  <div class="card">
    <?php if ($message): ?>
      <p style="color:#1e8449;font-weight:600;"><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <?php if ($userSettings): ?>
      <form method="post" class="form-grid" style="display:grid;gap:16px;max-width:480px;">
        <input type="hidden" name="user_id" value="<?= $userSettings['User_ID']; ?>">
        <div class="form-group">
          <label>Name</label>
          <input type="text" value="<?= htmlspecialchars($userSettings['Name'] ?? ''); ?>" disabled>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" value="<?= htmlspecialchars($userSettings['Email'] ?? ''); ?>" disabled>
        </div>
        <div class="form-group">
          <label for="language">Language</label>
          <select name="language" id="language">
            <option value="English" <?= ($userSettings['Language'] ?? '') === 'English' ? 'selected' : ''; ?>>English</option>
            <option value="Filipino" <?= ($userSettings['Language'] ?? '') === 'Filipino' ? 'selected' : ''; ?>>Filipino</option>
          </select>
        </div>
        <div class="form-group">
          <label for="theme">Theme</label>
          <select name="theme" id="theme">
            <option value="Light" <?= ($userSettings['Theme'] ?? '') === 'Light' ? 'selected' : ''; ?>>Light</option>
            <option value="Dark" <?= ($userSettings['Theme'] ?? '') === 'Dark' ? 'selected' : ''; ?>>Dark</option>
          </select>
        </div>
        <div class="form-group" style="display:flex;flex-direction:column;gap:8px;">
          <label>Notifications</label>
          <label><input type="checkbox" name="notify_order" <?= !empty($userSettings['Notify_Order_Status']) ? 'checked' : ''; ?>> Order status updates</label>
          <label><input type="checkbox" name="notify_promo" <?= !empty($userSettings['Notify_Promotions']) ? 'checked' : ''; ?>> Promotions and discounts</label>
          <label><input type="checkbox" name="notify_feedback" <?= !empty($userSettings['Notify_Feedback']) ? 'checked' : ''; ?>> Customer feedback alerts</label>
        </div>
        <div>
          <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
      </form>
    <?php else: ?>
      <p class="table-empty">No account record found to configure settings.</p>
    <?php endif; ?>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
