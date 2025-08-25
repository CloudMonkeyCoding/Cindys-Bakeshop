<?php ?>
<style>
  header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: red;
    padding: 16px 40px;
    border-bottom: 2px solid red;
  }

  .logo img {
    height: 80px;
  }

  .nav {
    display: flex;
    align-items: center;
    gap: 18px;
  }

  .nav a {
    padding: 10px 24px;
    border-radius: 30px;
    background: #d6e200;
    text-decoration: none;
    color: black;
    font-weight: bold;
    transition: 0.3s;
  }

  .nav a:hover,
  .nav a.active {
    background: black;
    color: white;
  }

  .dropdown {
    position: relative;
  }

  .dropdown button {
    padding: 10px 24px;
    border-radius: 30px;
    background: #d6e200;
    font-weight: bold;
    border: none;
    cursor: pointer;
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

  .dropdown-content a:hover {
    background-color: #f1f1f1;
  }
</style>
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
