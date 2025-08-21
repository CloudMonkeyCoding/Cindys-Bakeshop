<?php
require_once 'db_connect.php';
require_once 'cart_functions.php';
require_once 'cart_item_functions.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $userId = (int)($_GET['user_id'] ?? 0);
        $cart = getCartByUserId($pdo, $userId);
        if (!$cart) {
            $cartId = createCart($pdo, $userId);
            $items = [];
        } else {
            $cartId = $cart['Cart_ID'];
            $stmt = $pdo->prepare("SELECT ci.Cart_Item_ID, ci.Product_ID, ci.Quantity, p.Name, p.Price, p.Image_Path FROM Cart_Item ci JOIN Product p ON ci.Product_ID = p.Product_ID WHERE ci.Cart_ID = :cart_id");
            $stmt->execute([':cart_id' => $cartId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['cart_id' => $cartId, 'items' => $items]);
        break;
    case 'add':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        $id = addCartItem($pdo, $cartId, $productId, $qty);
        echo json_encode(['cart_item_id' => $id]);
        break;
    case 'update':
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        $updated = updateCartItemQuantity($pdo, $cartItemId, $qty);
        echo json_encode(['updated' => $updated]);
        break;
    case 'remove':
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        $deleted = deleteCartItemById($pdo, $cartItemId);
        echo json_encode(['deleted' => $deleted]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
