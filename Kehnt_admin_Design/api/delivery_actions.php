<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

require_once '../../PHP/db_connect.php';
require_once '../../PHP/delivery_functions.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action !== 'update') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

$deliveryId = filter_input(INPUT_POST, 'delivery_id', FILTER_VALIDATE_INT);
if (!$deliveryId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid delivery ID']);
    exit;
}

$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'Pending';
$date = filter_input(INPUT_POST, 'delivery_date', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$personnel = filter_input(INPUT_POST, 'delivery_personnel', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

updateDelivery($pdo, $deliveryId, $status, $date, $personnel);
$updated = getDeliveryById($pdo, $deliveryId);

echo json_encode(['success' => true, 'delivery' => $updated]);
