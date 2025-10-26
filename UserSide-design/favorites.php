<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Favorites • Cindy’s Bakeshop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
    body { background:#fdf8f2; color:#3c2a1d; min-height:100vh; display:flex; flex-direction:column; }

    nav { background:#fff9f3; display:flex; justify-content:space-between; align-items:center; padding:1rem 2rem; box-shadow:0 1px 6px rgba(0,0,0,0.06); position:sticky; top:0; z-index:999; }
    nav .logo { font-size:1.3rem; font-weight:600; color:#a66e44; display:flex; align-items:center; gap:.6rem; }
    nav .logo img { height:42px; }
    nav ul { list-style:none; display:flex; gap:1.2rem; align-items:center; }
    nav ul li a { color:#3c2a1d; text-decoration:none; font-weight:500; }
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
    .profile-dropdown .dropdown-menu { position:absolute; top:120%; right:0; background:#fff; color:#3c2a1d; border-radius:8px; box-shadow:0 3px 10px rgba(0,0,0,0.08); display:none; flex-direction:column; min-width:190px; z-index:2000; }
    .profile-dropdown .dropdown-menu li { padding:0.7rem 1rem; }
    .profile-dropdown .dropdown-menu li a { text-decoration:none; color:#3c2a1d; display:block; }
    .profile-dropdown .dropdown-menu li:hover { background:#f9f6f2; }
    .profile-dropdown .dropdown-menu li[data-auth="required"] { display:none; }
    .profile-dropdown.show .dropdown-menu { display:flex; }

    main { flex:1; }
    .favorites-wrapper { max-width:1080px; margin:2.5rem auto; padding:0 1.5rem 3rem; }
    .favorites-header { display:flex; justify-content:space-between; flex-wrap:wrap; align-items:center; gap:1rem; margin-bottom:1.8rem; }
    .favorites-header h1 { font-size:2rem; color:#603818; }
    .favorites-header p { color:#806552; }

    .favorites-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:1.5rem; }
    .favorite-card { background:#fff; border-radius:16px; box-shadow:0 10px 22px rgba(0,0,0,0.08); overflow:hidden; display:flex; flex-direction:column; }
    .favorite-card img { width:100%; height:180px; object-fit:cover; }
    .favorite-card .card-body { padding:1.2rem; display:flex; flex-direction:column; gap:0.7rem; flex:1; }
    .favorite-card h3 { font-size:1.15rem; color:#a66e44; }
    .favorite-card p { color:#6f5844; font-size:0.9rem; flex:1; }
    .card-actions { display:flex; gap:0.6rem; margin-top:auto; }
    .card-actions a, .card-actions button { flex:1; padding:0.6rem 0.75rem; border-radius:999px; border:none; font-weight:600; cursor:pointer; text-align:center; text-decoration:none; transition:background 0.2s, transform 0.2s; }
    .card-actions a { background:#fff3e5; color:#c25d21; }
    .card-actions a:hover { background:#ffd9bc; }
    .card-actions button { background:#f3e2da; color:#8b4513; }
    .card-actions button:hover { background:#e5cdbd; transform:translateY(-1px); }

    .empty-state { margin:3rem auto; text-align:center; color:#7a6152; max-width:420px; font-size:1rem; }
    .explore-btn { margin:2rem auto 0; display:block; width:max-content; padding:0.75rem 1.6rem; border-radius:999px; background:#c25d21; color:#fff; border:none; font-weight:600; cursor:pointer; box-shadow:0 10px 20px rgba(194,93,33,0.22); }
    .explore-btn:hover { background:#a74d16; }

    .toast { position:fixed; bottom:28px; left:50%; transform:translateX(-50%); background:#c25d21; color:#fff; padding:0.8rem 1.5rem; border-radius:26px; opacity:0; pointer-events:none; transition:opacity 0.4s, transform 0.4s; z-index:3000; font-weight:600; }
    .toast.show { opacity:1; transform:translate(-50%, -12px); }
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
          <li data-auth="required"><a href="settings.php">Account Settings</a></li>
          <li data-auth="required"><a href="logout.php">Logout</a></li>
          <li data-auth="guest"><a href="login.php">Login</a></li>
          <li data-auth="guest"><a href="signup.php">Sign Up</a></li>
        </ul>
      </li>
    </ul>
  </nav>

  <main>
    <section class="favorites-wrapper">
      <div class="favorites-header">
        <div>
          <h1>My Favorite Treats</h1>
          <p>Savor the bakes you love the most. Remove items anytime or jump back to the menu for more.</p>
        </div>
      </div>
      <div class="favorites-grid" id="favoritesGrid"></div>
      <div class="empty-state" id="emptyState" style="display:none;">
        Your favorites list is empty. Browse our menu and tap the heart icon to save breads, cakes, and pastries you adore!
      </div>
      <button class="explore-btn" type="button" id="exploreBtn">Explore Menu</button>
    </section>
  </main>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>

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

    const favoritesGrid = document.getElementById('favoritesGrid');
    const emptyState = document.getElementById('emptyState');
    const toast = document.getElementById('toast');
    const exploreBtn = document.getElementById('exploreBtn');

    exploreBtn?.addEventListener('click', () => {
      window.location.href = 'menu.php';
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

    function showToast(message) {
      if (!toast) return;
      toast.textContent = message;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2400);
    }

    function buildImagePath(item) {
      const path = item?.Image_Path || '';
      if (!path) {
        return '../userSide/Images/cindy_s logo.png';
      }
      return `/adminSide/products/uploads/${path}`;
    }

    async function removeFavorite(favoriteId) {
      try {
        const resp = await fetch('/PHP/favorite_api.php?action=remove', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ favorite_id: favoriteId })
        });
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        if (data.deleted) {
          showToast('Removed from favorites');
          await loadFavorites();
        }
      } catch (err) {
        console.error('Failed to remove favorite', err);
        showToast('Unable to remove this favorite right now');
      }
    }

    function renderFavorites(favorites) {
      favoritesGrid.innerHTML = '';
      if (!favorites.length) {
        emptyState.style.display = 'block';
        return;
      }
      emptyState.style.display = 'none';

      favorites.forEach((item) => {
        const card = document.createElement('div');
        card.className = 'favorite-card';

        const img = document.createElement('img');
        img.src = buildImagePath(item);
        img.alt = item.Name ? String(item.Name) : 'Favorite product';

        const body = document.createElement('div');
        body.className = 'card-body';

        const title = document.createElement('h3');
        title.textContent = item.Name ? String(item.Name) : 'Favorite Item';

        const description = document.createElement('p');
        description.textContent = 'Keep this bake close! Tap below to view details or remove it from your list.';

        const actions = document.createElement('div');
        actions.className = 'card-actions';

        const viewLink = document.createElement('a');
        viewLink.href = `product.php?id=${item.Product_ID}`;
        viewLink.textContent = 'View Product';
        viewLink.setAttribute('aria-label', `View ${item.Name ? String(item.Name) : 'product'} details`);

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove';
        removeBtn.addEventListener('click', () => removeFavorite(item.Favorite_ID));

        actions.appendChild(viewLink);
        actions.appendChild(removeBtn);

        body.appendChild(title);
        body.appendChild(description);
        body.appendChild(actions);

        card.appendChild(img);
        card.appendChild(body);
        favoritesGrid.appendChild(card);
      });
    }

    async function loadFavorites() {
      if (!userEmail) return;
      try {
        const resp = await fetch(`/PHP/favorite_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        const favorites = Array.isArray(data) ? data : [];
        renderFavorites(favorites);
      } catch (err) {
        console.error('Unable to load favorites', err);
        showToast('Unable to load your favorites');
        favoritesGrid.innerHTML = '';
        emptyState.style.display = 'block';
      }
    }

    onAuthStateChanged(auth, async (user) => {
      if (!user) {
        window.location.href = 'login.php';
        return;
      }
      userEmail = user.email;
      updateProfileMenu();
      await loadFavorites();
    });

    updateProfileMenu();
  </script>
</body>
</html>
