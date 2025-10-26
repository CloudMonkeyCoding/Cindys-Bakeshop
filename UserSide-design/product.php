<?php
require_once __DIR__ . '/../PHP/db_connect.php';
require_once __DIR__ . '/../PHP/product_functions.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$error = '';

if (!$productId) {
    $error = 'Missing or invalid product identifier.';
} elseif (!$pdo) {
    $error = 'Unable to connect to the database.';
} else {
    try {
        $product = getProductById($pdo, $productId);
        if (!$product) {
            $error = 'The requested product could not be found.';
        }
    } catch (Throwable $e) {
        $error = 'Unable to load product information at this time.';
    }
}

function product_image_path(?array $product): string
{
    $path = $product['Image_Path'] ?? '';
    if ($path) {
        return '/adminSide/products/uploads/' . ltrim($path, '/');
    }
    $name = $product['Name'] ?? 'Product';
    return 'https://via.placeholder.com/520x380?text=' . rawurlencode($name);
}

$displayName = $product['Name'] ?? 'Product';
$priceValue = (float)($product['Price'] ?? 0);
$priceDisplay = number_format($priceValue, 2);
$stockQuantity = (int)($product['Stock_Quantity'] ?? 0);
$category = $product['Category'] ?? '';
$description = trim((string)($product['Description'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($displayName) ?> • Cindy’s Bakeshop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <style>
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Poppins",sans-serif; }
    body { background:#fdfaf6; color:#3d2c1d; min-height:100vh; display:flex; flex-direction:column; }

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
    .product-wrapper { max-width:1100px; margin:2.5rem auto; padding:0 1.5rem; }
    .product-card { background:#fff; border-radius:18px; box-shadow:0 12px 28px rgba(0,0,0,0.08); display:flex; gap:2.5rem; padding:2.5rem; align-items:stretch; }
    .product-gallery { flex:1; display:flex; align-items:center; justify-content:center; background:linear-gradient(140deg,#fff3e5,#ffe8d3); border-radius:16px; padding:1.5rem; }
    .product-gallery img { width:100%; max-width:420px; border-radius:16px; box-shadow:0 10px 25px rgba(166,110,68,0.18); object-fit:cover; }
    .product-info { flex:1; display:flex; flex-direction:column; gap:1.1rem; }
    .breadcrumb { font-size:0.9rem; color:#a66e44; letter-spacing:0.04em; text-transform:uppercase; }
    .product-info h1 { font-size:2.1rem; color:#5b3a1e; }
    .product-desc { color:#6f5844; line-height:1.6; }
    .price-tag { font-size:1.8rem; font-weight:700; color:#c25d21; }
    .stock-label { font-size:0.95rem; font-weight:600; color:#2e7d32; }
    .stock-label.out { color:#c0392b; }

    .quantity-row { display:flex; align-items:center; gap:0.8rem; margin-top:0.5rem; }
    .quantity-row button { width:40px; height:40px; border-radius:12px; border:none; background:#a66e44; color:#fff; font-size:1.2rem; cursor:pointer; transition:background 0.2s; }
    .quantity-row button:disabled { background:#d6c5b6; cursor:not-allowed; }
    .quantity-row input { width:70px; text-align:center; padding:0.5rem; border-radius:10px; border:1px solid #d6c5b6; font-size:1rem; }

    .quick-actions { display:flex; gap:0.8rem; flex-wrap:wrap; }
    .quick-actions button { padding:0.65rem 1.2rem; border-radius:999px; border:1px solid #d6c5b6; background:#fff; color:#5b3a1e; cursor:pointer; font-weight:600; }
    .quick-actions button.active { background:#ffe6df; border-color:#ffa47a; color:#c25d21; }

    .cta-buttons { display:flex; gap:1rem; flex-wrap:wrap; margin-top:0.5rem; }
    .cta-buttons button { flex:1 1 160px; padding:0.9rem 1.2rem; border:none; border-radius:12px; font-weight:700; cursor:pointer; font-size:1rem; transition:transform 0.2s, box-shadow 0.2s; }
    .cta-buttons .primary { background:#c25d21; color:#fff; box-shadow:0 8px 16px rgba(194,93,33,0.25); }
    .cta-buttons .secondary { background:#fff3e5; color:#c25d21; box-shadow:0 8px 16px rgba(194,93,33,0.12); }
    .cta-buttons button:disabled { background:#e3d8d0; color:#8d7b6f; box-shadow:none; cursor:not-allowed; }
    .cta-buttons button:not(:disabled):hover { transform:translateY(-2px); box-shadow:0 10px 20px rgba(194,93,33,0.25); }

    .meta-row { display:flex; gap:1rem; align-items:center; font-size:0.9rem; color:#907260; }

    .toast { position:fixed; bottom:32px; left:50%; transform:translateX(-50%); background:#c25d21; color:#fff; padding:0.85rem 1.6rem; border-radius:28px; opacity:0; pointer-events:none; transition:opacity 0.4s ease, transform 0.4s ease; z-index:3000; font-weight:600; }
    .toast.show { opacity:1; transform:translate(-50%, -10px); }

    .error-state { max-width:520px; margin:6rem auto; text-align:center; background:#fff; padding:2.5rem 2rem; border-radius:16px; box-shadow:0 10px 24px rgba(0,0,0,0.08); }
    .error-state h1 { font-size:2rem; color:#c0392b; margin-bottom:0.8rem; }
    .error-state p { color:#6f5844; margin-bottom:1.4rem; }
    .error-state a { display:inline-block; padding:0.75rem 1.6rem; border-radius:999px; background:#c25d21; color:#fff; text-decoration:none; font-weight:600; }
    .error-state a:hover { background:#a74d16; }

    @media (max-width:900px) {
      .product-card { flex-direction:column; padding:2rem; gap:2rem; }
      .product-gallery { border-radius:14px; }
      .product-info h1 { font-size:1.8rem; }
      .cta-buttons { flex-direction:column; }
      .cta-buttons button { width:100%; }
    }
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

  <main>
    <?php if ($product): ?>
    <div class="product-wrapper">
      <div class="product-card" data-product-id="<?= (int)$product['Product_ID'] ?>">
        <div class="product-gallery">
          <img src="<?= htmlspecialchars(product_image_path($product)) ?>" alt="<?= htmlspecialchars($displayName) ?>" />
        </div>
        <div class="product-info">
          <span class="breadcrumb"><?= htmlspecialchars($category ?: 'Freshly baked') ?></span>
          <h1><?= htmlspecialchars($displayName) ?></h1>
          <div class="meta-row">
            <span class="price-tag">₱<?= htmlspecialchars($priceDisplay) ?></span>
            <span id="stockLabel" class="stock-label<?= $stockQuantity <= 0 ? ' out' : '' ?>"><?= $stockQuantity > 0 ? 'In stock (' . $stockQuantity . ' available)' : 'Currently out of stock' ?></span>
          </div>
          <p class="product-desc"><?= htmlspecialchars($description ?: 'Baked fresh daily with premium ingredients to delight every bite!') ?></p>

          <div class="quantity-row">
            <button id="decreaseQty" aria-label="Decrease quantity">−</button>
            <input id="quantityInput" type="number" min="1" value="<?= $stockQuantity > 0 ? 1 : 0 ?>" aria-label="Quantity" />
            <button id="increaseQty" aria-label="Increase quantity">+</button>
          </div>

          <div class="quick-actions">
            <button id="favoriteBtn" type="button">♡ Add to Favorites</button>
            <button id="shareBtn" type="button">Share Link</button>
          </div>

          <div class="cta-buttons">
            <button id="addToCartBtn" class="secondary" type="button"<?= $stockQuantity <= 0 ? ' disabled' : '' ?>>Add to Cart</button>
            <button id="buyNowBtn" class="primary" type="button"<?= $stockQuantity <= 0 ? ' disabled' : '' ?>>Buy Now</button>
          </div>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="error-state">
      <h1>Product unavailable</h1>
      <p><?= htmlspecialchars($error ?: 'The product you are looking for could not be located.') ?></p>
      <a href="menu.php">← Back to Menu</a>
    </div>
    <?php endif; ?>
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
    let cartId = null;
    let favoriteId = null;
    const productId = <?= $product ? (int)$product['Product_ID'] : 'null' ?>;
    const baseStock = <?= $product ? (int)$stockQuantity : 0 ?>;
    const price = <?= json_encode($priceValue) ?>;

    const quantityInput = document.getElementById('quantityInput');
    const decreaseQty = document.getElementById('decreaseQty');
    const increaseQty = document.getElementById('increaseQty');
    const addToCartBtn = document.getElementById('addToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');
    const favoriteBtn = document.getElementById('favoriteBtn');
    const shareBtn = document.getElementById('shareBtn');
    const stockLabel = document.getElementById('stockLabel');
    const toast = document.getElementById('toast');

    let availableStock = baseStock;

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
      setTimeout(() => toast.classList.remove('show'), 2600);
    }

    function updateQuantityButtons() {
      if (!quantityInput) return;
      const current = parseInt(quantityInput.value, 10) || 0;
      decreaseQty.disabled = current <= 1;
      increaseQty.disabled = current >= availableStock;
    }

    function updateAvailability(existingInCart = 0) {
      if (!stockLabel || !addToCartBtn || !buyNowBtn || !quantityInput) return;
      if (availableStock > 0) {
        const label = existingInCart > 0
          ? `In stock (${availableStock} available • ${existingInCart} in cart)`
          : `In stock (${availableStock} available)`;
        stockLabel.textContent = label;
        stockLabel.classList.remove('out');
        if (parseInt(quantityInput.value, 10) <= 0) {
          quantityInput.value = '1';
        }
        addToCartBtn.disabled = false;
        buyNowBtn.disabled = false;
      } else {
        stockLabel.textContent = existingInCart > 0
          ? `Currently unavailable (you have ${existingInCart} in cart)`
          : 'Currently out of stock';
        stockLabel.classList.add('out');
        quantityInput.value = '0';
        addToCartBtn.disabled = true;
        buyNowBtn.disabled = true;
      }
      updateQuantityButtons();
    }

    function setFavoriteState(isFavorite) {
      if (!favoriteBtn) return;
      favoriteBtn.classList.toggle('active', isFavorite);
      favoriteBtn.textContent = isFavorite ? '♥ Favorited' : '♡ Add to Favorites';
    }

    async function loadFavorites() {
      if (!userEmail || !productId) {
        favoriteId = null;
        setFavoriteState(false);
        return;
      }
      try {
        const resp = await fetch(`/PHP/favorite_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        const match = Array.isArray(data) ? data.find((item) => String(item.Product_ID) === String(productId)) : null;
        favoriteId = match ? match.Favorite_ID : null;
        setFavoriteState(Boolean(favoriteId));
      } catch (err) {
        console.error('Failed to load favorites', err);
        favoriteId = null;
        setFavoriteState(false);
      }
    }

    async function loadCart() {
      if (!userEmail || !productId) {
        availableStock = baseStock;
        updateAvailability();
        return;
      }
      try {
        const resp = await fetch(`/PHP/cart_api.php?action=list&email=${encodeURIComponent(userEmail)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        cartId = data.cart_id || null;
        const items = Array.isArray(data.items) ? data.items : [];
        const existing = items.find((item) => String(item.Product_ID) === String(productId));
        const existingQty = existing ? parseInt(existing.Quantity, 10) || 0 : 0;
        availableStock = Math.max(baseStock - existingQty, 0);
        updateAvailability(existingQty);
      } catch (err) {
        console.error('Failed to load cart', err);
        availableStock = baseStock;
        updateAvailability();
      }
    }

    function clampQuantity() {
      if (!quantityInput) return 1;
      let value = parseInt(quantityInput.value, 10) || 1;
      if (value < 1) value = 1;
      if (availableStock > 0 && value > availableStock) value = availableStock;
      quantityInput.value = String(value);
      updateQuantityButtons();
      return value;
    }

    async function toggleFavorite() {
      if (!productId) return;
      if (!userEmail) {
        window.location.href = 'login.php';
        return;
      }
      try {
        if (favoriteId) {
          const resp = await fetch('/PHP/favorite_api.php?action=remove', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ favorite_id: favoriteId })
          });
          const text = await resp.text();
          if (!resp.ok) throw new Error(text);
          const data = JSON.parse(text);
          if (data.deleted) {
            favoriteId = null;
            setFavoriteState(false);
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
            favoriteId = data.favorite_id;
            setFavoriteState(true);
            showToast('Added to favorites');
          }
        }
      } catch (err) {
        console.error('Failed to update favorite', err);
        showToast('Unable to update favorites right now');
      }
    }

    async function addToCart(redirectToCheckout = false) {
      if (!productId) return;
      if (!userEmail) {
        window.location.href = 'login.php';
        return;
      }
      const quantity = clampQuantity();
      if (quantity <= 0 || availableStock <= 0) {
        showToast('This item is currently unavailable');
        return;
      }
      try {
        if (!cartId) {
          await loadCart();
        }
        const params = new URLSearchParams();
        params.set('product_id', productId);
        params.set('quantity', String(quantity));
        params.set('email', userEmail);
        if (cartId) params.set('cart_id', String(cartId));

        const resp = await fetch('/PHP/cart_api.php?action=add', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        if (data.capped) {
          showToast('Quantity adjusted to available stock');
        } else {
          showToast('Item added to cart');
        }
        await loadCart();
        if (redirectToCheckout) {
          window.location.href = 'checkout.php';
        }
      } catch (err) {
        console.error('Failed to add to cart', err);
        showToast('Unable to add item to cart');
      }
    }

    function shareProduct() {
      const url = window.location.href;
      if (navigator.share) {
        navigator.share({ title: document.title, url }).catch(() => {});
        return;
      }
      if (navigator.clipboard) {
        navigator.clipboard.writeText(url)
          .then(() => showToast('Product link copied to clipboard'))
          .catch(() => showToast('Unable to copy link'));
      } else {
        showToast('Sharing not supported on this browser');
      }
    }

    decreaseQty?.addEventListener('click', () => {
      if (!quantityInput) return;
      const current = parseInt(quantityInput.value, 10) || 1;
      if (current > 1) {
        quantityInput.value = String(current - 1);
        updateQuantityButtons();
      }
    });

    increaseQty?.addEventListener('click', () => {
      if (!quantityInput) return;
      const current = parseInt(quantityInput.value, 10) || 1;
      if (current < availableStock) {
        quantityInput.value = String(current + 1);
        updateQuantityButtons();
      }
    });

    quantityInput?.addEventListener('input', clampQuantity);
    favoriteBtn?.addEventListener('click', toggleFavorite);
    addToCartBtn?.addEventListener('click', () => addToCart(false));
    buyNowBtn?.addEventListener('click', () => addToCart(true));
    shareBtn?.addEventListener('click', shareProduct);

    onAuthStateChanged(auth, async (user) => {
      userEmail = user?.email || null;
      updateProfileMenu();
      if (!productId) return;
      await Promise.all([loadCart(), loadFavorites()]);
    });

    clampQuantity();
    updateAvailability();
  </script>
</body>
</html>
