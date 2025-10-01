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
$menuActive = in_array($currentScript, $menuScripts, true) ? 'active' : '';
$cartActive = $currentScript === 'cart_checkout_page.php' ? 'active' : '';
?>
<header>
  <div class="logo">
    <img src="<?php echo $rootPrefix; ?>Images/logo.png" alt="Cindy's Logo">
  </div>
  <div class="nav">
    <a href="<?php echo $userPrefix; ?>PRODUCT/MENU.php" class="<?php echo $menuActive; ?>">Menu</a>
    <a href="<?php echo $userPrefix; ?>CART/cart_checkout_page.php" class="<?php echo $cartActive; ?>">Cart</a>
    <div class="dropdown">
      <button type="button" onclick="toggleDropdown()">Profile</button>
      <div class="dropdown-content" id="profileDropdown">
        <a href="<?php echo $userPrefix; ?>PROFILE/EditProfile.php">Edit Profile</a>
        <a href="<?php echo $userPrefix; ?>PURCHASES/MyPurchase.php">My Purchases</a>
        <a href="<?php echo $userPrefix; ?>PROFILE/Settings.php">Settings</a>
      </div>
    </div>
    <a href="<?php echo $userPrefix; ?>logout.html">Logout</a>
  </div>
</header>
<script>
function toggleDropdown() {
  const dropdown = document.getElementById('profileDropdown');
  if (dropdown) {
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
  }
}

window.addEventListener('click', function(event) {
  if (!event.target.matches('button')) {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown && dropdown.style.display === 'block') {
      dropdown.style.display = 'none';
    }
  }
});
</script>
