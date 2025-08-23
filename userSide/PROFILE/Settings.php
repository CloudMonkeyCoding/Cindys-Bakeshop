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
  <style>
    * {
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }

    body {
      margin: 0;
      background: url('../Images/kn.jpg') no-repeat center center fixed;
      background-size: cover;
      backdrop-filter: blur(6px);
    }

     header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: red;
      padding: 16px 40px;
      border-bottom: 2px solid red;
    }
    .logo img {
      height: 120px;
    }
    .nav {
      display: flex;
      align-items: center;
      gap: 18px;
    }
 .nav a,
.dropdown button {
  padding: 15px 34px;
  border-radius: 30px;
  background: transparent; /* ✅ transparent background */
  text-decoration: none;
  color: rgb(255, 255, 255);
  font-weight: bold;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: none;
  cursor: pointer;
  transition: 0.3s;
}


.nav a i,
.dropdown button i {
  font-size: 18px;
}

    .nav a:hover,
    .dropdown button:hover {
      background: black;
      color: white;
    }

    .dropdown {
      position: relative;
    }
    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 45px;
      background-color: yellow;
      min-width: 160px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      z-index: 1;
    }
    .dropdown-content a {
      color: black;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      font-weight: bold;
    }


   .back-btn {
      display: inline-block;
      background-color: red;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      margin-bottom: 20px;
    }

    .back-btn:hover {
      background-color: black;
    }

    .container {
      max-width: 850px;
      margin: 40px auto;
      background: rgba(255, 255, 255, 0.95);
      padding: 35px 40px;
      border-radius: 20px;
      border: 2px solid red;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    h2 {
      text-align: center;
      color: #333;
      margin-bottom: 35px;
    }

    .settings-section {
      margin-bottom: 35px;
    }

    .settings-section h3 {
      margin-bottom: 15px;
      color: #555;
    }

    .settings-section label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
    }

    .settings-section input,
    .settings-section select {
      width: 100%;
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 10px;
      margin-bottom: 15px;
      font-size: 14px;
    }

    .settings-section input[type="checkbox"] {
      width: auto;
      margin-right: 8px;
    }

    button.save-btn {
      background: yellow;
      border: none;
      padding: 12px 24px;
      font-weight: bold;
      font-size: 16px;
      border-radius: 10px;
      cursor: pointer;
      display: block;
      margin: 0 auto;
      transition: background 0.3s, color 0.3s;
    }

    button.save-btn:hover {
      background: black;
      color: white;
    }

    .message {
      text-align: center;
      font-weight: bold;
      margin-bottom: 20px;
    }

    @media screen and (max-width: 600px) {
      .container {
        padding: 20px;
      }

      h2 {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>

  <header>
    <div class="logo">
      <img src="../Images/cindy's logo.png" alt="Cindy's Logo">
    </div>
    <div class="nav">
     <a href="../HOME PAGING/MENU.html"><i class="fas fa-bread-slice"></i> Menu</a>
      <a href="../CART/cart_checkout_page.html"><i class="fas fa-shopping-cart"></i> Cart</a>
      <div class="dropdown">
        <button onclick="toggleDropdown()"><i class="fas fa-user"></i> Profile</button>
        <div class="dropdown-content" id="profileDropdown">
          <a href="../PROFILE/EditProfile.html">Edit Profile</a>
          <a href="../PURCHASES/MyPurchase.html">My Purchases</a>
          <a href="../PROFILE/Settings.php">Settings</a>
        </div>
      </div>
      <a href="../HOME PAGING/HOME.HTML"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>


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
    function toggleDropdown() {
      const dropdown = document.getElementById("profileDropdown");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    window.onclick = function(e) {
      if (!e.target.matches("button")) {
        const dropdown = document.getElementById("profileDropdown");
        if (dropdown && dropdown.style.display === "block") {
          dropdown.style.display = "none";
        }
      }
    };
  </script>

</body>
</html>
