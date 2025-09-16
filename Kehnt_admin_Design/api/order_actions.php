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
require_once '../../PHP/order_cancellation_functions.php';

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

switch ($action) {
    case 'update_status':
        $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'Pending';
        if (!$orderId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }
        updateOrderStatus($pdo, $orderId, $status);
        echo json_encode(['success' => true, 'status' => $status]);
        break;

    case 'approve_cancellation':
    case 'reject_cancellation':
        $cancelId = filter_input(INPUT_POST, 'cancel_id', FILTER_VALIDATE_INT);
        if (!$cancelId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid cancellation ID']);
            exit;
        }
        $newStatus = $action === 'approve_cancellation' ? 'Approved' : 'Rejected';
        updateOrderCancellationStatus($pdo, $cancelId, $newStatus);
        echo json_encode(['success' => true, 'status' => $newStatus]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
