<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse();
requirePostRequest('Method not allowed');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/blacklist_functions.php';

requireDatabaseConnection($pdo);

$request = getRequestPayload();
$action = isset($request['action']) ? (string)$request['action'] : '';
if ($action === 'unblock') {
    $id = isset($request['blacklist_id']) ? (int)$request['blacklist_id'] : 0;
    if ($id > 0) {
        $deleted = deleteBlacklistById($pdo, $id);
        if ($deleted) {
            sendJsonResponse(['success' => true]);
        }
        sendJsonResponse(['success' => false, 'message' => 'Blacklist entry not found'], 404);
    }
    sendJsonResponse(['success' => false, 'message' => 'Invalid ID'], 400);
}

sendJsonResponse(['success' => false, 'message' => 'Invalid action'], 400);
