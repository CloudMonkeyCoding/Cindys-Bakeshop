<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/favorite_functions.php';
require_once __DIR__ . '/user_request_helpers.php';

startJsonResponse();
requireDatabaseConnection($pdo);

$request = getRequestPayload();
$action = $_GET['action'] ?? $request['action'] ?? '';

switch ($action) {
    case 'list':
        $context = array_merge($_GET, $request);
        [$userId] = resolveUserContext($pdo, $context, ['allowMissing' => true]);
        if ($userId <= 0) {
            sendJsonResponse([]);
        }

        $favorites = getFavoritesByUserId($pdo, $userId);
        sendJsonResponse($favorites);

    case 'add':
        [$userId] = resolveUserContext($pdo, $request);
        $productId = isset($request['product_id']) ? (int)$request['product_id'] : 0;
        $id = addFavorite($pdo, $userId, $productId);
        sendJsonResponse(['favorite_id' => $id]);

    case 'remove':
        $favoriteId = isset($request['favorite_id']) ? (int)$request['favorite_id'] : 0;
        $deleted = deleteFavorite($pdo, $favoriteId);
        sendJsonResponse(['deleted' => $deleted]);

    default:
        sendJsonResponse(['error' => 'Invalid action'], 400);
}
?>
