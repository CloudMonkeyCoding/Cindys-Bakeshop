<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

require_once '../../PHP/db_connect.php';
require_once '../../PHP/order_functions.php';
require_once '../../PHP/order_item_functions.php';
require_once '../../PHP/product_functions.php';
require_once '../../PHP/inventory_functions.php';
require_once '../../PHP/transaction_functions.php';
require_once '../../PHP/user_functions.php';
require_once '../../PHP/delivery_functions.php';
require_once '../../PHP/email_functions.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
$token = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';

if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$respond = static function (int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
};

switch ($action) {
    case 'search_products':
        $query = trim(filter_input(INPUT_POST, 'query', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $limit = filter_input(INPUT_POST, 'limit', FILTER_VALIDATE_INT);
        if (!is_int($limit) || $limit <= 0 || $limit > 100) {
            $limit = 20;
        }
        $inStockOnly = filter_input(INPUT_POST, 'in_stock_only', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $sql = "SELECT p.Product_ID, p.Name, p.Price, p.Category,\n                       COALESCE(i.Stock_Quantity, p.Stock_Quantity) AS Stock_Quantity\n                FROM product p\n                LEFT JOIN inventory i ON i.Product_ID = p.Product_ID";
        $conditions = [];
        $params = [];
        if ($query !== '') {
            $conditions[] = '(p.Name LIKE :term OR p.Category LIKE :term)';
            $params[':term'] = "%$query%";
        }
        if ($inStockOnly === true) {
            $conditions[] = 'COALESCE(i.Stock_Quantity, p.Stock_Quantity) > 0';
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY p.Name ASC LIMIT :limit';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $products[] = [
                'id' => (int)$row['Product_ID'],
                'name' => $row['Name'],
                'price' => (float)$row['Price'],
                'category' => $row['Category'],
                'stock' => (int)($row['Stock_Quantity'] ?? 0)
            ];
        }
        $respond(200, ['success' => true, 'products' => $products]);
        break;

    case 'search_customers':
        $query = trim(filter_input(INPUT_POST, 'query', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $limit = filter_input(INPUT_POST, 'limit', FILTER_VALIDATE_INT);
        if (!is_int($limit) || $limit <= 0 || $limit > 50) {
            $limit = 10;
        }
        $sql = "SELECT User_ID, Name, Email, Address FROM user";
        $params = [];
        if ($query !== '') {
            $sql .= ' WHERE Name LIKE :term OR Email LIKE :term';
            $params[':term'] = "%$query%";
        }
        $sql .= ' ORDER BY Name ASC LIMIT :limit';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $customers = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $customers[] = [
                'id' => (int)$row['User_ID'],
                'name' => $row['Name'],
                'email' => $row['Email'] ?? '',
                'address' => $row['Address'] ?? ''
            ];
        }
        $respond(200, ['success' => true, 'customers' => $customers]);
        break;

    case 'create_order':
        $customerMode = filter_input(INPUT_POST, 'customer_mode', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'guest';
        $customerMode = in_array($customerMode, ['existing', 'guest'], true) ? $customerMode : 'guest';

        $itemsRaw = $_POST['items'] ?? '[]';
        $itemsData = json_decode($itemsRaw, true);
        if (!is_array($itemsData) || empty($itemsData)) {
            $respond(422, ['success' => false, 'message' => 'Please add at least one product.']);
        }

        $orderItems = [];
        $orderTotal = 0.0;

        foreach ($itemsData as $item) {
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : 0;
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
            if ($productId <= 0 || $quantity <= 0) {
                $respond(422, ['success' => false, 'message' => 'Invalid product selection provided.']);
            }

            $product = getProductById($pdo, $productId);
            if (!$product) {
                $respond(404, ['success' => false, 'message' => "Product ID {$productId} does not exist."]);
            }

            $inventory = getInventoryByProductId($pdo, $productId);
            $availableStock = (int)($inventory['Stock_Quantity'] ?? $product['Stock_Quantity'] ?? 0);
            if ($quantity > $availableStock) {
                $respond(422, ['success' => false, 'message' => "Not enough stock for {$product['Name']}."]);
            }

            $price = (float)$product['Price'];
            $subtotal = $price * $quantity;
            $orderTotal += $subtotal;
            $orderItems[] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal
            ];
        }

        $fulfillmentType = filter_input(INPUT_POST, 'fulfillment_type', FILTER_SANITIZE_SPECIAL_CHARS) ?: '';
        $allowedFulfillment = ['Delivery', 'Pick up'];
        if (!in_array($fulfillmentType, $allowedFulfillment, true)) {
            $fulfillmentType = 'Pick up';
        }

        $orderStatus = filter_input(INPUT_POST, 'order_status', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'Pending';
        $allowedStatuses = ['Pending', 'Confirmed', 'Shipped', 'Delivered'];
        if (!in_array($orderStatus, $allowedStatuses, true)) {
            $orderStatus = 'Pending';
        }

        $paymentMethod = trim(filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Cash');
        if ($paymentMethod === '') {
            $paymentMethod = 'Cash';
        }

        $paymentStatusInput = filter_input(INPUT_POST, 'payment_status', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'Paid';
        $allowedPaymentStatuses = ['Paid', 'Pending', 'Partially Paid'];
        $paymentStatus = in_array($paymentStatusInput, $allowedPaymentStatuses, true) ? $paymentStatusInput : 'Paid';

        $paymentAmount = filter_input(INPUT_POST, 'payment_amount', FILTER_VALIDATE_FLOAT);
        if (!is_float($paymentAmount) || $paymentAmount < 0) {
            $paymentAmount = $paymentStatus === 'Paid' ? $orderTotal : 0.0;
        }
        $paymentAmount = min($paymentAmount, $orderTotal);

        $referenceNumber = trim(filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        if ($referenceNumber === '') {
            $referenceNumber = null;
        }

        $customerEmail = '';
        $userId = null;

        try {
            $pdo->beginTransaction();

            if ($customerMode === 'existing') {
                $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                if (!$userId) {
                    throw new InvalidArgumentException('Select an existing customer to continue or submit as a walk-in guest.', 422);
                }
                $user = getUserById($pdo, $userId);
                if (!$user) {
                    throw new InvalidArgumentException('Selected customer could not be found.', 404);
                }
                $customerEmail = $user['Email'] ?? '';
            }

            $orderId = addOrder($pdo, $userId, date('Y-m-d'), $orderStatus, 'walk-in', $fulfillmentType);

            foreach ($orderItems as $line) {
                addOrderItem($pdo, $orderId, $line['product_id'], $line['quantity'], $line['subtotal']);
                adjustInventoryStock($pdo, $line['product_id'], -$line['quantity']);
                adjustProductStock($pdo, $line['product_id'], -$line['quantity']);
            }

            $paymentDate = date('Y-m-d');
            addTransaction($pdo, $orderId, $paymentMethod, $paymentStatus, $paymentDate, $paymentAmount, $referenceNumber);

            if ($fulfillmentType === 'Delivery') {
                addDelivery($pdo, $orderId, 'Pending', null, null);
            }

            $pdo->commit();
        } catch (InvalidArgumentException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $code = $exception->getCode();
            if ($code < 400 || $code > 599) {
                $code = 422;
            }
            $respond($code, ['success' => false, 'message' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Walk-in order API error: ' . $exception->getMessage());
            $respond(500, ['success' => false, 'message' => 'Unable to create the order. Please try again.']);
        }

        if ($customerEmail !== '') {
            try {
                sendOrderConfirmationEmail($customerEmail, $orderId, $orderTotal);
            } catch (Throwable $exception) {
                error_log('Walk-in order email error: ' . $exception->getMessage());
            }
        }

        $respond(200, [
            'success' => true,
            'order_id' => (int)$orderId,
            'total' => $orderTotal
        ]);
        break;

    default:
        $respond(400, ['success' => false, 'message' => 'Unknown action']);
}
