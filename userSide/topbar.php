<header>
  <div class="logo">
    <img src="../Images/cindy's logo.png" alt="Cindy's Logo">
  </div>
  <div class="nav">
    <a href="../PRODUCT/MENU.php" class="active">Menu</a>
    <a href="../CART/cart_checkout_page.php">Cart</a>
    <div class="dropdown">
      <button onclick="toggleDropdown()">Profile</button>
      <div class="dropdown-content" id="profileDropdown">
          <a href="../PROFILE/EditProfile.php">Edit Profile</a>
          <a href="../PURCHASES/MyPurchase.php">My Purchases</a>
        <a href="../PROFILE/Settings.php">Settings</a>
      </div>
    </div>
    <a href="../LOGIN_SIGNUP/logout.html">Logout</a>
  </div>
</header>
<script>
function toggleDropdown() {
  const dropdown = document.getElementById('profileDropdown');
  dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

window.onclick = function(event) {
  if (!event.target.matches('button')) {
    const dropdown = document.getElementById('profileDropdown');
    if (dropdown && dropdown.style.display === 'block') {
      dropdown.style.display = 'none';
    }
  }
};
</script>
