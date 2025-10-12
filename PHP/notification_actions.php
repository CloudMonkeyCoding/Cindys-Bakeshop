<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse();
requirePostRequest();

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/notification_functions.php';
require_once __DIR__ . '/audit_log_functions.php';

requireDatabaseConnection($pdo);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

record_api_call($pdo, 'notification_actions', [
    'action' => $action,
]);

if ($action !== 'mark_read') {
    sendJsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

$ids = isset($input['ids']) && is_array($input['ids']) ? array_map('intval', $input['ids']) : [];
if (!$ids) {
    sendJsonResponse(['success' => true]);
}

markNotificationsAsRead($pdo, $ids);
sendJsonResponse(['success' => true]);
