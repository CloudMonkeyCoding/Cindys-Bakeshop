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

  </div>

  <script>
    function goToProduct(id) {
      window.location.href = `product.php?id=${id}`;
    }
  </script>
</body>
</html>

