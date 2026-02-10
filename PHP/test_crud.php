<?php
require 'db_connect.php';
require 'user_functions.php';
require 'product_functions.php';
require 'inventory_functions.php';
require 'cart_functions.php';
require 'cart_item_functions.php';
require 'order_functions.php';
require 'order_item_functions.php';
require 'delivery_personnel_functions.php';
require 'delivery_functions.php';
require 'transaction_functions.php';
require 'blacklist_functions.php';
require 'store_staff_functions.php';

// Simple CLI test script for CRUD operations across various modules

echo "Starting CRUD tests\n";

try {
    // ---- User and related modules ----
    $userId = addUser($pdo, 'Test User', 'testuser@example.com', 'password', '123 Test St', 0);
    echo "Created user: $userId\n";
    updateUserById($pdo, $userId, 'Updated User', 'testuser@example.com', '123 Test St', 0);
    echo "Updated user: $userId\n";

    $staffId = addStoreStaff($pdo, $userId);
    echo "Added store staff: $staffId\n";
    deleteStoreStaffById($pdo, $staffId);
    echo "Deleted store staff: $staffId\n";

    $blacklistId = addBlacklist($pdo, $userId, 'Testing', '127.0.0.1');
    echo "Added blacklist entry: $blacklistId\n";
    deleteBlacklistById($pdo, $blacklistId);
    echo "Deleted blacklist entry: $blacklistId\n";

    // ---- Product and Inventory ----
    $productId = addProduct($pdo, 'Test Product', 'Sample description', 9.99, 50, 'Test Category');
    echo "Created product: $productId\n";
    updateProductById($pdo, $productId, 'Updated Product', 'Sample description', 12.99, 50, 'Test Category');
    echo "Updated product: $productId\n";

    addInventory($pdo, $productId, 50);
    echo "Added inventory for product: $productId\n";
    updateInventoryStock($pdo, $productId, 100);
    echo "Updated inventory stock for product: $productId\n";

    // ---- Cart and Cart Items ----
    $cartId = createCart($pdo, $userId);
    echo "Created cart: $cartId\n";
    $cartItemId = addCartItem($pdo, $cartId, $productId, 2);
    echo "Added cart item: $cartItemId\n";
    updateCartItemQuantity($pdo, $cartItemId, 3);
    echo "Updated cart item quantity: $cartItemId\n";

    // ---- Orders and Order Items ----
    $orderId = addOrder($pdo, $userId, date('Y-m-d H:i:s'), 'pending');
    echo "Created order: $orderId\n";
    $orderItemId = addOrderItem($pdo, $orderId, $productId, 3, 29.97);
    echo "Added order item: $orderItemId\n";
    updateOrderStatus($pdo, $orderId, 'processing');
    echo "Updated order status: $orderId\n";
    updateOrderItem($pdo, $orderItemId, 4, 39.96);
    echo "Updated order item: $orderItemId\n";

    // ---- Delivery Personnel and Delivery ----
    $personnelId = addDeliveryPersonnel($pdo, $userId);
    echo "Added delivery personnel: $personnelId\n";
    $deliveryId = addDelivery($pdo, $orderId, 'shipped', date('Y-m-d H:i:s'), $personnelId);
    echo "Added delivery: $deliveryId\n";
    updateDelivery($pdo, $deliveryId, 'delivered', date('Y-m-d H:i:s'), $personnelId);
    echo "Updated delivery: $deliveryId\n";

    // ---- Transactions ----
    $transactionId = addTransaction($pdo, $orderId, 'cash', 'paid', date('Y-m-d H:i:s'), 39.96, 'REF123');
    echo "Added transaction: $transactionId\n";
    updateTransactionStatus($pdo, $transactionId, 'completed');
    echo "Updated transaction status: $transactionId\n";

    // ---- Cleanup ----
    deleteTransactionById($pdo, $transactionId);
    deleteDeliveryById($pdo, $deliveryId);
    deleteDeliveryPersonnelById($pdo, $personnelId);
    deleteOrderItemById($pdo, $orderItemId);
    deleteOrderById($pdo, $orderId);
    deleteCartItemById($pdo, $cartItemId);
    deleteCartById($pdo, $cartId);
    deleteInventoryByProductId($pdo, $productId);
    deleteProductById($pdo, $productId);
    deleteUserById($pdo, $userId);
    echo "Cleanup complete\n";

} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
