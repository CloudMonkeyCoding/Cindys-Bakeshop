<?php
$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
$callerFile = isset($trace[0]['file']) ? $trace[0]['file'] : __FILE__;
$callerDir = str_replace('\\', '/', dirname($callerFile));
$baseDir = str_replace('\\', '/', __DIR__);
$depth = 0;
if (strpos($callerDir, $baseDir) === 0) {
    $relative = trim(substr($callerDir, strlen($baseDir)), '/');
    $depth = $relative === '' ? 0 : substr_count($relative, '/') + 1;
}
$userPrefix = str_repeat('../', $depth);
$rootPrefix = str_repeat('../', $depth + 2);
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

$menuScripts = array('MENU.php', 'bread.php', 'cakes.php', 'pastry.php', 'product.php');
$favoriteScripts = array('my favorite.php');
$profileScripts = array('EditProfile.php', 'Settings.php');
$purchasesScripts = array('MyPurchase.php');

$apiBase = $rootPrefix . 'PHP/';
$imagesBase = $rootPrefix . 'Images/';

$navItems = [
    [
        'label' => 'Home',
        'href' => $userPrefix . 'home.html',
        'match' => ['home.html', 'index.php']
    ],
    [
        'label' => 'Menu',
        'href' => $userPrefix . 'PRODUCT/MENU.php',
        'match' => $menuScripts
    ],
    [
        'label' => 'Favorites',
        'href' => $userPrefix . 'FAVORITE/my favorite.php',
        'match' => $favoriteScripts
    ],
    [
        'label' => 'Orders',
        'href' => $userPrefix . 'PURCHASES/MyPurchase.php',
        'match' => array_merge($purchasesScripts, ['orderDetails.php'])
    ]
];

$activeResolver = function (array $needles) use ($currentScript): string {
    foreach ($needles as $needle) {
        if (strcasecmp($currentScript, $needle) === 0) {
            return 'active';
        }
    }
    return '';
};
?>
<header id="mainHeader" data-api-base="<?= htmlspecialchars($apiBase, ENT_QUOTES) ?>" data-images-base="<?= htmlspecialchars($imagesBase, ENT_QUOTES) ?>" data-root-prefix="<?= htmlspecialchars($rootPrefix, ENT_QUOTES) ?>" data-user-prefix="<?= htmlspecialchars($userPrefix, ENT_QUOTES) ?>">
  <div class="header-content">
    <div class="logo">
      <span class="logo-icon" aria-hidden="true">🥐</span>
      <a href="<?= htmlspecialchars($userPrefix . 'home.html', ENT_QUOTES) ?>" class="logo-text">Cindy's Bakeshop</a>
    </div>

    <button type="button" class="menu-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">☰</button>

    <nav aria-label="Main navigation">
      <ul id="mainNav">
        <?php foreach ($navItems as $item): ?>
          <?php $active = $activeResolver($item['match']); ?>
          <li><a href="<?= htmlspecialchars($item['href'], ENT_QUOTES) ?>" class="<?= $active ?>"><?= htmlspecialchars($item['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="nav-right">
      <a class="cart-link" href="<?= htmlspecialchars($userPrefix . 'CART/cart_checkout_page.php', ENT_QUOTES) ?>">
        <span aria-hidden="true">🛒</span>
        <span>Cart</span>
      </a>
      <div class="profile-dropdown">
        <button type="button" class="profile-trigger" id="profileToggle" aria-haspopup="true" aria-expanded="false">
          <img src="<?= htmlspecialchars($imagesBase . 'logo.png', ENT_QUOTES) ?>" alt="User avatar" class="profile-img" id="profileAvatar">
          <span class="profile-meta">
            <strong id="profileName">Guest</strong>
            <span id="profileEmail">Sign in</span>
          </span>
        </button>
        <ul class="dropdown-menu" id="profileMenu">
          <li><a href="<?= htmlspecialchars($userPrefix . 'PROFILE/EditProfile.php', ENT_QUOTES) ?>">Edit Profile</a></li>
          <li><a href="<?= htmlspecialchars($userPrefix . 'PROFILE/Settings.php', ENT_QUOTES) ?>" data-settings-link="true">Account Settings</a></li>
          <li><a href="<?= htmlspecialchars($userPrefix . 'PURCHASES/MyPurchase.php', ENT_QUOTES) ?>">Order History</a></li>
          <li><a href="<?= htmlspecialchars($userPrefix . 'logout.html', ENT_QUOTES) ?>">Logout</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
<script type="module" src="<?= htmlspecialchars($userPrefix . 'js/topbar.js', ENT_QUOTES) ?>"></script>
