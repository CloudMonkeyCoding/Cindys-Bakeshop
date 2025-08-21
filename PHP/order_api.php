<?php
require_once 'db_connect.php';
require_once 'order_functions.php';
require_once 'order_item_functions.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $userId = (int)($_GET['user_id'] ?? 0);
        $orders = getOrdersByUserId($pdo, $userId);
        echo json_encode($orders);
        break;
    case 'create':
        $userId = (int)($_POST['user_id'] ?? 0);
        $items = json_decode($_POST['items'] ?? '[]', true);
        $orderId = addOrder($pdo, $userId, date('Y-m-d'), 'Pending');
        foreach ($items as $it) {
            $stmt = $pdo->prepare('SELECT Price FROM Product WHERE Product_ID = :id');
            $stmt->execute([':id' => $it['product_id']]);
            $price = $stmt->fetchColumn();
            $subtotal = $price * $it['quantity'];
            addOrderItem($pdo, $orderId, $it['product_id'], $it['quantity'], $subtotal);
        }
        echo json_encode(['order_id' => $orderId]);
        break;
    case 'view':
        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = getOrderById($pdo, $orderId);
        $stmt = $pdo->prepare('SELECT oi.Quantity, oi.Subtotal, p.Name FROM Order_Item oi JOIN Product p ON oi.Product_ID = p.Product_ID WHERE oi.Order_ID = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['order' => $order, 'items' => $items]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
