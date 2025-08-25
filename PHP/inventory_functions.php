<?php
// 1) Add inventory entry for a product
function addInventory($pdo, $productId, $stockQuantity) {
    $stmt = $pdo->prepare("
        INSERT INTO Inventory (Product_ID, Stock_Quantity)
        VALUES (:product_id, :stock_quantity)
    ");
    $stmt->execute([
        ':product_id' => $productId,
        ':stock_quantity' => $stockQuantity
    ]);
    return $pdo->lastInsertId();
}

// 2) Get inventory record by Product_ID
function getInventoryByProductId($pdo, $productId) {
    $stmt = $pdo->prepare("
        SELECT * FROM Inventory WHERE Product_ID = :product_id
    ");
    $stmt->execute([':product_id' => $productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 3) Get all inventory records
function getAllInventory($pdo) {
    $stmt = $pdo->query("SELECT * FROM Inventory");
    return $stmt->fetchAll();
}

// 3b) Get inventory with product details
function getInventoryWithProducts($pdo) {
    $stmt = $pdo->query(
        "SELECT p.Product_ID, p.Name, p.Category, i.Stock_Quantity\n" .
        "FROM Inventory i\n" .
        "JOIN Product p ON i.Product_ID = p.Product_ID"
    );
    return $stmt->fetchAll();
}

// 4) Update stock quantity for a product
function updateInventoryStock($pdo, $productId, $stockQuantity) {
    $stmt = $pdo->prepare("
        UPDATE Inventory
        SET Stock_Quantity = :stock_quantity
        WHERE Product_ID = :product_id
    ");
    $stmt->execute([
        ':stock_quantity' => $stockQuantity,
        ':product_id' => $productId
    ]);
    return $stmt->rowCount();
}

// 5) Adjust stock quantity (+/-)
function adjustInventoryStock($pdo, $productId, $quantityChange) {
    $stmt = $pdo->prepare("
        UPDATE Inventory
        SET Stock_Quantity = Stock_Quantity + :quantity_change
        WHERE Product_ID = :product_id
    ");
    $stmt->execute([
        ':quantity_change' => $quantityChange,
        ':product_id' => $productId
    ]);
    return $stmt->rowCount();
}

// 6) Delete an inventory record by Product_ID
function deleteInventoryByProductId($pdo, $productId) {
    $stmt = $pdo->prepare("
        DELETE FROM Inventory WHERE Product_ID = :product_id
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
    updateInventoryStock($pdo, $productId, $stockQuantity);

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
