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
  <link rel="stylesheet" href="../styles.css" />
</head>
  <body class="product-category cakes-page">
    <div class="content-wrapper">
      <div class="top-bar">
        <a href="MENU.php" class="back-link">&larr; Back to Menu</a>
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

<p id="no-results" style="text-align: center; font-weight: bold; font-size: 18px; display: none;">
  No products found.
</p>

<script>
  const searchInput = document.getElementById('searchInput');
  const productCards = Array.from(document.querySelectorAll('.product-card'));
  const noResults = document.getElementById('no-results');

  function applyFilter() {
    const searchTerm = (searchInput.value || '').toLowerCase().trim();
    let matches = 0;

    productCards.forEach(card => {
      const nameEl = card.querySelector('.product-name');
      const productName = (nameEl ? nameEl.textContent : '').toLowerCase();
      const isMatch = !searchTerm || productName.includes(searchTerm);
      // Use '' (empty string) to restore default display so the grid stays intact
      card.style.display = isMatch ? '' : 'none';
      if (isMatch) matches++;
    });

    noResults.style.display = matches === 0 ? '' : 'none';
  }

  searchInput.addEventListener('input', applyFilter);
  // Run once on load in case list is empty or pre-filtered
  applyFilter();

  function goToProduct(id) {
    window.location.href = `product.php?id=${encodeURIComponent(id)}`;
  }
</script>

</body>
</html>

