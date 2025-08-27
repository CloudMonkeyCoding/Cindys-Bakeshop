<?php
// 1) Add a store staff member
function addStoreStaff($pdo, $userId) {
    $stmt = $pdo->prepare("
        INSERT INTO store_staff (User_ID)
        VALUES (:user_id)
    ");
    $stmt->execute([':user_id' => $userId]);
    return $pdo->lastInsertId();
}

// 2) Get all store staff
function getAllStoreStaff($pdo) {
    $stmt = $pdo->query("SELECT * FROM store_staff");
    return $stmt->fetchAll();
}

// 3) Get store staff by ID
function getStoreStaffById($pdo, $staffId) {
    $stmt = $pdo->prepare("
        SELECT * FROM store_staff WHERE Store_Staff_ID = :staff_id
    ");
    $stmt->execute([':staff_id' => $staffId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 4) Get store staff by User_ID
function getStoreStaffByUserId($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT * FROM store_staff WHERE User_ID = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 5) Delete store staff by Store_Staff_ID
function deleteStoreStaffById($pdo, $staffId) {
    $stmt = $pdo->prepare("
        DELETE FROM store_staff WHERE Store_Staff_ID = :staff_id
    ");
    $stmt->execute([':staff_id' => $staffId]);
    return $stmt->rowCount();
}

// 6) Delete store staff by User_ID
function deleteStoreStaffByUserId($pdo, $userId) {
    $stmt = $pdo->prepare("
        DELETE FROM store_staff WHERE User_ID = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->rowCount();
}
?>
