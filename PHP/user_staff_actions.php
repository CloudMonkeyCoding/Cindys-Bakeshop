<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse();
requirePostRequest();

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/store_staff_functions.php';
require_once __DIR__ . '/user_functions.php';

requireDatabaseConnection($pdo);

$action = $_POST['action'] ?? '';
if (!in_array($action, ['mark_employee', 'remove_employee'], true)) {
    sendJsonResponse(['success' => false, 'message' => 'Unsupported action'], 400);
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid or missing user ID'], 400);
}

$user = getUserById($pdo, $userId);
if (!$user) {
    sendJsonResponse(['success' => false, 'message' => 'User not found'], 404);
}

$existingStaff = getStoreStaffByUserId($pdo, $userId);

try {
    if ($action === 'mark_employee') {
        if ($existingStaff) {
            sendJsonResponse(['success' => true, 'message' => 'User is already marked as an employee.']);
        }

        addStoreStaff($pdo, $userId);
        sendJsonResponse(['success' => true, 'message' => 'User marked as employee successfully.']);
    }

    if ($action === 'remove_employee') {
        if (!$existingStaff) {
            sendJsonResponse(['success' => true, 'message' => 'User is not currently marked as an employee.']);
        }

        deleteStoreStaffByUserId($pdo, $userId);
        sendJsonResponse(['success' => true, 'message' => 'Employee status removed successfully.']);
    }
} catch (PDOException $exception) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Failed to update employee status.',
    ], 500);
}
