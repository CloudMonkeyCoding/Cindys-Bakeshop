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

$category = $product['Category'] ?? '';
$categoryLower = strtolower($category);
$bodyClass = 'product-detail-page ' . $categoryLower;

$price = isset($product['Price']) ? number_format((float)$product['Price'], 2) : '0.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($product['Name']) ?></title>

  <link rel="stylesheet" href="../styles.css" />
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
  <div class="background-blur"></div>
  <?php include __DIR__ . '/../topbar.php'; ?>
  <div class="wrapper">
    <div class="container">
      <div class="image-section">
        <div class="circle-bg"></div>
        <?php if (!empty($product['Image_Path'])): ?>
          <img src="../../adminSide/products/uploads/<?= htmlspecialchars($product['Image_Path']) ?>" alt="<?= htmlspecialchars($product['Name']) ?>" />
        <?php endif; ?>
      </div>

      <div class="details-section">
        <div class="breadcrumb">
          Categories > <strong><?= htmlspecialchars($category) ?></strong>
        </div>
        <div class="price-row">
          <div class="price">Php <?= htmlspecialchars($price) ?></div>
          <span class="favorite-icon" onclick="toggleFavorite(this)">❤️</span>
        </div>
        <div class="stock" id="stockDisplay">Stock: <?= htmlspecialchars($product['Stock_Quantity'] ?? '') ?></div>
        <h2><?= htmlspecialchars($product['Name']) ?></h2>
        <p><?= htmlspecialchars($product['Description'] ?? '') ?></p>

        <div class="quantity-controls">
          <span>Qty:</span>
          <button onclick="changeQty(-1)">−</button>
          <input type="text" id="qty" value="1" readonly>
          <button onclick="changeQty(1)">+</button>
        </div>

        <div class="buttons">
          <button class="add-to-cart" onclick="addToCart()">Add to Cart</button>
          <button class="buy-now" onclick="buyNow()">Buy Now</button>
          <button class="share-now" onclick="shareNow()">🔗 Share</button>
        </div>
      </div>
    </div>
  </div>

  <script type="module" src="../firebase-init.js"></script>
  <script src="js/cart.js"></script>
  <script>
    let maxStock = <?= (int)($product['Stock_Quantity'] ?? 0); ?>;
    window.maxStock = maxStock;

    async function updateMaxStockFromCart() {
      const productId = <?= (int)$id ?>;
      try {
        let email = null;
        try {
          const auth = window.getAuth ? window.getAuth() : null;
          email = auth && auth.currentUser ? auth.currentUser.email : null;
        } catch (e) {
          console.error("Auth unavailable", e);
        }

        const listUrl = email
          ? `/PHP/cart_api.php?action=list&email=${encodeURIComponent(email)}`
          : `/PHP/cart_api.php?action=list`;
        const resp = await fetch(listUrl);
        const text = await resp.text();
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error("Invalid cart list response", text);
          return;
        }
        if (data.items) {
          const existing = data.items.find(
            (item) => String(item.Product_ID) === String(productId)
          );
          if (existing) {
            const existingQty = parseInt(existing.Quantity, 10) || 0;
            maxStock = Math.max(0, maxStock - existingQty);
            window.maxStock = maxStock;
          }
        }
      } catch (err) {
        console.error("Failed to fetch cart", err);
      }

      const stockEl = document.getElementById('stockDisplay');
      if (stockEl) stockEl.textContent = `Stock: ${maxStock}`;

      const qtyEl = document.getElementById('qty');
      if (qtyEl) {
        if (maxStock === 0) qtyEl.value = 0;
        else if (parseInt(qtyEl.value, 10) > maxStock) qtyEl.value = maxStock;
      }

      if (maxStock === 0) {
        document.querySelector('.add-to-cart').disabled = true;
        document.querySelector('.buy-now').disabled = true;
      }
    }

    updateMaxStockFromCart();

    function changeQty(delta) {
      const qty = document.getElementById('qty');
      let current = parseInt(qty.value);
      current = isNaN(current) ? 1 : current;
      current += delta;
      if (current < 1) current = 1;
      if (current > maxStock) {
        current = maxStock;
        alert(`Only ${maxStock} left in stock.`);
      }
      qty.value = current;
    }

    function toggleFavorite(el) {
      el.textContent = el.textContent === '❤️' ? '💖' : '❤️';
      alert(el.textContent === '💖' ? 'Added to favorites!' : 'Removed from favorites.');
    }

    function shareNow() {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(window.location.href)
          .then(() => alert('Product link copied to clipboard!'))
          .catch(() => alert('Failed to copy link.'));
      } else {
        alert('Clipboard not supported.');
      }
    }
  </script>
</body>
</html>

