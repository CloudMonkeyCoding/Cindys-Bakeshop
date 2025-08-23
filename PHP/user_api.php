<?php
require_once 'db_connect.php';
require_once 'user_functions.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_face':
        $email = $_GET['email'] ?? '';
        if ($email) {
            $user = getUserByEmail($pdo, $email);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            echo json_encode(['face_image_path' => $user['Face_Image_Path'] ?? null]);
        } else {
            $userId = (int)($_GET['user_id'] ?? 0);
            $user = getUserById($pdo, $userId);
            echo json_encode(['face_image_path' => $user['Face_Image_Path'] ?? null]);
        }
        break;
    case 'set_face':
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
        $path = $_POST['face_image_path'] ?? '';
        $stmt = $pdo->prepare('UPDATE User SET Face_Image_Path = :path WHERE User_ID = :id');
        $stmt->execute([':path' => $path, ':id' => $userId]);
        echo json_encode(['updated' => $stmt->rowCount()]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
