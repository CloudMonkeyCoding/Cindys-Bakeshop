<?php
require_once __DIR__ . '/../../PHP/db_connect.php';
require_once __DIR__ . '/../../PHP/product_functions.php';

$id = $_GET['id'] ?? null;
$product = null;
if ($pdo && $id !== null) {
    $product = getProductById($pdo, $id);
}
if (!$product) {
    http_response_code(404);
    echo "Product not found";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['Name']) ?></title>
</head>
<body>
  <h1><?= htmlspecialchars($product['Name']) ?></h1>
  <?php if (!empty($product['Image_Path'])): ?>
    <img src="../../adminSide/products/uploads/<?= htmlspecialchars($product['Image_Path']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>" style="max-width:300px;">
  <?php endif; ?>
  <p><?= htmlspecialchars($product['Description'] ?? '') ?></p>
  <p>Price: ₱<?= htmlspecialchars($product['Price'] ?? '') ?></p>
  <p>Category: <?= htmlspecialchars($product['Category'] ?? '') ?></p>
  <p>Stock: <?= htmlspecialchars($product['Stock_Quantity'] ?? '') ?></p>
  <div>
    <label for="qty">Qty:</label>
    <input type="number" id="qty" value="1" min="1">
  </div>
  <button onclick="addToCart()">Add to Cart</button>
  <button onclick="buyNow()">Buy Now</button>
  <script src="js/cart.js"></script>
</body>
</html>
