<?php
require_once __DIR__ . '/../../PHP/db_connect.php';
require_once __DIR__ . '/../../PHP/product_functions.php';

$products = [];
if ($pdo) {
    $products = getProductsByCategory($pdo, 'bread');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cindy's Bread Menu</title>
  <style>
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background: url('../Images/bread/b.jpg') no-repeat center center fixed;
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
