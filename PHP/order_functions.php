<?php
// 1) Create a new order
function addOrder($pdo, $userId, $orderDate, $status, $source = 'online', $fulfillmentType = 'Delivery', $specialInstructions = null) {
    echo "Debug: addOrder called\n";
    try {
        $validSources = ['online', 'walk-in'];
        if (in_array($source, $validSources, true) === false) {
            $source = 'online';
        }

        $validFulfillment = ['Delivery', 'Pick up'];
        if (in_array($fulfillmentType, $validFulfillment, true) === false) {
            $fulfillmentType = 'Delivery';
        }

        if ($specialInstructions !== null) {
            $specialInstructions = trim((string)$specialInstructions);
            if ($specialInstructions === '') {
                $specialInstructions = null;
            }
        }

        if ($orderDate instanceof DateTimeInterface) {
            $orderDateValue = $orderDate->format('Y-m-d H:i:s');
        } else {
            $orderDateValue = is_string($orderDate) ? trim($orderDate) : '';
            if ($orderDateValue !== '') {
                $parsed = DateTime::createFromFormat('Y-m-d H:i:s', $orderDateValue);
                if ($parsed === false) {
                    $parsed = DateTime::createFromFormat('Y-m-d', $orderDateValue);
                }
                if ($parsed instanceof DateTimeInterface) {
                    $orderDateValue = $parsed->format('Y-m-d H:i:s');
                } else {
                    $orderDateValue = '';
                }
            }
            if ($orderDateValue === '') {
                $orderDateValue = date('Y-m-d H:i:s');
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO `order` (User_ID, Order_Date, Source, Fulfillment_Type, Special_Instructions, Status)
            VALUES (:user_id, :order_date, :source, :fulfillment_type, :special_instructions, :status)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':order_date' => $orderDateValue,
            ':source' => $source,
            ':fulfillment_type' => $fulfillmentType,
            ':special_instructions' => $specialInstructions,
            ':status' => $status
        ]);
        $orderId = $pdo->lastInsertId();
        echo "Debug: addOrder created order ID {$orderId}\n";
        return $orderId;
    } catch (Throwable $e) {
        echo "Debug: addOrder error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 2) Get all orders
function getAllOrders($pdo) {
    echo "Debug: getAllOrders called\n";
    try {
        $stmt = $pdo->query("SELECT * FROM `order`");
        $results = $stmt->fetchAll();
        echo "Debug: getAllOrders fetched " . count($results) . " rows\n";
        return $results;
    } catch (Throwable $e) {
        echo "Debug: getAllOrders error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 3) Get all orders for all users along with product images and summaries
function getAllOrdersWithSummary($pdo)
{
    echo "Debug: getAllOrdersWithSummary called\n";
    try {
        $sql = "
            SELECT o.Order_ID,
                   o.User_ID,
                   u.Email AS User_Email,
                   u.Name AS User_Name,
                   o.Order_Date,
                   o.Source,
                   o.Fulfillment_Type,
                   o.Status,
                   o.Special_Instructions,
                   MIN(p.Image_Path) AS Image_Path,
                   MIN(p.Category) AS Category,
                   COALESCE(SUM(oi.Quantity), 0) AS Item_Count,
                   COALESCE(SUM(oi.Subtotal), 0) AS Total_Amount,
                   GROUP_CONCAT(
                       CONCAT(p.Name, ' x', oi.Quantity)
                       ORDER BY p.Name SEPARATOR ', '
                   ) AS Item_Summary
            FROM `order` o
            LEFT JOIN user u ON o.User_ID = u.User_ID
            LEFT JOIN order_item oi ON o.Order_ID = oi.Order_ID
            LEFT JOIN product p ON oi.Product_ID = p.Product_ID
            GROUP BY o.Order_ID,
                     o.User_ID,
                     u.Email,
                     u.Name,
                     o.Order_Date,
                     o.Source,
                     o.Fulfillment_Type,
                     o.Status,
                     o.Special_Instructions
            ORDER BY o.Order_Date DESC, o.Order_ID DESC
        ";

        echo "Debug: getAllOrdersWithSummary SQL prepared\n";
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Debug: getAllOrdersWithSummary fetched " . count($results) . " rows\n";
        return $results;
    } catch (Throwable $e) {
        echo "Debug: getAllOrdersWithSummary error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 4) Get all orders for a specific user along with a product image
function getOrdersByUserId($pdo, $userId) {
    echo "Debug: getOrdersByUserId called for user {$userId}\n";
    try {
        $stmt = $pdo->prepare("
            SELECT o.Order_ID,
                   o.User_ID,
                   o.Order_Date,
                   o.Source,
                   o.Fulfillment_Type,
                   o.Status,
                   o.Special_Instructions,
                   MIN(p.Image_Path) AS Image_Path,
                   MIN(p.Category) AS Category,
                   COALESCE(SUM(oi.Quantity), 0) AS Item_Count,
                   COALESCE(SUM(oi.Subtotal), 0) AS Total_Amount,
                   GROUP_CONCAT(
                       CONCAT(p.Name, ' x', oi.Quantity)
                       ORDER BY p.Name SEPARATOR ', '
                   ) AS Item_Summary
            FROM `order` o
            LEFT JOIN order_item oi ON o.Order_ID = oi.Order_ID
            LEFT JOIN product p ON oi.Product_ID = p.Product_ID
            WHERE o.User_ID = :user_id
            GROUP BY o.Order_ID, o.User_ID, o.Order_Date, o.Source, o.Fulfillment_Type, o.Status, o.Special_Instructions
            ORDER BY o.Order_Date DESC, o.Order_ID DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Debug: getOrdersByUserId fetched " . count($results) . " rows\n";
        return $results;
    } catch (Throwable $e) {
        echo "Debug: getOrdersByUserId error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 5) Get a single order by ID
function getOrderById($pdo, $orderId) {
    echo "Debug: getOrderById called for order {$orderId}\n";
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM `order` WHERE Order_ID = :order_id
        ");
        $stmt->execute([':order_id' => $orderId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Debug: getOrderById result " . ($result ? 'found' : 'not found') . "\n";
        return $result;
    } catch (Throwable $e) {
        echo "Debug: getOrderById error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 6) Update order status
function updateOrderStatus($pdo, $orderId, $status) {
    echo "Debug: updateOrderStatus called for order {$orderId}\n";
    try {
        $stmt = $pdo->prepare("
            UPDATE `order`
            SET Status = :status
            WHERE Order_ID = :order_id
        ");
        $stmt->execute([
            ':status' => $status,
            ':order_id' => $orderId
        ]);
        $rows = $stmt->rowCount();
        echo "Debug: updateOrderStatus affected {$rows} rows\n";
        return $rows;
    } catch (Throwable $e) {
        echo "Debug: updateOrderStatus error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 7) Delete an order by ID
function deleteOrderById($pdo, $orderId) {
    echo "Debug: deleteOrderById called for order {$orderId}\n";
    try {
        $stmt = $pdo->prepare("
            DELETE FROM `order` WHERE Order_ID = :order_id
        ");
        $stmt->execute([':order_id' => $orderId]);
        $rows = $stmt->rowCount();
        echo "Debug: deleteOrderById affected {$rows} rows\n";
        return $rows;
    } catch (Throwable $e) {
        echo "Debug: deleteOrderById error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 8) Count total orders
function countOrders($pdo, $startDate = null, $endDate = null) {
    echo "Debug: countOrders called\n";
    try {
        if ($startDate && $endDate) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM `order`
                WHERE Order_Date BETWEEN :start_date AND :end_date
            ");
            $stmt->execute([
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `order`");
        }

        $count = $stmt->fetchColumn();
        echo "Debug: countOrders result {$count}\n";
        return $count;
    } catch (Throwable $e) {
        echo "Debug: countOrders error - " . $e->getMessage() . "\n";
        throw $e;
    }
}

// 9) Get orders by status
function getOrdersByStatus($pdo, $status, $startDate = null, $endDate = null) {
    echo "Debug: getOrdersByStatus called for status {$status}\n";
    try {
        $query = "SELECT * FROM `order` WHERE Status = :status";
        $params = [':status' => $status];

        if ($startDate && $endDate) {
            $query .= " AND Order_Date BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = $endDate;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $results = $stmt->fetchAll();
        echo "Debug: getOrdersByStatus fetched " . count($results) . " rows\n";
        return $results;
    } catch (Throwable $e) {
        echo "Debug: getOrdersByStatus error - " . $e->getMessage() . "\n";
        throw $e;
    }
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
    require_once __DIR__ . '/audit_log_functions.php';
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

    $actorId = null;
    $actorEmail = null;
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (isset($_SESSION['admin_user_id']) && is_numeric($_SESSION['admin_user_id'])) {
            $actorId = (int) $_SESSION['admin_user_id'];
        }
        if (!empty($_SESSION['admin_email'])) {
            $actorEmail = (string) $_SESSION['admin_email'];
        }
    }

    record_api_call($pdo, 'order_functions', [
        'action' => $action,
        'actor_id' => $actorId,
        'actor_email' => $actorEmail,
    ]);

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
