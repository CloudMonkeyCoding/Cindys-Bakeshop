<?php
require_once 'db_connect.php';
require_once 'cart_functions.php';
require_once 'cart_item_functions.php';
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
        if ($userId <= 0) {
            echo json_encode(['cart_id' => 0, 'items' => []]);
            break;
        }

        $cart = getCartByUserId($pdo, $userId);
        if (!$cart) {
            $cartId = createCart($pdo, $userId);
            $items = [];
        } else {
            $cartId = $cart['Cart_ID'];
            $stmt = $pdo->prepare("SELECT ci.Cart_Item_ID, ci.Product_ID, ci.Quantity, p.Name, p.Price, p.Stock_Quantity, p.Image_Path FROM cart_item ci JOIN product p ON ci.Product_ID = p.Product_ID WHERE ci.Cart_ID = :cart_id");
            $stmt->execute([':cart_id' => $cartId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        echo json_encode(['cart_id' => $cartId, 'items' => $items]);
        break;
    case 'add':
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        $userId = (int)($_POST['user_id'] ?? 0);
        $email = $_POST['email'] ?? '';

        if ($userId <= 0 && $email) {
            $user = getUserByEmail($pdo, $email);
            if ($user) {
                $userId = (int)$user['User_ID'];
            }
        }

        // Ensure the cart exists; if not, create or fetch using user ID
        $cartExists = $cartId > 0 ? getCartById($pdo, $cartId) : null;
        if (!$cartExists) {
            if ($userId <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid or missing cart_id']);
                break;
            }
            $cart = getCartByUserId($pdo, $userId);
            $cartId = $cart ? $cart['Cart_ID'] : createCart($pdo, $userId);
        }

        $existing = getCartItemByCartAndProduct($pdo, $cartId, $productId);
        if ($existing) {
            $newQty = $existing['Quantity'] + $qty;
            $updated = updateCartItemQuantity($pdo, $existing['Cart_Item_ID'], $newQty);
            echo json_encode(['cart_item_id' => $existing['Cart_Item_ID'], 'cart_id' => $cartId, 'updated' => $updated]);
        } else {
            $id = addCartItem($pdo, $cartId, $productId, $qty);
            echo json_encode(['cart_item_id' => $id, 'cart_id' => $cartId]);
        }
        break;
    case 'update':
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);
        $result = updateCartItemQuantity($pdo, $cartItemId, $qty);
        if ($qty <= 0) {
            echo json_encode(['deleted' => $result]);
        } else {
            echo json_encode(['updated' => $result]);
        }
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
