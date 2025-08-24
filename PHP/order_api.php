<?php
require_once 'db_connect.php';
require_once 'order_functions.php';
require_once 'order_item_functions.php';
require_once 'transaction_functions.php';
require_once 'user_functions.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $email = $_GET['email'] ?? '';
        if ($email) {
            $user = getUserByEmail($pdo, $email);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            $userId = (int)$user['User_ID'];
        } else {
            $userId = (int)($_GET['user_id'] ?? 0);
        }
        $orders = getOrdersByUserId($pdo, $userId);
        echo json_encode($orders);
        break;
    case 'create':
        $email = $_POST['email'] ?? '';
        if ($email) {
            $user = getUserByEmail($pdo, $email);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            $userId = (int)$user['User_ID'];
        } else {
            $userId = (int)($_POST['user_id'] ?? 0);
        }
        $items = json_decode($_POST['items'] ?? '[]', true);
        $mop   = $_POST['mop'] ?? '';
        $orderId = addOrder($pdo, $userId, date('Y-m-d'), 'Pending');
        $total = 0;
        foreach ($items as $it) {
            $stmt = $pdo->prepare('SELECT Price FROM Product WHERE Product_ID = :id');
            $stmt->execute([':id' => $it['product_id']]);
            $price = $stmt->fetchColumn();
            $subtotal = $price * $it['quantity'];
            $total += $subtotal;
            addOrderItem($pdo, $orderId, $it['product_id'], $it['quantity'], $subtotal);
        }
        addTransaction($pdo, $orderId, $mop, 'Pending', date('Y-m-d'), $total, null);
        echo json_encode(['order_id' => $orderId]);
        break;
    case 'view':
        $orderId = (int)($_GET['order_id'] ?? 0);
        $order = getOrderById($pdo, $orderId);
        $user = $order ? getUserById($pdo, $order['User_ID']) : null;
        $stmt = $pdo->prepare('SELECT oi.Quantity, oi.Subtotal, p.Name FROM Order_Item oi JOIN Product p ON oi.Product_ID = p.Product_ID WHERE oi.Order_ID = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('SELECT Payment_Method, Payment_Status, Amount_Paid, Reference_Number FROM Transaction WHERE Order_ID = :order_id');
        $stmt->execute([':order_id' => $orderId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['order' => $order, 'user' => $user, 'items' => $items, 'transaction' => $transaction]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
