<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse();
requirePostRequest('Method not allowed');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/blacklist_functions.php';
require_once __DIR__ . '/audit_log_functions.php';

requireDatabaseConnection($pdo);

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

record_api_call($pdo, 'blacklist_api', [
    'action' => $action,
    'actor_id' => $actorId,
    'actor_email' => $actorEmail,
]);
if ($action === 'unblock') {
    $id = filter_input(INPUT_POST, 'blacklist_id', FILTER_VALIDATE_INT);
    if ($id) {
        $deleted = deleteBlacklistById($pdo, $id);
        if ($deleted) {
            sendJsonResponse(['success' => true]);
        }
        sendJsonResponse(['success' => false, 'message' => 'Blacklist entry not found'], 404);
    }
    sendJsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
}

sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
