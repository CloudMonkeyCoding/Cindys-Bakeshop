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

switch ($categoryLower) {
    case 'bread':
        $bgImage = 'breads/bread/b.jpg';
        break;
    case 'pastry':
        $bgImage = 'pastry/images/p.jpg';
        break;
    case 'cakes':
        $bgImage = 'cakes/cakes/c.jpg';
        break;
    default:
        $bgImage = '';
}

$price = isset($product['Price']) ? number_format((float)$product['Price'], 2) : '0.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($product['Name']) ?></title>

  <style>
    * {
      box-sizing: border-box;
    }

    html, body {
      margin: 0;
      height: 100%;
      overflow: hidden;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #000;
    }

    .background-blur {
      position: fixed;
      top: 0;
      left: 0;
      height: 100%;
      width: 100%;
      background: url('<?= htmlspecialchars($bgImage) ?>') no-repeat center center fixed;
      background-size: cover;
      filter: blur(5px) brightness(0.85);
      z-index: -1;
    }

    .wrapper {
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }


    .container {
      background: #fff;
      border-radius: 30px;
      box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
      display: flex;
      overflow: hidden;
      padding: 2rem;
      margin-top: 20px;
      width: 100%;
      max-width: 1200px;
      height: 500px;
    }

    .image-section {
      flex: 1;
      min-width: 300px;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .image-section img {
      width: 320px;
      z-index: 2;
      position: relative;
      transition: transform 0.4s ease;
    }

    .image-section img:hover {
      transform: scale(1.08);
    }

    .details-section {
      flex: 1;
      padding: 2rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .breadcrumb {
      font-size: 0.9rem;
      color: #aaa;
      margin-bottom: 1rem;
    }

    .price-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 0.5rem 0;
    }

    .price {
      font-size: 1.5rem;
      font-weight: 800;
      color: #111;
    }

    .favorite-icon {
      font-size: 24px;
      cursor: pointer;
      transition: transform 0.2s ease;
    }

    .favorite-icon:hover {
      transform: scale(1.2);
      color: #f66;
    }

    .stock {
      font-size: 1.1rem;
      margin-bottom: 1rem;
      color: #333;
    }

    .details-section h2 {
      font-size: 2.2rem;
      font-weight: 800;
      margin-bottom: 0.8rem;
      color: #2b2b2b;
      text-shadow: 1px 1px 1px #eee;
    }

    .details-section p {
      font-size: 1.05rem;
      color: #444;
      line-height: 1.7;
      margin-bottom: 1.5rem;
      max-width: 520px;
    }

    .quantity-controls {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      margin-bottom: 1.5rem;
    }

    .quantity-controls button {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      border: 2px solid #888;
      background: white;
      font-size: 18px;
      font-weight: bold;
      cursor: pointer;
      transition: background 0.3s, transform 0.2s;
    }

    .quantity-controls button:hover {
      background: #fdd;
      transform: scale(1.1);
    }

    .quantity-controls input {
      width: 50px;
      text-align: center;
      font-weight: bold;
      font-size: 16px;
      border: 2px solid #ccc;
      border-radius: 8px;
    }

    .buttons {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      align-items: center;
    }

    .buttons button {
      padding: 12px 22px;
      border: none;
      border-radius: 30px;
      font-weight: bold;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-to-cart {
      background-color: #ffff66;
      color: #000;
    }

    .buy-now {
      background-color: #b6ff00;
      color: #000;
    }

    .share-now {
      background-color: #ccf2ff;
      color: #0077b6;
    }

    .add-to-cart:hover,
    .buy-now:hover,
    .share-now:hover {
      background: black;
      color: white;
    }
  </style>
</head>
<body>
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

