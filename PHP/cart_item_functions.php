<?php
// 1) Add an item to cart
function addCartItem($pdo, $cartId, $productId, $quantity) {
    $stmt = $pdo->prepare("
        INSERT INTO Cart_Item (Cart_ID, Product_ID, Quantity)
        VALUES (:cart_id, :product_id, :quantity)
    ");
    $stmt->execute([
        ':cart_id' => $cartId,
        ':product_id' => $productId,
        ':quantity' => $quantity
    ]);
    return $pdo->lastInsertId();
}

// 2) Get all items in a cart
function getCartItemsByCartId($pdo, $cartId) {
    $stmt = $pdo->prepare("
        SELECT * FROM Cart_Item WHERE Cart_ID = :cart_id
    ");
    $stmt->execute([':cart_id' => $cartId]);
    return $stmt->fetchAll();
}

// 3) Get all items for a product
function getCartItemsByProductId($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT * FROM Cart_Item WHERE Product_ID = :product_id
    ");
    $stmt->execute([':product_id' => $productId]);
    return $stmt->fetchAll();
}

// 4) Get a single cart item by ID
function getCartItemById($pdo, $cartItemId) {
    $stmt = $pdo->prepare("
        SELECT * FROM Cart_Item WHERE Cart_Item_ID = :cart_item_id
    ");
    $stmt->execute([':cart_item_id' => $cartItemId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 5) Update quantity of a cart item
function updateCartItemQuantity($pdo, $cartItemId, $quantity) {
    $stmt = $pdo->prepare("
        UPDATE Cart_Item
        SET Quantity = :quantity
        WHERE Cart_Item_ID = :cart_item_id
    ");
    $stmt->execute([
        ':quantity' => $quantity,
        ':cart_item_id' => $cartItemId
    ]);
    return $stmt->rowCount();
}

// 6) Remove one item from the cart
function deleteCartItemById($pdo, $cartItemId) {
    $stmt = $pdo->prepare("
        DELETE FROM Cart_Item WHERE Cart_Item_ID = :cart_item_id
    ");
    $stmt->execute([':cart_item_id' => $cartItemId]);
    return $stmt->rowCount();
}

// 7) Remove all items from a cart
function deleteCartItemsByCartId($pdo, $cartId) {
    $stmt = $pdo->prepare("
        DELETE FROM Cart_Item WHERE Cart_ID = :cart_id
    ");
    $stmt->execute([':cart_id' => $cartId]);
    return $stmt->rowCount();
}

// 8) Remove all items for a product (optional, e.g., product discontinued)
function deleteCartItemsByProductId($pdo, $productId) {
    $stmt = $pdo->prepare("
        DELETE FROM Cart_Item WHERE Product_ID = :product_id
    ");
    $stmt->execute([':product_id' => $productId]);
    return $stmt->rowCount();
}
?>
