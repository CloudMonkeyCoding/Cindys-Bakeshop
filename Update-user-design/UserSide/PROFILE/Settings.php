<?php
require_once __DIR__ . '/../../../PHP/db_connect.php';
require_once __DIR__ . '/../../../PHP/user_functions.php';

$email = $_GET['email'] ?? $_POST['email'] ?? '';
$userId = null;
$message = '';

if ($email) {
    $user = getUserByEmail($pdo, $email);
    if ($user) {
        $userId = $user['User_ID'];
    }
}

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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css" />
  <style>
    body.settings-view {
      display: flex;
      flex-direction: column;
    }

    .settings-hero {
      background: linear-gradient(135deg, rgba(139, 69, 19, 0.92), rgba(255, 204, 128, 0.9));
      border-radius: 32px;
      padding: clamp(2.5rem, 5vw, 4rem);
      color: #fff;
      margin-bottom: 3rem;
      box-shadow: 0 30px 60px rgba(139, 69, 19, 0.25);
    }

    .settings-hero h1 {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 700;
      margin-bottom: 0.8rem;
    }

    .settings-hero p {
      font-size: 1.05rem;
      opacity: 0.85;
      max-width: 520px;
    }

    .settings-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 2rem;
    }

    .settings-card {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 28px;
      padding: clamp(2rem, 3vw, 2.6rem);
      box-shadow: var(--shadow-soft);
      border: 1px solid rgba(139, 69, 19, 0.12);
      display: flex;
      flex-direction: column;
      gap: 2rem;
    }

    .settings-card h2 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--primary-brown);
    }

    .settings-section {
      display: grid;
      gap: 1.2rem;
    }

    .settings-section label {
      font-weight: 600;
      color: var(--primary-brown);
    }

    .settings-section select {
      width: 100%;
      border-radius: 16px;
      border: 1px solid rgba(139, 69, 19, 0.12);
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      background: rgba(255, 255, 255, 0.9);
      font-family: inherit;
    }

    .toggle {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1rem 1.2rem;
      border-radius: 20px;
      background: rgba(139, 69, 19, 0.08);
    }

    .toggle span {
      font-weight: 600;
    }

    .switch {
      position: relative;
      display: inline-block;
      width: 52px;
      height: 28px;
    }

    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .slider {
      position: absolute;
      cursor: pointer;
      inset: 0;
      background: rgba(139, 69, 19, 0.25);
      border-radius: 999px;
      transition: var(--transition);
    }

    .slider::before {
      position: absolute;
      content: '';
      height: 22px;
      width: 22px;
      left: 4px;
      top: 3px;
      background: #fff;
      border-radius: 50%;
      box-shadow: 0 6px 12px rgba(139, 69, 19, 0.25);
      transition: var(--transition);
    }

    .switch input:checked + .slider {
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
    }

    .switch input:checked + .slider::before {
      transform: translateX(24px);
    }

    .message {
      padding: 0.85rem 1.2rem;
      border-radius: 16px;
      font-weight: 600;
      background: rgba(45, 134, 89, 0.12);
      color: #2d8659;
    }

    .message.error {
      background: rgba(200, 40, 60, 0.12);
      color: #c8283c;
    }

    .save-row {
      display: flex;
      justify-content: flex-end;
    }

    .save-row button {
      padding: 0.85rem 1.8rem;
      border-radius: var(--radius-pill);
      border: none;
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
      font-weight: 600;
      box-shadow: 0 16px 32px rgba(139, 69, 19, 0.22);
      cursor: pointer;
    }

    @media (max-width: 680px) {
      .settings-hero {
        padding: 2.4rem 1.8rem;
      }

      .save-row {
        justify-content: stretch;
      }

      .save-row button {
        width: 100%;
      }
    }
  </style>
</head>
<body class="settings-view">
  <?php include __DIR__ . '/../topbar.php'; ?>

  <main class="page-container">
    <section class="settings-hero">
      <h1>Fine-tune your Cindy's experience</h1>
      <p>Choose how you want to hear from us and personalise the look and feel of your bakery journey.</p>
    </section>

    <div class="settings-grid">
      <form method="POST" action="" class="settings-card">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <div>
          <h2>Experience preferences</h2>
          <div class="settings-section">
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
        </div>

        <div>
          <h2>Notifications</h2>
          <div class="settings-section">
            <label class="toggle">
              <span>Order status updates</span>
              <span class="switch">
                <input type="checkbox" name="notify_order" <?php echo $userSettings['notify_order'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </span>
            </label>
            <label class="toggle">
              <span>Promotions & discounts</span>
              <span class="switch">
                <input type="checkbox" name="notify_promotions" <?php echo $userSettings['notify_promotions'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </span>
            </label>
            <label class="toggle">
              <span>Feedback reminders</span>
              <span class="switch">
                <input type="checkbox" name="notify_feedback" <?php echo $userSettings['notify_feedback'] ? 'checked' : ''; ?>>
                <span class="slider"></span>
              </span>
            </label>
          </div>
        </div>

        <?php if (!empty($message)): ?>
          <div class="message <?php echo strpos($message, 'successfully') === false ? 'error' : ''; ?>">
            <?php echo htmlspecialchars($message); ?>
          </div>
        <?php endif; ?>

        <div class="save-row">
          <button type="submit">Save preferences</button>
        </div>
      </form>
    </div>
  </main>

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
          const newUrl = `${window.location.pathname}?${params.toString()}`;
          window.history.replaceState({}, '', newUrl);
        }
      }
    });
  </script>
</body>
</html>
