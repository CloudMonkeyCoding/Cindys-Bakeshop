<?php
require_once __DIR__ . '/../PHP/db_connect.php';
require_once __DIR__ . '/../PHP/user_functions.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$userId = null;
$message = '';
$userSettings = [
    'language' => 'English',
    'theme' => 'Light',
    'notify_order' => 0,
    'notify_promotions' => 0,
    'notify_feedback' => 0,
];

if ($email && $pdo) {
    $user = getUserByEmail($pdo, $email);
    if ($user) {
        $userId = (int)$user['User_ID'];
        $userSettings['language'] = $user['Language'] ?? 'English';
        $userSettings['theme'] = $user['Theme'] ?? 'Light';
        $userSettings['notify_order'] = $user['Notify_Order_Status'] ?? 0;
        $userSettings['notify_promotions'] = $user['Notify_Promotions'] ?? 0;
        $userSettings['notify_feedback'] = $user['Notify_Feedback'] ?? 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId) {
    $userSettings['language'] = $_POST['language'] ?? 'English';
    $userSettings['theme'] = $_POST['theme'] ?? 'Light';
    $userSettings['notify_order'] = isset($_POST['notify_order']) ? 1 : 0;
    $userSettings['notify_promotions'] = isset($_POST['notify_promotions']) ? 1 : 0;
    $userSettings['notify_feedback'] = isset($_POST['notify_feedback']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare('UPDATE User SET Language = :language, Theme = :theme, Notify_Order_Status = :notify_order, Notify_Promotions = :notify_promotions, Notify_Feedback = :notify_feedback WHERE User_ID = :user_id');
        $stmt->execute([
            ':language' => $userSettings['language'],
            ':theme' => $userSettings['theme'],
            ':notify_order' => $userSettings['notify_order'],
            ':notify_promotions' => $userSettings['notify_promotions'],
            ':notify_feedback' => $userSettings['notify_feedback'],
            ':user_id' => $userId,
        ]);
        $message = 'Settings updated successfully.';
    } catch (Throwable $e) {
        $message = 'Failed to update settings.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Account Settings • Cindy’s Bakeshop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
    body { background:#f8f3ed; color:#3d2c1d; min-height:100vh; display:flex; flex-direction:column; }

    nav { background:#fff9f3; display:flex; justify-content:space-between; align-items:center; padding:1rem 2rem; box-shadow:0 1px 6px rgba(0,0,0,0.06); position:sticky; top:0; z-index:1000; }
    nav .logo { font-size:1.3rem; font-weight:600; color:#a66e44; display:flex; align-items:center; gap:.6rem; }
    nav .logo img { height:42px; }
    nav ul { list-style:none; display:flex; gap:1.2rem; align-items:center; }
    nav ul li a { color:#3d2c1d; text-decoration:none; font-weight:500; }
    nav ul li a:hover, nav ul li a.active { color:#a66e44; }
    nav .menu-toggle { display:none; font-size:1.8rem; cursor:pointer; }

    @media (max-width:768px) {
      nav { flex-wrap:wrap; }
      nav ul { display:none; flex-direction:column; gap:1rem; width:100%; margin-top:1rem; }
      nav ul.show { display:flex; }
      .menu-toggle { display:block; }
      .profile-dropdown .dropdown-menu { position:static; box-shadow:none; border-radius:6px; background:#fff9f3; margin-top:0.5rem; width:100%; }
      .profile-dropdown .dropdown-menu li { padding:0.7rem 0.5rem; border-top:1px solid #f0e5da; }
      .profile-dropdown .dropdown-menu li:first-child { border-top:none; }
    }

    .profile-dropdown { position:relative; }
    .profile-dropdown span { cursor:pointer; display:flex; align-items:center; gap:.25rem; }
    .profile-dropdown .dropdown-menu { position:absolute; top:120%; right:0; background:#fff; color:#3d2c1d; border-radius:8px; box-shadow:0 3px 10px rgba(0,0,0,0.08); display:none; flex-direction:column; min-width:190px; z-index:2000; }
    .profile-dropdown .dropdown-menu li { padding:0.7rem 1rem; }
    .profile-dropdown .dropdown-menu li a { text-decoration:none; color:#3d2c1d; display:block; }
    .profile-dropdown .dropdown-menu li:hover { background:#f9f6f2; }
    .profile-dropdown .dropdown-menu li[data-auth="required"] { display:none; }
    .profile-dropdown.show .dropdown-menu { display:flex; }

    main { flex:1; }
    .settings-wrapper { max-width:840px; margin:2.5rem auto; padding:0 1.5rem 3.5rem; }
    .settings-card { background:#fff; border-radius:20px; box-shadow:0 12px 28px rgba(0,0,0,0.08); padding:2.5rem; display:flex; flex-direction:column; gap:1.8rem; }
    .settings-card h1 { font-size:2rem; color:#5b3a1e; }
    .settings-card p.lead { color:#806552; }

    form .section { border-top:1px solid #f0e5da; padding-top:1.5rem; display:grid; gap:1.1rem; }
    form .section:first-of-type { border-top:none; padding-top:0; }
    .section h2 { font-size:1.1rem; color:#a66e44; }
    label { font-weight:600; color:#5b3a1e; }
    select { width:100%; padding:0.75rem; border-radius:12px; border:1px solid #d6c5b6; background:#fffaf4; font-size:1rem; }
    select:focus { border-color:#c25d21; outline:none; box-shadow:0 0 0 3px rgba(194,93,33,0.15); }
    .checkbox-group label { display:flex; align-items:center; gap:0.6rem; font-weight:500; color:#6f5844; }
    .checkbox-group input { width:18px; height:18px; }

    .actions { display:flex; gap:1rem; flex-wrap:wrap; }
    .actions button { flex:1 1 160px; padding:0.85rem 1.2rem; border-radius:12px; border:none; font-weight:700; cursor:pointer; font-size:1rem; transition:transform 0.2s, box-shadow 0.2s; }
    .actions .primary { background:#c25d21; color:#fff; box-shadow:0 10px 20px rgba(194,93,33,0.22); }
    .actions .primary:hover { background:#a74d16; transform:translateY(-2px); }
    .actions .secondary { background:#f3e2da; color:#8b4513; }
    .actions .secondary:hover { background:#e5cdbd; }

    .alert { padding:0.85rem 1rem; border-radius:12px; font-weight:600; }
    .alert.success { background:#e6f4ea; color:#2e7d32; }
    .alert.error { background:#fdecea; color:#c0392b; }
  </style>
</head>
<body>
  <nav>
    <div class="logo"><img src="../Kehnt_admin_Design/Cindys.png" alt="Cindy’s Logo"/> Cindy’s Bakeshop</div>
    <div class="menu-toggle" id="menuToggle">☰</div>
    <ul id="navMenu">
      <li><a href="home.php">Home</a></li>
      <li><a href="menu.php">Menu</a></li>
      <li><a href="checkout.php">Cart 🛒</a></li>
      <li><a href="orders.php">Orders</a></li>
      <li class="profile-dropdown">
        <span id="profileToggle">Account ▾</span>
        <ul class="dropdown-menu" id="profileMenu">
          <li data-auth="required"><a href="profile.php">View Profile</a></li>
          <li data-auth="required"><a href="favorites.php">Favorites</a></li>
          <li data-auth="required"><a href="orders.php">Order History</a></li>
          <li data-auth="required"><a href="settings.php" class="active">Account Settings</a></li>
          <li data-auth="required"><a href="logout.php">Logout</a></li>
          <li data-auth="guest"><a href="login.php">Login</a></li>
          <li data-auth="guest"><a href="signup.php">Sign Up</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <main>
    <div class="settings-wrapper">
      <div class="settings-card">
        <div>
          <h1>Account Preferences</h1>
          <p class="lead">Fine-tune how Cindy’s Bakeshop connects with you. Update language, theme, and notification choices anytime.</p>
        </div>

        <?php if (!empty($message)): ?>
          <div class="alert <?= stripos($message, 'success') !== false ? 'success' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <input type="hidden" name="email" id="emailField" value="<?= htmlspecialchars($email) ?>">

          <div class="section">
            <h2>Display</h2>
            <div>
              <label for="language">Language</label>
              <select id="language" name="language">
                <option value="English" <?= $userSettings['language'] === 'English' ? 'selected' : '' ?>>English</option>
                <option value="Tagalog" <?= $userSettings['language'] === 'Tagalog' ? 'selected' : '' ?>>Tagalog</option>
              </select>
            </div>
            <div>
              <label for="theme">Theme</label>
              <select id="theme" name="theme">
                <option value="Light" <?= $userSettings['theme'] === 'Light' ? 'selected' : '' ?>>Light</option>
                <option value="Dark" <?= $userSettings['theme'] === 'Dark' ? 'selected' : '' ?>>Dark</option>
              </select>
            </div>
          </div>

          <div class="section">
            <h2>Notifications</h2>
            <div class="checkbox-group">
              <label><input type="checkbox" name="notify_order" <?= $userSettings['notify_order'] ? 'checked' : '' ?>> Order status updates</label>
              <label><input type="checkbox" name="notify_promotions" <?= $userSettings['notify_promotions'] ? 'checked' : '' ?>> Promotions & seasonal treats</label>
              <label><input type="checkbox" name="notify_feedback" <?= $userSettings['notify_feedback'] ? 'checked' : '' ?>> Friendly reminders for feedback</label>
            </div>
          </div>

          <div class="actions">
            <button type="submit" class="primary">Save Settings</button>
            <button type="button" class="secondary" id="backBtn">Back to Profile</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    menuToggle?.addEventListener('click', () => navMenu.classList.toggle('show'));

    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    profileToggle?.addEventListener('click', () => profileMenu?.parentElement?.classList.toggle('show'));
    window.addEventListener('click', (event) => {
      if (!event.target.closest('.profile-dropdown')) {
        profileMenu?.parentElement?.classList.remove('show');
      }
    });

    const auth = getAuth();
    let userEmail = null;
    const emailField = document.getElementById('emailField');
    const backBtn = document.getElementById('backBtn');

    backBtn?.addEventListener('click', () => {
      window.location.href = 'profile.php';
    });

    function updateProfileMenu() {
      if (!profileToggle || !profileMenu) return;
      const authedItems = profileMenu.querySelectorAll('[data-auth="required"]');
      const guestItems = profileMenu.querySelectorAll('[data-auth="guest"]');
      if (userEmail) {
        profileToggle.textContent = `${userEmail} ▾`;
        authedItems.forEach((item) => { item.style.display = 'block'; });
        guestItems.forEach((item) => { item.style.display = 'none'; });
      } else {
        profileToggle.textContent = 'Login ▾';
        authedItems.forEach((item) => { item.style.display = 'none'; });
        guestItems.forEach((item) => { item.style.display = 'block'; });
        profileMenu.parentElement?.classList.remove('show');
      }
    }

    onAuthStateChanged(auth, (user) => {
      if (!user) {
        window.location.href = 'login.php';
        return;
      }
      userEmail = user.email;
      updateProfileMenu();
      if (emailField) {
        emailField.value = userEmail;
      }
      const params = new URLSearchParams(window.location.search);
      if (params.get('email') !== userEmail) {
        params.set('email', userEmail);
        window.location.search = params.toString();
      }
    });

    updateProfileMenu();
  </script>
</body>
</html>
