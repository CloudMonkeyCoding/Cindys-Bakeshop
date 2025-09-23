<?php
require_once __DIR__ . '/../PHP/db_connect.php';
require_once __DIR__ . '/../PHP/product_functions.php';

$products = [];
if ($pdo) {
    try {
        $products = getAllProducts($pdo) ?: [];
    } catch (Throwable $e) {
        $products = [];
    }
}

usort($products, static function (array $a, array $b): int {
    return strcasecmp($a['Name'] ?? '', $b['Name'] ?? '');
});

$bestSellerIds = [];
if ($products) {
    $byStock = $products;
    usort($byStock, static function (array $a, array $b): int {
        return (int)($b['Stock_Quantity'] ?? 0) <=> (int)($a['Stock_Quantity'] ?? 0);
    });
    $bestSellerIds = array_slice(array_map(static fn ($p) => (int)($p['Product_ID'] ?? 0), $byStock), 0, 6);
}

function menu_image_path(array $product): string
{
    $path = $product['Image_Path'] ?? '';
    if ($path) {
        return '/adminSide/products/uploads/' . ltrim($path, '/');
    }
    $name = $product['Name'] ?? 'Product';
    return 'https://via.placeholder.com/300x180?text=' . rawurlencode($name);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cindy’s Bakeshop - Menu</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
    body { background:#fcfbf9; color:#3c3c3c; line-height:1.5; }

    nav { background:#fff9f3; display:flex; justify-content:space-between; align-items:center; padding:1rem 2rem; position:sticky; top:0; z-index:1000; box-shadow:0 1px 5px rgba(0,0,0,0.05); }
    nav .logo { font-size:1.3rem; font-weight:600; color:#a66e44; display:flex; align-items:center; gap:.5rem; }
    nav .logo img { height:42px; }
    nav ul { list-style:none; display:flex; gap:1.2rem; align-items:center; }
    nav ul li a { color:#3c3c3c; text-decoration:none; font-weight:500; }
    nav ul li a:hover { color:#a66e44; }
    nav .menu-toggle { display:none; font-size:1.8rem; cursor:pointer; }

    @media (max-width:768px) {
      nav ul { display:none; flex-direction:column; gap:1rem; background:#fff9f3; position:absolute; top:60px; left:0; right:0; padding:1rem 2rem; border-top:1px solid #e2d6c9; }
      nav ul.show { display:flex; }
      .menu-toggle { display:block; }
      .profile-dropdown .dropdown-menu { position:static; box-shadow:none; border-radius:6px; background:#fff9f3; margin-top:0.5rem; width:100%; }
      .profile-dropdown .dropdown-menu li { padding:0.7rem 0.5rem; border-top:1px solid #f0e5da; }
      .profile-dropdown .dropdown-menu li:first-child { border-top:none; }
    }

    .profile-dropdown { position:relative; }
    .profile-dropdown span { cursor:pointer; display:flex; align-items:center; gap:.25rem; }
    .profile-dropdown .dropdown-menu { position:absolute; top:120%; right:0; background:#fff; color:#3c3c3c; border-radius:8px; box-shadow:0 3px 10px rgba(0,0,0,0.08); display:none; flex-direction:column; min-width:180px; z-index:2000; }
    .profile-dropdown .dropdown-menu li { padding:0.7rem 1rem; }
    .profile-dropdown .dropdown-menu li a { text-decoration:none; color:#3c3c3c; display:block; }
    .profile-dropdown .dropdown-menu li:hover { background:#f9f6f2; }
    .profile-dropdown .dropdown-menu li[data-auth="required"] { display:none; }
    .profile-dropdown.show .dropdown-menu { display:flex; }

    header { background:#fff4ea; text-align:center; padding:2.5rem 1rem; }
    header h1 { font-size:2.2rem; font-weight:600; color:#a66e44; }
    header p { font-size:1rem; opacity:0.85; margin-top:0.3rem; }

    .best-sellers { max-width:1200px; margin:1.5rem auto; background:#fff8e7; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.08); }
    .best-sellers h2 { font-size:1.5rem; margin-bottom:15px; color:#d35400; }
    .best-seller-list { display:flex; gap:1rem; overflow-x:auto; padding:0.5rem 0; }
    .best-seller-list::-webkit-scrollbar { height:8px; }
    .best-seller-list::-webkit-scrollbar-thumb { background:#ffb703; border-radius:4px; }

    .controls { max-width:1200px; margin:1.5rem auto; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; padding:0 1rem; gap:1rem; }
    .categories { display:flex; gap:0.6rem; flex-wrap:wrap; }
    .categories button { padding:0.5rem 1rem; border:1px solid #e2d6c9; border-radius:20px; background:#fff; cursor:pointer; font-weight:500; transition:0.2s; }
    .categories button.active, .categories button:hover { background:#a66e44; color:#fff; border-color:#a66e44; }
    .search-bar input { padding:0.5rem 1rem; border:1px solid #e2d6c9; border-radius:20px; outline:none; width:220px; background:#fff; }

    .menu-section { max-width:1200px; margin:1rem auto 3rem; padding:0 1rem; }
    .menu-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 280px)); justify-content:left; gap:1.2rem; }
    .menu-item { position:relative; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 1px 6px rgba(0,0,0,0.05); transition:0.2s; }
    .menu-item:hover { transform:translateY(-2px); box-shadow:0 3px 10px rgba(0,0,0,0.07); }
    .menu-item img { width:100%; height:170px; object-fit:cover; }
    .product-link { display:block; }
    .favorite-btn { position:absolute; top:10px; right:10px; cursor:pointer; font-size:1.3rem; width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,0.9); display:flex; align-items:center; justify-content:center; color:#bbb; transition:all 0.25s ease; box-shadow:0 2px 6px rgba(0,0,0,0.12); }
    .favorite-btn::before { content:"♡"; }
    .favorite-btn:hover { background:#fff; transform:scale(1.1); color:#e76f51; }
    .favorite-btn.favorited { background:#ffeaea; color:#e63946; transform:scale(1.15); box-shadow:0 0 10px rgba(230,57,70,0.3); }
    .favorite-btn.favorited::before { content:"♥"; }

    .menu-content { padding:1rem; display:flex; flex-direction:column; gap:.6rem; }
    .menu-content h3 { font-size:1.1rem; font-weight:600; color:#a66e44; }
    .menu-content h3 a { color:inherit; text-decoration:none; }
    .menu-content h3 a:hover { color:#d2691e; }
    .menu-desc { font-size:.85rem; color:#666; min-height:2.5em; }
    .menu-footer { display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap; }
    .price { font-size:1rem; font-weight:bold; color:#a66e44; }
    .stock { font-size:0.85rem; color:#666; margin-left:auto; }
    .stock.out-of-stock { color:#d62828; font-weight:600; }
    .add-btn { margin-left:auto; background:#a66e44; color:#fff; border:none; border-radius:20px; padding:0.45rem 1.1rem; cursor:pointer; font-size:0.9rem; transition:background 0.2s; }
    .add-btn:hover { background:#b77952; }
    .add-btn:disabled { background:#d0c4ba; cursor:not-allowed; }

    footer { text-align:center; padding:1.5rem; background:#fff9f3; color:#3c3c3c; margin-top:2rem; font-size:0.9rem; }

    .modal { display:none; position:fixed; z-index:3000; left:50%; top:50%; transform:translate(-50%,-50%); background:#fff; border-radius:12px; box-shadow:0 3px 12px rgba(0,0,0,0.15); width:320px; max-width:90%; padding:1.5rem; text-align:center; }
    .modal.show { display:block; }
    .quantity-control { display:flex; justify-content:center; align-items:center; gap:1rem; margin:1rem 0; }
    .quantity-control button { width:34px; height:34px; border-radius:50%; border:none; background:#a66e44; color:#fff; font-size:1.2rem; cursor:pointer; }
    .quantity-control button:disabled { background:#d0c4ba; cursor:not-allowed; }
    .modal-actions { display:flex; justify-content:space-between; margin-top:1rem; gap:10px; }
    .modal-actions button { flex:1; padding:0.6rem; border-radius:20px; border:none; font-weight:600; cursor:pointer; }
    #confirmAdd { background:#a66e44; color:#fff; }
    #confirmAdd:hover { background:#b77952; }
    #cancelBtn { background:#ddd; color:#333; }
    #cancelBtn:hover { background:#bbb; }

    .toast { position:fixed; bottom:30px; left:50%; transform:translateX(-50%); background:#a66e44; color:#fff; padding:0.8rem 1.5rem; border-radius:25px; opacity:0; pointer-events:none; transition:opacity 0.5s, transform 0.5s; z-index:4000; font-weight:500; }
    .toast.show { opacity:1; transform:translateX(-50%) translateY(-10px); }
    .empty-state { text-align:center; padding:3rem 1rem; color:#777; }
  </style>
</head>
<body>
  <nav>
    <div class="logo"><img src="../Kehnt_admin_Design/Cindys.png" alt="Cindy’s Logo"/> Cindy’s Bakeshop</div>
    <div class="menu-toggle" id="menuToggle">☰</div>
    <ul id="navMenu">
      <li><a href="home.php">Home</a></li>
      <li><a href="menu.php" class="active">Menu</a></li>
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

  <header>
    <h1>Freshly Baked with Love</h1>
    <p>Delicious breads, cakes, and pastries made daily</p>
  </header>

  <section class="best-sellers">
    <h2>⭐ Best Sellers</h2>
    <div class="best-seller-list" id="bestSellerList">
      <p class="empty-state" id="bestSellerEmpty" style="display:none; width:100%;">No best sellers to display yet.</p>
    </div>
  </section>

  <div class="controls">
    <div class="categories">
      <button class="active" data-category="all">All</button>
      <button data-category="bread">Breads</button>
      <button data-category="cakes">Cakes</button>
      <button data-category="pastry">Pastries</button>
      <button data-category="favorites">Favorites ❤️</button>
    </div>
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search for items..." />
    </div>
  </div>

  <section class="menu-section">
    <div class="menu-grid" id="menuGrid">
      <?php foreach ($products as $product):
        $category = strtolower($product['Category'] ?? '');
        $productId = (int)($product['Product_ID'] ?? 0);
        $priceRaw = number_format((float)($product['Price'] ?? 0), 2, '.', '');
        $priceDisplay = number_format((float)($product['Price'] ?? 0), 2);
        $stock = (int)($product['Stock_Quantity'] ?? 0);
        $isBest = in_array($productId, $bestSellerIds, true);
        $description = trim((string)($product['Description'] ?? ''));
      ?>
      <div class="menu-item" data-category="<?= htmlspecialchars($category) ?>" data-bestseller="<?= $isBest ? 'true' : 'false' ?>" data-stock="<?= $stock ?>" data-product-id="<?= $productId ?>" data-price="<?= htmlspecialchars($priceRaw, ENT_QUOTES) ?>">
        <a class="product-link" href="product.php?id=<?= $productId ?>">
          <img src="<?= htmlspecialchars(menu_image_path($product)) ?>" alt="<?= htmlspecialchars($product['Name'] ?? 'Product') ?>"/>
        </a>
        <span class="favorite-btn" data-product-id="<?= $productId ?>" aria-label="Toggle favorite"></span>
        <div class="menu-content">
          <h3><a href="product.php?id=<?= $productId ?>"><?= htmlspecialchars($product['Name'] ?? 'Product') ?></a></h3>
          <p class="menu-desc"><?= htmlspecialchars($description ?: 'Freshly baked and ready to serve!') ?></p>
          <div class="menu-footer">
            <span class="price">₱<?= htmlspecialchars($priceDisplay) ?></span>
            <span class="stock">Stock: <?= $stock ?></span>
            <button class="add-btn" data-product-id="<?= $productId ?>" data-name="<?= htmlspecialchars($product['Name'] ?? 'Product') ?>" data-price="<?= htmlspecialchars($priceRaw, ENT_QUOTES) ?>">Add to Cart</button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php if (!$products): ?>
      <div class="empty-state">No products found. Please check back later.</div>
    <?php endif; ?>
  </section>

  <footer>© <?= date('Y') ?> Cindy’s Bakeshop • Freshness Guaranteed</footer>

  <div class="modal" id="cartModal" role="dialog" aria-modal="true" aria-labelledby="modalItemName">
    <h2 id="modalItemName"></h2>
    <p id="modalItemPrice" style="font-weight:600; color:#d2691e"></p>
    <div class="quantity-control">
      <button id="decreaseQty" aria-label="Decrease quantity">−</button>
      <span id="itemQty">1</span>
      <button id="increaseQty" aria-label="Increase quantity">+</button>
    </div>
    <p id="modalTotal" style="margin:10px 0; font-weight:600">Total: ₱0.00</p>
    <div class="modal-actions">
      <button id="cancelBtn">Cancel</button>
      <button id="confirmAdd">Add to Cart</button>
    </div>
  </div>

  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    menuToggle?.addEventListener('click', () => navMenu.classList.toggle('show'));

    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');
    profileToggle?.addEventListener('click', () => profileMenu.parentElement.classList.toggle('show'));
    window.addEventListener('click', (e) => {
      if (!e.target.closest('.profile-dropdown')) {
        profileMenu.parentElement.classList.remove('show');
      }
    });

    const categoryButtons = document.querySelectorAll('.categories button');
    const menuItems = Array.from(document.querySelectorAll('.menu-item'));
    const searchInput = document.getElementById('searchInput');
    const toast = document.getElementById('toast');
    const modal = document.getElementById('cartModal');
    const modalItemName = document.getElementById('modalItemName');
    const modalItemPrice = document.getElementById('modalItemPrice');
    const modalTotal = document.getElementById('modalTotal');
    const itemQty = document.getElementById('itemQty');
    const decreaseQty = document.getElementById('decreaseQty');
    const increaseQty = document.getElementById('increaseQty');
    const confirmAdd = document.getElementById('confirmAdd');
    const cancelBtn = document.getElementById('cancelBtn');
    const bestSellerList = document.getElementById('bestSellerList');
    const bestSellerEmpty = document.getElementById('bestSellerEmpty');

    const auth = getAuth();
    let userEmail = null;
    let cartId = null;
    let currentItem = null;
    const cartQuantities = new Map();
    const favoritesMap = new Map();

    function showToast(message) {
      if (!toast) return;
      toast.textContent = message;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2400);
    }

    function updateProfileMenu() {
      if (!profileToggle || !profileMenu) return;
      const authedItems = profileMenu.querySelectorAll('[data-auth="required"]');
      const guestItems = profileMenu.querySelectorAll('[data-auth="guest"]');
      if (userEmail) {
        profileToggle.textContent = `${userEmail} ▾`;
        authedItems.forEach(item => { item.style.display = 'block'; });
        guestItems.forEach(item => { item.style.display = 'none'; });
      } else {
        profileToggle.textContent = 'Login ▾';
        authedItems.forEach(item => { item.style.display = 'none'; });
        guestItems.forEach(item => { item.style.display = 'block'; });
        profileMenu.parentElement.classList.remove('show');
      }
    }

    function updateStockDisplay(item, available) {
      const stockSpan = item.querySelector('.stock');
      const addBtn = item.querySelector('.add-btn');
      if (!stockSpan || !addBtn) return;
      if (available > 0) {
        stockSpan.textContent = `Stock: ${available}`;
        stockSpan.classList.remove('out-of-stock');
        addBtn.disabled = false;
      } else {
        stockSpan.textContent = 'Out of stock';
        stockSpan.classList.add('out-of-stock');
        addBtn.disabled = true;
      }
    }

    function applyCartQuantities() {
      menuItems.forEach(item => {
        const stock = parseInt(item.dataset.stock ?? '0', 10);
        const productId = item.dataset.productId;
        const existing = cartQuantities.get(productId) || 0;
        const available = Math.max(stock - existing, 0);
        item.dataset.available = String(available);
        updateStockDisplay(item, available);
      });
    }

    async function loadCart() {
      if (!userEmail) {
        cartId = null;
        cartQuantities.clear();
        applyCartQuantities();
        return;
      }
      try {
        const url = `/PHP/cart_api.php?action=list&email=${encodeURIComponent(userEmail)}`;
        const resp = await fetch(url);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        cartId = data.cart_id || null;
        cartQuantities.clear();
        if (Array.isArray(data.items)) {
          data.items.forEach(item => {
            cartQuantities.set(String(item.Product_ID), parseInt(item.Quantity, 10) || 0);
          });
        }
        applyCartQuantities();
      } catch (err) {
        console.error('Failed to load cart', err);
      }
    }

    function updateFavoriteButtons() {
      document.querySelectorAll('.favorite-btn').forEach(btn => {
        const productId = btn.dataset.productId;
        if (favoritesMap.has(productId)) {
          btn.classList.add('favorited');
        } else {
          btn.classList.remove('favorited');
        }
      });
    }

    async function loadFavorites() {
      if (!userEmail) {
        favoritesMap.clear();
        updateFavoriteButtons();
        return;
      }
      try {
        const url = `/PHP/favorite_api.php?action=list&email=${encodeURIComponent(userEmail)}`;
        const resp = await fetch(url);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        favoritesMap.clear();
        if (Array.isArray(data)) {
          data.forEach(entry => {
            favoritesMap.set(String(entry.Product_ID), entry.Favorite_ID);
          });
        }
        updateFavoriteButtons();
      } catch (err) {
        console.error('Failed to load favorites', err);
      }
    }

    async function toggleFavorite(productId, button) {
      if (!userEmail) {
        window.location.href = 'login.php';
        return;
      }
      try {
        if (favoritesMap.has(productId)) {
          const favoriteId = favoritesMap.get(productId);
          const resp = await fetch('/PHP/favorite_api.php?action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ favorite_id: favoriteId })
          });
          const text = await resp.text();
          if (!resp.ok) throw new Error(text);
          const data = JSON.parse(text);
          if (data.deleted) {
            favoritesMap.delete(productId);
            button.classList.remove('favorited');
            showToast('Removed from favorites');
          }
        } else {
          const params = new URLSearchParams({ email: userEmail, product_id: productId });
          const resp = await fetch('/PHP/favorite_api.php?action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params
          });
          const text = await resp.text();
          if (!resp.ok) throw new Error(text);
          const data = JSON.parse(text);
          if (data.favorite_id) {
            favoritesMap.set(productId, data.favorite_id);
            button.classList.add('favorited');
            showToast('Added to favorites');
          }
        }
      } catch (err) {
        console.error('Failed to toggle favorite', err);
        showToast('Unable to update favorites');
      }
    }

    function reorderFavorites() {
      const grid = document.getElementById('menuGrid');
      if (!grid) return;
      const items = Array.from(grid.querySelectorAll('.menu-item'));
      items.sort((a, b) => {
        const aFav = favoritesMap.has(a.dataset.productId) ? 1 : 0;
        const bFav = favoritesMap.has(b.dataset.productId) ? 1 : 0;
        if (bFav !== aFav) return bFav - aFav;
        return (a.dataset.category || '').localeCompare(b.dataset.category || '');
      });
      items.forEach(item => grid.appendChild(item));
    }

    function filterMenu(category) {
      const query = (searchInput?.value || '').toLowerCase();
      let visibleCount = 0;
      menuItems.forEach(item => {
        const name = (item.querySelector('h3')?.textContent || '').toLowerCase();
        let matchesCategory = category === 'all' || item.dataset.category === category;
        if (category === 'favorites') {
          matchesCategory = favoritesMap.has(item.dataset.productId);
          if (!userEmail) {
            matchesCategory = false;
          }
        }
        const matchesSearch = !query || name.includes(query);
        const show = matchesCategory && matchesSearch;
        item.style.display = show ? '' : 'none';
        if (show) visibleCount += 1;
      });
      if (category === 'favorites' && !userEmail) {
        showToast('Login to view your favorites');
      } else if (category === 'favorites' && visibleCount === 0) {
        showToast('No favorite items yet');
      }
    }

    categoryButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        categoryButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterMenu(btn.dataset.category);
      });
    });

    searchInput?.addEventListener('keyup', () => {
      const active = document.querySelector('.categories button.active');
      const category = active ? active.dataset.category : 'all';
      filterMenu(category);
    });

    const bestSellerItems = menuItems.filter(item => item.dataset.bestseller === 'true');
    if (bestSellerItems.length === 0) {
      bestSellerEmpty.style.display = 'block';
    } else {
      bestSellerEmpty.style.display = 'none';
      bestSellerItems.forEach(item => {
        const clone = item.cloneNode(true);
        clone.querySelectorAll('.add-btn').forEach(btn => {
          btn.addEventListener('click', (event) => {
            event.preventDefault();
            const original = item.querySelector('.add-btn');
            original?.dispatchEvent(new Event('click'));
          });
        });
        clone.querySelectorAll('.favorite-btn').forEach(btn => {
          btn.addEventListener('click', (event) => {
            event.preventDefault();
            const original = item.querySelector('.favorite-btn');
            original?.dispatchEvent(new Event('click'));
          });
        });
        bestSellerList.appendChild(clone);
      });
    }

    function openModal(itemElement) {
      const addBtn = itemElement.querySelector('.add-btn');
      const available = parseInt(itemElement.dataset.available ?? itemElement.dataset.stock ?? '0', 10);
      currentItem = {
        id: addBtn.dataset.productId,
        name: addBtn.dataset.name,
        price: parseFloat(addBtn.dataset.price),
        available
      };
      const price = currentItem.price || 0;
      modalItemName.textContent = currentItem.name;
      modalItemPrice.textContent = `₱${price.toFixed(2)}`;
      itemQty.textContent = available > 0 ? '1' : '0';
      decreaseQty.disabled = true;
      increaseQty.disabled = available <= 1;
      updateModalTotal();
      modal.classList.add('show');
    }

    function updateModalTotal() {
      if (!currentItem) return;
      const qty = parseInt(itemQty.textContent || '0', 10);
      const total = (currentItem.price || 0) * qty;
      modalTotal.textContent = qty > 0 ? `Total: ₱${total.toFixed(2)}` : 'Out of stock';
    }

    document.querySelectorAll('.add-btn').forEach(btn => {
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        const item = btn.closest('.menu-item');
        if (!item) return;
        const available = parseInt(item.dataset.available ?? item.dataset.stock ?? '0', 10);
        if (available <= 0) {
          showToast('This item is currently out of stock');
          return;
        }
        if (!userEmail) {
          window.location.href = 'login.php';
          return;
        }
        openModal(item);
      });
    });

    document.querySelectorAll('.favorite-btn').forEach(btn => {
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        toggleFavorite(btn.dataset.productId, btn);
      });
    });

    increaseQty.addEventListener('click', () => {
      const current = parseInt(itemQty.textContent || '1', 10);
      if (!currentItem) return;
      if (current < currentItem.available) {
        itemQty.textContent = String(current + 1);
        decreaseQty.disabled = false;
        if (current + 1 >= currentItem.available) {
          increaseQty.disabled = true;
        }
        updateModalTotal();
      }
    });

    decreaseQty.addEventListener('click', () => {
      const current = parseInt(itemQty.textContent || '1', 10);
      if (current <= 1) {
        decreaseQty.disabled = true;
        return;
      }
      itemQty.textContent = String(current - 1);
      increaseQty.disabled = false;
      decreaseQty.disabled = current - 1 <= 1;
      updateModalTotal();
    });

    cancelBtn.addEventListener('click', () => {
      modal.classList.remove('show');
    });

    confirmAdd.addEventListener('click', async () => {
      if (!currentItem) return;
      const qty = parseInt(itemQty.textContent || '0', 10);
      if (qty <= 0) {
        showToast('Item unavailable at the moment');
        return;
      }
      if (!userEmail) {
        window.location.href = 'login.php';
        return;
      }
      try {
        if (!cartId) {
          await loadCart();
        }
        const params = new URLSearchParams();
        params.set('product_id', currentItem.id);
        params.set('quantity', String(qty));
        if (cartId) params.set('cart_id', String(cartId));
        params.set('email', userEmail);

        const resp = await fetch('/PHP/cart_api.php?action=add', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        modal.classList.remove('show');
        if (data.capped) {
          showToast('Quantity adjusted to available stock');
        } else {
          showToast('Item added to cart');
        }
        await loadCart();
      } catch (err) {
        console.error('Failed to add to cart', err);
        showToast('Unable to add item to cart');
      }
    });

    onAuthStateChanged(auth, async (user) => {
      userEmail = user?.email || null;
      updateProfileMenu();
      await Promise.all([loadCart(), loadFavorites()]);
      reorderFavorites();
      const active = document.querySelector('.categories button.active');
      if (active) filterMenu(active.dataset.category);
    });

    // Initial filter
    filterMenu('all');
  </script>
</body>
</html>
