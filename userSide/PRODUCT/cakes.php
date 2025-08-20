<?php
require_once __DIR__ . '/../../PHP/db_connect.php';
require_once __DIR__ . '/../../PHP/product_functions.php';

$products = [];
if ($pdo) {
    $products = getProductsByCategory($pdo, 'cakes');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cakes - Cindy's Bakeshop</title>
  <style>
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background: url('../Images/cakes/cakes.jpg') no-repeat center center fixed;
      background-size: cover;
    }

    .content-wrapper {
      background-color: rgba(255, 255, 255, 0.85);
      margin: 20px auto;
      padding: 20px;
      border-radius: 15px;
      max-width: 1300px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
    }

    .top-bar a {
      font-weight: bold;
      color: red;
      text-decoration: none;
      font-size: 18px;
    }

    .top-bar a:hover {
      text-decoration: underline;
    }

    .search-box input {
      padding: 8px 15px;
      width: 220px;
      border-radius: 30px;
      border: 2px solid black;
      outline: none;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 25px;
      padding: 20px 40px;
    }

    .product-card {
      background: white;
      border: 2px solid red;
      border-radius: 15px;
      padding: 10px;
      text-align: center;
      box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s;
      cursor: pointer;
    }

    .product-card:hover {
      transform: scale(1.03);
    }

    .product-card img {
      max-width: 160px;
      height: auto;
      margin-bottom: 10px;
    }

    .product-name {
      font-weight: bold;
      font-size: 14px;
      margin-bottom: 5px;
    }

    .price-stock {
      font-size: 13px;
      margin-bottom: 10px;
    }

    .buttons {
      display: flex;
      justify-content: space-between;
      gap: 5px;
    }

    .buttons button {
      flex: 1;
      padding: 6px 10px;
      font-size: 12px;
      font-weight: bold;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }

    .add-btn {
      background: #ffff66;
    }

    .buy-btn {
      background: #b6ff00;
    }

    .add-btn:hover,
    .buy-btn:hover {
      background: black;
      color: white;
    }
  </style>
</head>
<body>
  <div class="content-wrapper">
    <div class="top-bar">
      <a href="MENU.html">&larr; Back to Menu</a>
      <div class="search-box">
        <input id="searchInput" type="text" placeholder="Search cakes...">
      </div>
    </div>

    <div class="products-grid">
      <?php foreach ($products as $product): ?>
        <div class="product-card" onclick="goToProduct(<?= htmlspecialchars($product['Product_ID']) ?>)">
          <?php if (!empty($product['Image_Path'])): ?>
            <img src="../../adminSide/products/uploads/<?= htmlspecialchars($product['Image_Path']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>">
          <?php endif; ?>
          <div class="product-name"><?= htmlspecialchars($product['Name']) ?></div>
          <div class="price-stock">Price: ₱<?= htmlspecialchars(number_format((float)$product['Price'], 2)) ?><br>Stock: <?= htmlspecialchars($product['Stock_Quantity']) ?></div>
          <div class="buttons">
            <button class="add-btn">Add to Cart</button>
            <button class="buy-btn">Buy Now</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <p id="no-results" style="text-align: center; font-weight: bold; font-size: 18px; display: none;">No products found.</p>
  </div>

  <script>
    // Search Functionality
    const searchInput = document.getElementById('searchInput');
    const productCards = document.querySelectorAll('.product-card');
    const noResults = document.getElementById('no-results');

    searchInput.addEventListener('input', () => {
      const searchTerm = searchInput.value.toLowerCase();
      let matches = 0;

      productCards.forEach(card => {
        const productName = card.querySelector('.product-name').textContent.toLowerCase();
        const isMatch = productName.includes(searchTerm);
        card.style.display = isMatch ? 'block' : 'none';
        if (isMatch) matches++;
      });

      noResults.style.display = matches === 0 ? 'block' : 'none';
    });

    function goToProduct(id) {
      window.location.href = `product.php?id=${id}`;
    }
  </script>
</body>
</html>

