<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse(true);
requirePostRequest();

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/delivery_functions.php';
require_once __DIR__ . '/audit_log_functions.php';

requireDatabaseConnection($pdo);

$token = $_POST['csrf_token'] ?? '';
requireCsrfToken($token);

$action = $_POST['action'] ?? '';

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

record_api_call($pdo, 'delivery_actions', [
    'action' => $action,
    'actor_id' => $actorId,
    'actor_email' => $actorEmail,
]);
if ($action !== 'update') {
    sendJsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

$deliveryId = filter_input(INPUT_POST, 'delivery_id', FILTER_VALIDATE_INT);
if (!$deliveryId) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid delivery ID'], 400);
}

$status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'Pending';
$date = filter_input(INPUT_POST, 'delivery_date', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
$personnel = filter_input(INPUT_POST, 'delivery_personnel', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;

updateDelivery($pdo, $deliveryId, $status, $date, $personnel);
$updated = getDeliveryById($pdo, $deliveryId);

sendJsonResponse(['success' => true, 'delivery' => $updated]);
