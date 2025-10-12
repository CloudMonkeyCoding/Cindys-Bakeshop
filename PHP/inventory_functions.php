<?php

function normalizeInventoryQuantity($value)
{
    if ($value === null) {
        return null;
    }

    if (is_numeric($value)) {
        return (int)$value;
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        $filtered = filter_var($trimmed, FILTER_VALIDATE_INT, ['options' => ['default' => null]]);
        if ($filtered !== null) {
            return (int)$filtered;
        }
    }

    return null;
}

function buildInventoryLogPayload($previousQuantity, $newQuantity, array $options = [])
{
    $previousNormalized = normalizeInventoryQuantity($previousQuantity);
    $newNormalized = normalizeInventoryQuantity($newQuantity);

    if ($previousNormalized === $newNormalized) {
        return null;
    }

    $changeAmount = null;
    $note = $options['note'] ?? null;

    if ($previousNormalized === null && $newNormalized !== null) {
        $changeAmount = $newNormalized;
        if ($note === null) {
            $note = 'Tracking started';
        }
    } elseif ($previousNormalized !== null && $newNormalized === null) {
        $changeAmount = -$previousNormalized;
        if ($note === null) {
            $note = 'Marked as pre-order';
        }
    } elseif ($previousNormalized !== null && $newNormalized !== null) {
        $changeAmount = $newNormalized - $previousNormalized;
    }

    if (!isset($options['change_source'])) {
        $options['change_source'] = 'manual_adjustment';
    }

    if ($note !== null) {
        $options['note'] = $note;
    }

    return [
        'previous_quantity' => $previousNormalized,
        'new_quantity' => $newNormalized,
        'change_amount' => $changeAmount,
        'options' => $options,
    ];
}

function recordInventoryStockLog($pdo, $productId, array $payload)
{
    $changeAmount = $payload['change_amount'];
    $previousQuantity = $payload['previous_quantity'];
    $newQuantity = $payload['new_quantity'];
    $options = $payload['options'] ?? [];

    $stmt = $pdo->prepare("
        INSERT INTO inventory_stock_log
            (Product_ID, Change_Amount, Previous_Quantity, New_Quantity, Change_Source, Reference_Type, Reference_ID, Note)
        VALUES
            (:product_id, :change_amount, :previous_quantity, :new_quantity, :change_source, :reference_type, :reference_id, :note)
    ");

    $stmt->execute([
        ':product_id' => $productId,
        ':change_amount' => $changeAmount,
        ':previous_quantity' => $previousQuantity,
        ':new_quantity' => $newQuantity,
        ':change_source' => $options['change_source'] ?? 'manual_adjustment',
        ':reference_type' => $options['reference_type'] ?? null,
        ':reference_id' => $options['reference_id'] ?? null,
        ':note' => $options['note'] ?? null,
    ]);

    return $pdo->lastInsertId();
}

// 1) Add inventory entry for a product
function addInventory($pdo, $productId, $stockQuantity, array $logOptions = []) {
    $stmt = $pdo->prepare("
        INSERT INTO inventory (Product_ID, Stock_Quantity)
        VALUES (:product_id, :stock_quantity)
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':stock_quantity' => $stockQuantity
    ]);

    $payload = buildInventoryLogPayload(null, $stockQuantity, $logOptions + ['change_source' => 'initial_entry', 'note' => 'Initial stock entry']);
    if ($payload) {
        recordInventoryStockLog($pdo, $productId, $payload);
    }

    return $pdo->lastInsertId();
}

// 2) Get inventory record by Product_ID
function getInventoryByProductId($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT * FROM inventory WHERE Product_ID = :product_id
    ");
    $stmt->execute([':product_id' => $productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3) Get all inventory records
function getAllInventory($pdo) {
    $stmt = $pdo->query("SELECT * FROM inventory");
    return $stmt->fetchAll();
}

// 3b) Get inventory with product details
function getInventoryWithProducts($pdo) {
    $stmt = $pdo->query(
        "SELECT p.Product_ID, p.Name, p.Category, i.Stock_Quantity\n" .
        "FROM inventory i\n" .
        "JOIN product p ON i.Product_ID = p.Product_ID"
    );
    return $stmt->fetchAll();
}

// 3c) Get inventory change log entries
function getInventoryChangeLog($pdo, $startDate = null, $endDate = null) {
    $conditions = [];
    $params = [];

    if ($startDate !== null) {
        $conditions[] = 'DATE(isl.Created_At) >= :start_date';
        $params[':start_date'] = $startDate;
    }

    if ($endDate !== null) {
        $conditions[] = 'DATE(isl.Created_At) <= :end_date';
        $params[':end_date'] = $endDate;
    }

    $whereClause = '';
    if (!empty($conditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $conditions);
    }

    $sql = "
        SELECT
            isl.Log_ID,
            isl.Product_ID,
            isl.Change_Amount,
            isl.Previous_Quantity,
            isl.New_Quantity,
            isl.Change_Source,
            isl.Reference_Type,
            isl.Reference_ID,
            isl.Note,
            isl.Created_At,
            p.Name AS Product_Name,
            o.Order_ID,
            o.Order_Date,
            u.Name AS Customer_Name
        FROM inventory_stock_log isl
        LEFT JOIN product p ON isl.Product_ID = p.Product_ID
        LEFT JOIN `order` o ON isl.Reference_Type = 'order' AND isl.Reference_ID = o.Order_ID
        LEFT JOIN user u ON o.User_ID = u.User_ID
        {$whereClause}
        ORDER BY isl.Created_At DESC, isl.Log_ID DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Update stock quantity for a product
function updateInventoryStock($pdo, $productId, $stockQuantity, array $logOptions = []) {
    $current = getInventoryByProductId($pdo, $productId);
    $previousQuantity = $current['Stock_Quantity'] ?? null;

    $stmt = $pdo->prepare("
        UPDATE inventory
        SET Stock_Quantity = :stock_quantity
        WHERE Product_ID = :product_id
    ");
    $stmt->execute([
        ':stock_quantity' => $stockQuantity,
        ':product_id' => $productId
    ]);

    $updated = getInventoryByProductId($pdo, $productId);
    $newQuantity = $updated['Stock_Quantity'] ?? null;

    $payload = buildInventoryLogPayload($previousQuantity, $newQuantity, $logOptions + ['change_source' => 'manual_adjustment']);
    if ($payload) {
        recordInventoryStockLog($pdo, $productId, $payload);
    }

    return $stmt->rowCount();
}

// 5) Adjust stock quantity (+/-)
function adjustInventoryStock($pdo, $productId, $quantityChange, array $logOptions = []) {
    $current = getInventoryByProductId($pdo, $productId);
    $previousQuantity = $current['Stock_Quantity'] ?? null;

    $stmt = $pdo->prepare("
        UPDATE inventory
        SET Stock_Quantity = Stock_Quantity + :quantity_change
        WHERE Product_ID = :product_id
    ");
    $stmt->execute([
        ':quantity_change' => $quantityChange,
        ':product_id' => $productId
    ]);

    $updated = getInventoryByProductId($pdo, $productId);
    $newQuantity = $updated['Stock_Quantity'] ?? null;

    $payload = buildInventoryLogPayload(
        $previousQuantity,
        $newQuantity,
        $logOptions + ['change_source' => $logOptions['change_source'] ?? 'adjustment']
    );

    if ($payload) {
        recordInventoryStockLog($pdo, $productId, $payload);
    }

    return $stmt->rowCount();
}

// 6) Delete an inventory record by Product_ID
function deleteInventoryByProductId($pdo, $productId) {
    $stmt = $pdo->prepare("
        DELETE FROM inventory WHERE Product_ID = :product_id
    ");
    $stmt->execute([':product_id' => $productId]);
    return $stmt->rowCount();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'], $_POST['stock_quantity'])) {
    require_once 'db_connect.php';
    header('Content-Type: application/json');
    if (!$pdo) {
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }

    $productId = (int)$_POST['product_id'];
    $stockInput = trim($_POST['stock_quantity']);
    $stockQuantity = ($stockInput === '') ? null : (int)$stockInput;
    updateInventoryStock($pdo, $productId, $stockQuantity, [
        'reference_type' => 'admin_panel',
        'note' => 'Admin inventory adjustment'
    ]);

    $rows = getInventoryWithProducts($pdo);
    $data = [];
    foreach ($rows as $row) {
        $category = $row['Category'] ?? 'Uncategorized';
        $data[$category][] = [
            'id' => $row['Product_ID'],
            'name' => $row['Name'],
            'stock' => $row['Stock_Quantity']
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

?>
