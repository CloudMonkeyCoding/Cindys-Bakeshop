<?php
require_once __DIR__ . '/../../PHP/db_connect.php';
require_once __DIR__ . '/../../PHP/product_functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === null || $id === false) {
    http_response_code(400);
    echo 'Missing or invalid product ID.';
    exit;
}

if (!$pdo) {
    http_response_code(500);
    echo 'Database connection not available.';
    exit;
}

try {
    $product = getProductById($pdo, $id);
    if (!$product) {
        http_response_code(404);
        echo "Product with ID {$id} not found.";
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Error fetching product: ' . htmlspecialchars($e->getMessage());
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
