<?php
require_once __DIR__ . '/../../PHP/db_connect.php';
require_once __DIR__ . '/../../PHP/product_functions.php';

$products = [];
$category = 'pastry';
if ($pdo) {
    $products = getProductsByCategory($pdo, $category);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pastry - Cindy's Bakeshop</title>
  <link rel="stylesheet" href="../styles.css" />
  <link rel="stylesheet" href="../styles.css" />
</head>
<body class="product-category pastry-page">
  <div class="content-wrapper">
    <?php include __DIR__ . '/../topbar.php'; ?>

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

</div> <!-- end .content-wrapper -->

<script>
    const searchInput = document.getElementById('searchInput');
    const productCards = Array.from(document.querySelectorAll('.product-card'));
    const noResults = document.getElementById('no-results');

    function applyFilter() {
      const term = searchInput ? (searchInput.value || '').toLowerCase().trim() : '';
      let matches = 0;

      productCards.forEach(card => {
        const name = (card.querySelector('.product-name')?.textContent || '').toLowerCase();
        const isMatch = !term || name.includes(term);
        // Use '' to restore the element's natural display (keeps CSS grid intact)
        card.style.display = isMatch ? '' : 'none';
        if (isMatch) matches++;
      });

      // Show "No products found" only when there are zero matches
      noResults.style.display = matches === 0 ? '' : 'none';
    }

    if (searchInput) {
      searchInput.addEventListener('input', applyFilter);
    }
    // Run once on load
    applyFilter();

    function goToProduct(id) {
      window.location.href = `product.php?id=${encodeURIComponent(id)}`;
    }
  </script>

</body>
</html>

