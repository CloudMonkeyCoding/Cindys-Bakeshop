<?php
require_once 'db_connect.php';
require_once 'favorite_functions.php';
require_once 'user_functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list':
        $email = $_GET['email'] ?? '';
        if ($email) {
            $user = getUserByEmail($pdo, $email);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            $userId = (int)$user['User_ID'];
        } else {
            $userId = (int)($_GET['user_id'] ?? 0);
        }
        $favorites = getFavoritesByUserId($pdo, $userId);
        echo json_encode($favorites);
        break;
    case 'add':
        $email = $_POST['email'] ?? '';
        if ($email) {
            $user = getUserByEmail($pdo, $email);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            $userId = (int)$user['User_ID'];
        } else {
            $userId = (int)($_POST['user_id'] ?? 0);
        }
        $productId = (int)($_POST['product_id'] ?? 0);
        $id = addFavorite($pdo, $userId, $productId);
        echo json_encode(['favorite_id' => $id]);
        break;
    case 'remove':
        $favoriteId = (int)($_POST['favorite_id'] ?? 0);
        $deleted = deleteFavorite($pdo, $favoriteId);
        echo json_encode(['deleted' => $deleted]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
