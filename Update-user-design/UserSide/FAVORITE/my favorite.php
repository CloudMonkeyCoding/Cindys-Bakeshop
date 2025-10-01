<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Favorite Treats - Cindy's Bakeshop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css" />
  <style>
    body.favorites-view {
      display: flex;
      flex-direction: column;
    }

    .favorites-hero {
      background: linear-gradient(135deg, rgba(139, 69, 19, 0.92), rgba(255, 159, 128, 0.85));
      border-radius: 32px;
      padding: clamp(2.5rem, 5vw, 4rem);
      color: #fff;
      margin-bottom: 3rem;
      box-shadow: 0 30px 60px rgba(139, 69, 19, 0.25);
    }

    .favorites-hero h1 {
      font-size: clamp(2rem, 4vw, 2.8rem);
      font-weight: 700;
      margin-bottom: 1rem;
    }

    .favorites-hero p {
      font-size: 1.05rem;
      max-width: 520px;
      opacity: 0.85;
    }

    .favorites-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 2rem;
    }

    .favorite-card {
      background: rgba(255, 255, 255, 0.92);
      border-radius: 28px;
      padding: 1.6rem;
      display: flex;
      flex-direction: column;
      gap: 1.1rem;
      box-shadow: var(--shadow-soft);
      border: 1px solid rgba(139, 69, 19, 0.12);
    }

    .favorite-card img {
      width: 100%;
      height: 170px;
      object-fit: cover;
      border-radius: 22px;
      box-shadow: 0 15px 30px rgba(139, 69, 19, 0.18);
    }

    .favorite-card h3 {
      font-size: 1.2rem;
      font-weight: 700;
      color: var(--primary-brown);
    }

    .card-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }

    .card-actions a,
    .card-actions button {
      padding: 0.75rem 1.3rem;
      border-radius: var(--radius-pill);
      font-weight: 600;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      text-decoration: none;
    }

    .card-actions a {
      background: rgba(139, 69, 19, 0.12);
      color: var(--primary-brown);
    }

    .card-actions button.primary {
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
    }

    .card-actions button.danger {
      background: rgba(200, 40, 60, 0.12);
      color: #c8283c;
    }

    .empty-state {
      padding: 3rem;
      text-align: center;
      border-radius: 28px;
      background: rgba(255, 255, 255, 0.92);
      border: 1px dashed rgba(139, 69, 19, 0.2);
      color: var(--text-muted);
      box-shadow: var(--shadow-soft);
    }

    .toast {
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%);
      background: linear-gradient(135deg, var(--primary-brown), var(--primary-brown-dark));
      color: #fff;
      padding: 0.85rem 1.8rem;
      border-radius: var(--radius-pill);
      box-shadow: var(--shadow-strong);
      font-weight: 600;
      opacity: 0;
      pointer-events: none;
      transition: all 0.4s ease;
      z-index: 3000;
    }

    .toast.show {
      opacity: 1;
      transform: translate(-50%, -10px);
    }
  </style>
</head>
<body class="favorites-view">
  <?php include __DIR__ . '/../topbar.php'; ?>

  <main class="page-container">
    <section class="favorites-hero">
      <h1>Your handpicked bakery favourites</h1>
      <p>Keep these delights close or send them straight to your cart. We're ready when you are.</p>
    </section>

    <div class="favorites-grid" id="favoritesGrid"></div>
    <div class="empty-state" id="noFavorites" hidden>
      No favourites saved yet. Explore the menu and tap the heart to build your list!
    </div>
  </main>

  <div class="toast" id="favoritesToast" role="status" aria-live="polite"></div>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";

    const grid = document.getElementById('favoritesGrid');
    const noFav = document.getElementById('noFavorites');
    const toast = document.getElementById('favoritesToast');
    const auth = getAuth();
    let userEmail = null;

    function showToast(message) {
      toast.textContent = message;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2200);
    }

    function resolveImagePath(imagePath) {
      if (!imagePath) {
        return '../../../Images/logo.png';
      }
      if (imagePath.startsWith('http')) {
        return imagePath;
      }
      return '../../../adminSide/products/uploads/' + imagePath;
    }

    function renderFavorites(list) {
      grid.innerHTML = '';
      if (!list || list.length === 0) {
        noFav.hidden = false;
        return;
      }
      noFav.hidden = true;

      list.forEach(item => {
        const card = document.createElement('article');
        card.className = 'favorite-card';
        card.innerHTML = `
          <img src="${resolveImagePath(item.Image_Path)}" alt="${item.Name}">
          <h3>${item.Name}</h3>
          <div class="card-actions">
            <a href="../PRODUCT/product.php?id=${item.Product_ID}">View details</a>
            <button type="button" class="primary" data-action="add" data-id="${item.Product_ID}">Add to cart</button>
            <button type="button" class="danger" data-action="remove" data-favorite="${item.Favorite_ID}">Remove</button>
          </div>
        `;
        grid.appendChild(card);
      });
    }

    async function loadFavorites(email) {
      const res = await fetch(`../../../PHP/favorite_api.php?action=list&email=${encodeURIComponent(email)}`);
      const data = await res.json();
      if (data.error) {
        throw new Error(data.error);
      }
      renderFavorites(data);
    }

    async function removeFavorite(favoriteId) {
      const res = await fetch('../../../PHP/favorite_api.php?action=remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ favorite_id: favoriteId })
      });
      const data = await res.json();
      if (data.error) {
        throw new Error(data.error);
      }
    }

    async function addToCart(productId) {
      if (!userEmail) {
        showToast('Sign in to add treats to your cart.');
        return;
      }
      const res = await fetch('../../../PHP/cart_api.php?action=add', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ email: userEmail, product_id: productId, quantity: 1 })
      });
      const data = await res.json();
      if (data.error) {
        throw new Error(data.error);
      }
    }

    grid.addEventListener('click', async (event) => {
      const target = event.target;
      if (!(target instanceof HTMLElement)) return;
      const action = target.dataset.action;
      if (!action) return;
      try {
        if (action === 'remove') {
          await removeFavorite(target.dataset.favorite);
          await loadFavorites(userEmail);
          showToast('Removed from favourites.');
        } else if (action === 'add') {
          await addToCart(target.dataset.id);
          showToast('Added to cart!');
        }
      } catch (error) {
        showToast(error.message || 'Something went wrong.');
      }
    });

    onAuthStateChanged(auth, user => {
      if (user) {
        userEmail = user.email;
        loadFavorites(userEmail).catch(() => {
          grid.innerHTML = '';
          noFav.hidden = false;
        });
      } else {
        userEmail = null;
        grid.innerHTML = '';
        noFav.hidden = false;
      }
    });
  </script>
</body>
</html>
