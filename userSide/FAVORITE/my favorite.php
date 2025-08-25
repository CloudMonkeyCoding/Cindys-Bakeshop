<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cindy's Favorites</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles.css" />
</head>
<body class="favorite-page">
  <?php include __DIR__ . '/../topbar.php'; ?>

  <section class="favorites-section">
    <div class="favorites-title">Your Favorite Products</div>
    <div class="favorites-grid" id="favoritesGrid"></div>
    <div class="no-favorites" id="noFavorites" style="display:none;">No favorite products found.</div>
  </section>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";

    const grid = document.getElementById('favoritesGrid');
    const noFav = document.getElementById('noFavorites');

    const auth = getAuth();
    onAuthStateChanged(auth, user => {
      if (user) {
        fetch(`../../PHP/favorite_api.php?action=list&email=${encodeURIComponent(user.email)}`)
          .then(res => res.json())
          .then(favorites => {
            if (!favorites || favorites.length === 0) {
              noFav.style.display = 'block';
              return;
            }

            favorites.forEach(product => {
              const div = document.createElement('div');
              div.className = 'favorite-item';
              div.innerHTML = `
                <a href="../PRODUCT/product.php?id=${product.Product_ID}">
                  <img src="${product.Image_Path ? '../../adminSide/products/uploads/' + product.Image_Path : ''}" alt="${product.Name}">
                  <div class="product-name">${product.Name}</div>
                </a>
                <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
              `;
              grid.appendChild(div);
            });
          })
          .catch(() => {
            noFav.style.display = 'block';
          });
      } else {
        noFav.style.display = 'block';
      }
    });
  </script>

</body>
</html>