<?php
require_once '../../PHP/db_connect.php';
require_once '../../PHP/user_functions.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$userId = null;
$message = '';

if ($email) {
    $user = getUserByEmail($pdo, $email);
    if ($user) {
        $userId = $user['User_ID'];
    }
}

// Default settings
$userSettings = [
    'language' => 'English',
    'theme' => 'Light',
    'notify_order' => 0,
    'notify_promotions' => 0,
    'notify_feedback' => 0,
];

if ($userId) {
    $userSettings['language'] = $user['Language'] ?? 'English';
    $userSettings['theme'] = $user['Theme'] ?? 'Light';
    $userSettings['notify_order'] = $user['Notify_Order_Status'] ?? 0;
    $userSettings['notify_promotions'] = $user['Notify_Promotions'] ?? 0;
    $userSettings['notify_feedback'] = $user['Notify_Feedback'] ?? 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $userSettings['language'] = $_POST['language'] ?? 'English';
    $userSettings['theme'] = $_POST['theme'] ?? 'Light';
    $userSettings['notify_order'] = isset($_POST['notify_order']) ? 1 : 0;
    $userSettings['notify_promotions'] = isset($_POST['notify_promotions']) ? 1 : 0;
    $userSettings['notify_feedback'] = isset($_POST['notify_feedback']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE User SET Language = :language, Theme = :theme, Notify_Order_Status = :notify_order, Notify_Promotions = :notify_promotions, Notify_Feedback = :notify_feedback WHERE User_ID = :user_id");
        $stmt->execute([
            ':language' => $userSettings['language'],
            ':theme' => $userSettings['theme'],
            ':notify_order' => $userSettings['notify_order'],
            ':notify_promotions' => $userSettings['notify_promotions'],
            ':notify_feedback' => $userSettings['notify_feedback'],
            ':user_id' => $userId
        ]);

        if (!empty($_POST['newPassword']) && ($_POST['newPassword'] === ($_POST['confirmNewPassword'] ?? ''))) {
            updateUserPasswordById($pdo, $userId, $_POST['newPassword']);
        }
        $message = 'Settings updated successfully.';
    } catch (Exception $e) {
        $message = 'Failed to update settings.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Settings - Cindy's Bakeshop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../styles.css" />
</head>
<body class="settings-page">
  <?php include __DIR__ . '/../topbar.php'; ?>


  <div class="container">
    <h2>User Settings</h2>
    <?php if (!empty($message)): ?>
      <p class="message"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
    <div class="settings-section">
      <h3>Account Preferences</h3>
      <label for="language">Language</label>
      <select id="language" name="language">
        <option value="English" <?php echo $userSettings['language'] === 'English' ? 'selected' : ''; ?>>English</option>
        <option value="Tagalog" <?php echo $userSettings['language'] === 'Tagalog' ? 'selected' : ''; ?>>Tagalog</option>
      </select>

      <label for="theme">Theme</label>
      <select id="theme" name="theme">
        <option value="Light" <?php echo $userSettings['theme'] === 'Light' ? 'selected' : ''; ?>>Light</option>
        <option value="Dark" <?php echo $userSettings['theme'] === 'Dark' ? 'selected' : ''; ?>>Dark</option>
      </select>
    </div>

    <div class="settings-section">
      <h3>Notification Settings</h3>
      <label><input type="checkbox" name="notify_order" <?php echo $userSettings['notify_order'] ? 'checked' : ''; ?>> Order Status Updates</label>
      <label><input type="checkbox" name="notify_promotions" <?php echo $userSettings['notify_promotions'] ? 'checked' : ''; ?>> Promotions & Discounts</label>
      <label><input type="checkbox" name="notify_feedback" <?php echo $userSettings['notify_feedback'] ? 'checked' : ''; ?>> Feedback Reminders</label>
    </div>

    <div class="settings-section">
      <h3>Security</h3>
      <label for="currentPassword">Current Password</label>
      <input type="password" id="currentPassword" name="currentPassword">

      <label for="newPassword">New Password</label>
      <input type="password" id="newPassword" name="newPassword">

      <label for="confirmNewPassword">Confirm New Password</label>
      <input type="password" id="confirmNewPassword" name="confirmNewPassword">
    </div>

    <button class="save-btn" type="submit">Save Settings</button>
    </form>
  </div>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";

    const auth = getAuth();
    onAuthStateChanged(auth, user => {
      if (user) {
        const emailInput = document.querySelector('input[name="email"]');
        if (emailInput) {
          emailInput.value = user.email;
        }

        const params = new URLSearchParams(window.location.search);
        if (!params.get('email')) {
          params.set('email', user.email);
          window.location.search = params.toString();
        }
      }
    });
  </script>
  <script>
  </script>

</body>
</html>
