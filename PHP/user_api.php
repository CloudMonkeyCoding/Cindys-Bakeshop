<?php
require_once __DIR__ . '/action_helpers.php';
require_once 'db_connect.php';
require_once 'user_functions.php';

header('Content-Type: application/json');

function normalizeFacePath($path) {
    if (!$path) {
        return null;
    }
    return $path[0] === '/' ? $path : '/' . ltrim($path, '/');
}

$request = getRequestPayload();
$action = $_GET['action'] ?? $request['action'] ?? '';

switch ($action) {
    case 'get_face':
        $emailValue = $request['email'] ?? $_GET['email'] ?? '';
        $email = is_string($emailValue) ? $emailValue : '';
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
            $userId = isset($request['user_id']) ? (int)$request['user_id'] : (int)($_GET['user_id'] ?? 0);
            $user = getUserById($pdo, $userId);
            $path = normalizeFacePath($user['Face_Image_Path'] ?? null);
            echo json_encode(['face_image_path' => $path]);
        }
        break;
    case 'set_face':
        $emailValue = $request['email'] ?? '';
        $email = is_string($emailValue) ? $emailValue : '';
        if ($email) {
            $user = getUserByEmail($pdo, $email);
            if (!$user) {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
                break;
            }
            $userId = (int)$user['User_ID'];
        } else {
            $userId = isset($request['user_id']) ? (int)$request['user_id'] : 0;
        }
        $pathValue = $request['face_image_path'] ?? '';
        $path = is_string($pathValue) ? $pathValue : '';
        $stmt = $pdo->prepare('UPDATE user SET Face_Image_Path = :path WHERE User_ID = :id');
        $stmt->execute([':path' => $path, ':id' => $userId]);
        echo json_encode(['updated' => $stmt->rowCount()]);
        break;
    case 'get_profile':
        $emailValue = $request['email'] ?? $_GET['email'] ?? '';
        $email = is_string($emailValue) ? $emailValue : '';
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
        $emailValue = $request['email'] ?? '';
        $nameValue = $request['name'] ?? '';
        $addressValue = $request['address'] ?? '';
        $email = is_string($emailValue) ? $emailValue : '';
        $name = is_string($nameValue) ? $nameValue : '';
        $address = is_string($addressValue) ? $addressValue : '';
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
        $firstNameValue = $request['first_name'] ?? '';
        $lastNameValue = $request['last_name'] ?? '';
        $emailValue = $request['email'] ?? '';
        $passwordValue = $request['password'] ?? '';

        $firstName = is_string($firstNameValue) ? $firstNameValue : '';
        $lastName = is_string($lastNameValue) ? $lastNameValue : '';
        $email = is_string($emailValue) ? $emailValue : '';
        $password = is_string($passwordValue) ? $passwordValue : '';

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
        $sql = 'UPDATE user SET Name = :name';

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
