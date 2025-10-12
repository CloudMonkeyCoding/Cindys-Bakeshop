<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/order_functions.php';
require_once __DIR__ . '/order_item_functions.php';
require_once __DIR__ . '/transaction_functions.php';
require_once __DIR__ . '/inventory_functions.php';
require_once __DIR__ . '/product_functions.php';
require_once __DIR__ . '/cart_functions.php';
require_once __DIR__ . '/cart_item_functions.php';
require_once __DIR__ . '/email_functions.php';
require_once __DIR__ . '/notification_functions.php';
require_once __DIR__ . '/user_request_helpers.php';
require_once __DIR__ . '/audit_log_functions.php';

startJsonResponse();
requireDatabaseConnection($pdo);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        [$userId] = resolveUserContext($pdo, $_GET, ['allowMissing' => true]);
        $orders = $userId > 0 ? getOrdersByUserId($pdo, $userId) : [];
        $orders = array_map(static function ($order) {
            $imageMeta = [
                'Image_Path' => $order['Image_Path'] ?? '',
                'Category' => $order['Category'] ?? '',
            ];
            $order['Image_Url'] = getProductImageUrl($imageMeta, '/');
            return $order;
        }, $orders);

        sendJsonResponse($orders);

    case 'list_all':
        $orders = getAllOrdersWithSummary($pdo);
        $orders = array_map(static function ($order) {
            $imageMeta = [
                'Image_Path' => $order['Image_Path'] ?? '',
                'Category' => $order['Category'] ?? '',
            ];
            $order['Image_Url'] = getProductImageUrl($imageMeta, '/');
            return $order;
        }, $orders);

        sendJsonResponse($orders);

    case 'create':
        [$userId, $user] = resolveUserContext($pdo, $_POST, ['includeUser' => true]);
        $items = json_decode($_POST['items'] ?? '[]', true);
        if (!is_array($items)) {
            $items = [];
        }

        $orderTypeInput = isset($_POST['order_type']) ? trim((string)$_POST['order_type']) : '';
        $orderType = in_array($orderTypeInput, ['Delivery', 'Pick up'], true) ? $orderTypeInput : 'Delivery';
        $mop = $_POST['mop'] ?? '';
        $specialInstructions = isset($_POST['special_instructions']) ? trim((string)$_POST['special_instructions']) : '';
        if ($specialInstructions !== '') {
            if (function_exists('mb_substr')) {
                $specialInstructions = mb_substr($specialInstructions, 0, 500);
            } else {
                $specialInstructions = substr($specialInstructions, 0, 500);
            }
        } else {
            $specialInstructions = null;
        }

        foreach ($items as $it) {
            $productId = (int)($it['product_id'] ?? 0);
            $quantity = (int)($it['quantity'] ?? 0);
            $inventory = getInventoryByProductId($pdo, $productId);
            if (!$inventory || $inventory['Stock_Quantity'] < $quantity) {
                sendJsonResponse(['error' => 'Insufficient stock for product ID ' . $productId], 400);
            }
        }

        $orderId = addOrder($pdo, $userId, date('Y-m-d H:i:s'), 'Pending', 'online', $orderType, $specialInstructions);
        $total = 0;
        foreach ($items as $it) {
            $productId = (int)$it['product_id'];
            $quantity = (int)$it['quantity'];
            $stmt = $pdo->prepare('SELECT Price FROM product WHERE Product_ID = :id');
            $stmt->execute([':id' => $productId]);
            $price = (float)$stmt->fetchColumn();
            $subtotal = $price * $quantity;
            $total += $subtotal;
            addOrderItem($pdo, $orderId, $productId, $quantity, $subtotal);
            adjustInventoryStock($pdo, $productId, -$quantity, [
                'change_source' => 'order',
                'reference_type' => 'order',
                'reference_id' => $orderId,
                'note' => 'Online order placement'
            ]);
            adjustProductStock($pdo, $productId, -$quantity);

            $inventory = getInventoryByProductId($pdo, $productId);
            if ($inventory && $inventory['Stock_Quantity'] < 20) {
                $product = getProductById($pdo, $productId);
                sendLowStockEmail($product['Name'], (int)$inventory['Stock_Quantity']);
                addNotification(
                    $pdo,
                    'low_stock',
                    $productId,
                    "Stock for {$product['Name']} is low. Current level: {$inventory['Stock_Quantity']}."
                );
            }
        }

        addTransaction($pdo, $orderId, $mop, 'Pending', date('Y-m-d'), $total, null);
        $cart = getCartByUserId($pdo, $userId);
        if ($cart) {
            deleteCartItemsByCartId($pdo, $cart['Cart_ID']);
        }

        sendOrderNotificationEmail($orderId, $userId, $total);
        if ($user && isset($user['Email'])) {
            sendOrderConfirmationEmail($user['Email'], $orderId, $total);
        }

        addNotification(
            $pdo,
            'order',
            $orderId,
            "Order #{$orderId} has been placed by user ID {$userId}. Total amount: {$total}."
        );

        record_audit_log($pdo, 'order_created', "Online order #{$orderId} created.", [
            'actor_id' => $userId ?: null,
            'actor_email' => $user['Email'] ?? null,
            'source' => 'order_api',
            'metadata' => [
                'order_id' => $orderId,
                'total' => $total,
                'order_type' => $orderType,
                'payment_method' => $mop,
                'special_instructions' => $specialInstructions,
                'item_count' => count($items),
            ],
        ]);

        sendJsonResponse(['order_id' => $orderId]);

    case 'view':
        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = getOrderById($pdo, $orderId);
        $user = $order ? getUserById($pdo, $order['User_ID']) : null;
        $stmt = $pdo->prepare('SELECT oi.Quantity, oi.Subtotal, p.Name FROM order_item oi JOIN product p ON oi.Product_ID = p.Product_ID WHERE oi.Order_ID = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT Payment_Method, Payment_Status, Amount_Paid, Reference_Number FROM transaction WHERE Order_ID = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

        sendJsonResponse(['order' => $order, 'user' => $user, 'items' => $items, 'transaction' => $transaction]);

    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
}
?>
