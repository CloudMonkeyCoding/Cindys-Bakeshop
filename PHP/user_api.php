<?php
require_once 'db_connect.php';
require_once 'user_functions.php';

header('Content-Type: application/json');

function normalizeFacePath($path) {
    if (!$path) {
        return null;
    }
    return $path[0] === '/' ? $path : '/' . ltrim($path, '/');
}

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
            $path = normalizeFacePath($user['Face_Image_Path'] ?? null);
            echo json_encode(['face_image_path' => $path]);
        } else {
            $userId = (int)($_GET['user_id'] ?? 0);
            $user = getUserById($pdo, $userId);
            $path = normalizeFacePath($user['Face_Image_Path'] ?? null);
            echo json_encode(['face_image_path' => $path]);
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
    case 'get_profile':
        $email = $_GET['email'] ?? '';
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Email required']);
            break;
        }
        $user = getUserByEmail($pdo, $email);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            break;
        }
        $fullName = $user['Name'] ?? '';
        $parts = preg_split('/\s+/', trim($fullName));
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
        $path = normalizeFacePath($user['Face_Image_Path'] ?? null);
        echo json_encode([
            'user_id' => $user['User_ID'],
            'name' => $fullName,
            'first_name' => $first,
            'last_name' => $last,
            'address' => $user['Address'],
            'face_image_path' => $path
        ]);
        break;
    case 'set_profile':
        $email = $_POST['email'] ?? '';
        $name = $_POST['name'] ?? '';
        $address = $_POST['address'] ?? '';
        if (!$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Email required']);
            break;
        }
        $user = getUserByEmail($pdo, $email);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            break;
        }
        updateUserNameAddress($pdo, $user['User_ID'], $name, $address);
        echo json_encode(['updated' => true]);
        break;
    case 'update_profile':
        $firstName = $_POST['first_name'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$firstName || !$lastName || !$email) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            break;
        }

        $user = getUserByEmail($pdo, $email);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            break;
        }

        $fullName = trim($firstName . ' ' . $lastName);
        $params = [
            ':name' => $fullName,
            ':id' => $user['User_ID']
        ];
        $sql = 'UPDATE User SET Name = :name';

        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ', Password = :password';
            $params[':password'] = $hashed;
        }

        $relativePath = null;
        $oldFace = $user['Face_Image_Path'] ?? null;
        $oldPath = $oldFace ? __DIR__ . '/../' . ltrim($oldFace, '/') : null;
        if (!empty($_FILES['profile_picture']['tmp_name'])) {
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($_FILES['profile_picture']['size'] > $maxSize) {
                http_response_code(400);
                echo json_encode(['error' => 'Profile picture must be 5MB or less']);
                break;
            }

            $facesDir = __DIR__ . '/../user_faces';
            if (!is_dir($facesDir)) {
                mkdir($facesDir, 0777, true);
            }
            $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('face_', true) . '.' . ($ext ?: 'png');
            $filepath = $facesDir . '/' . $filename;
            if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save profile picture']);
                break;
            }
            $relativePath = '/user_faces/' . $filename;
            $sql .= ', Face_Image_Path = :face';
            $params[':face'] = $relativePath;
            if ($oldPath && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        $sql .= ' WHERE User_ID = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $existing = normalizeFacePath($relativePath ?? $oldFace);

        echo json_encode([
            'message' => 'Profile updated successfully',
            'face_image_path' => $existing
        ]);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
