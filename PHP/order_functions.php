<?php
// 1) Create a new order
function addOrder($pdo, $userId, $orderDate, $status) {
    $stmt = $pdo->prepare("
        INSERT INTO `order` (User_ID, Order_Date, Status)
        VALUES (:user_id, :order_date, :status)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':order_date' => $orderDate,
        ':status' => $status
    ]);
    return $pdo->lastInsertId();
}

// 2) Get all orders
function getAllOrders($pdo) {
    $stmt = $pdo->query("SELECT * FROM `order`");
    return $stmt->fetchAll();
}

// 3) Get all orders for a specific user along with a product image
function getOrdersByUserId($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT o.Order_ID,
               o.User_ID,
               o.Order_Date,
               o.Status,
               MIN(p.Image_Path) AS Image_Path
        FROM `order` o
        LEFT JOIN order_item oi ON o.Order_ID = oi.Order_ID
        LEFT JOIN product p ON oi.Product_ID = p.Product_ID
        WHERE o.User_ID = :user_id
        GROUP BY o.Order_ID, o.User_ID, o.Order_Date, o.Status
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Get a single order by ID
function getOrderById($pdo, $orderId) {
    $stmt = $pdo->prepare("
        SELECT * FROM `order` WHERE Order_ID = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 5) Update order status
function updateOrderStatus($pdo, $orderId, $status) {
    $stmt = $pdo->prepare("
        UPDATE `order`
        SET Status = :status
        WHERE Order_ID = :order_id
    ");
    $stmt->execute([
        ':status' => $status,
        ':order_id' => $orderId
    ]);
    return $stmt->rowCount();
}

// 6) Delete an order by ID
function deleteOrderById($pdo, $orderId) {
    $stmt = $pdo->prepare("
        DELETE FROM `order` WHERE Order_ID = :order_id
    ");
    $stmt->execute([':order_id' => $orderId]);
    return $stmt->rowCount();
}

// 7) Count total orders
function countOrders($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `order`");
    return $stmt->fetchColumn();
}

// 8) Get orders by status
function getOrdersByStatus($pdo, $status) {
    $stmt = $pdo->prepare("
        SELECT * FROM `order` WHERE Status = :status
    ");
    $stmt->execute([':status' => $status]);
    return $stmt->fetchAll();
}

// --- API Endpoints -------------------------------------------------------
// Allows this file to handle status updates via AJAX.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_once __DIR__ . '/db_connect.php';
    if (!$pdo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    header('Content-Type: application/json');
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

    switch ($action) {
        case 'updateStatus':
            $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT) ?? 0;
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
            $success = updateOrderStatus($pdo, $orderId, $status) > 0;
            echo json_encode(['success' => $success]);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    exit;
}
?>
