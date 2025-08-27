<?php
require_once 'db_connect.php';
require_once 'cart_functions.php';
require_once 'cart_item_functions.php';
require_once 'user_functions.php';
require_once 'product_functions.php';
require_once 'inventory_functions.php';

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

        if ($qty <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Quantity must be greater than zero']);
            break;
        }

        $product = getProductById($pdo, $productId);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            break;
        }

        $inventory = getInventoryByProductId($pdo, $productId);
        $available = $inventory ? (int)$inventory['Stock_Quantity'] : (int)($product['Stock_Quantity'] ?? 0);
        if ($available <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Product out of stock']);
            break;
        }

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
        $existingQty = $existing ? (int)$existing['Quantity'] : 0;
        $maxAdd = $available - $existingQty;
        if ($maxAdd <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Requested quantity exceeds available stock']);
            break;
        }
        $capped = false;
        if ($qty > $maxAdd) {
            $qty = $maxAdd;
            $capped = true;
        }

        if ($existing) {
            $newQty = $existingQty + $qty;
            $updated = updateCartItemQuantity($pdo, $existing['Cart_Item_ID'], $newQty);
            $response = ['cart_item_id' => $existing['Cart_Item_ID'], 'cart_id' => $cartId, 'updated' => $updated, 'quantity' => $newQty];
        } else {
            $id = addCartItem($pdo, $cartId, $productId, $qty);
            $response = ['cart_item_id' => $id, 'cart_id' => $cartId, 'quantity' => $qty];
        }
        if ($capped) {
            $response['capped'] = true;
        }
        echo json_encode($response);
        break;
    case 'update':
        $cartItemId = (int)($_POST['cart_item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 1);

        if ($qty <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Quantity must be greater than zero']);
            break;
        }

        $cartItem = getCartItemById($pdo, $cartItemId);
        if (!$cartItem) {
            http_response_code(404);
            echo json_encode(['error' => 'Cart item not found']);
            break;
        }

        $productId = (int)$cartItem['Product_ID'];
        $product = getProductById($pdo, $productId);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            break;
        }

        $inventory = getInventoryByProductId($pdo, $productId);
        $available = $inventory ? (int)$inventory['Stock_Quantity'] : (int)($product['Stock_Quantity'] ?? 0);
        if ($available <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Product out of stock']);
            break;
        }

        $capped = false;
        if ($qty > $available) {
            $qty = $available;
            $capped = true;
        }

        $result = updateCartItemQuantity($pdo, $cartItemId, $qty);
        $response = ['updated' => $result, 'cart_item_id' => $cartItemId, 'quantity' => $qty];
        if ($capped) {
            $response['capped'] = true;
        }
        echo json_encode($response);
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
