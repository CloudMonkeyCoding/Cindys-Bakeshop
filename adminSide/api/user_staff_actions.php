<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

require_once '../../PHP/db_connect.php';
require_once '../../PHP/store_staff_functions.php';
require_once '../../PHP/user_functions.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'mark_employee') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported action']);
    exit;
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or missing user ID']);
    exit;
}

$user = getUserById($pdo, $userId);
if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

$existingStaff = getStoreStaffByUserId($pdo, $userId);
if ($existingStaff) {
    echo json_encode(['success' => true, 'message' => 'User is already marked as an employee.']);
    exit;
}

try {
    addStoreStaff($pdo, $userId);
    echo json_encode(['success' => true, 'message' => 'User marked as employee successfully.']);
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update employee status.',
    ]);
}
