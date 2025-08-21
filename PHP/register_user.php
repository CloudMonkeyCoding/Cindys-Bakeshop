<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$name = $input['fullName'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$faceImage = $input['faceImage'] ?? '';

if (!$name || !$email || !$password || !$faceImage) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$parts = explode(',', $faceImage);
if (count($parts) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image data']);
    exit;
}

$imageData = base64_decode($parts[1]);
$facesDir = __DIR__ . '/../user_faces';
if (!is_dir($facesDir)) {
    mkdir($facesDir, 0777, true);
}
$filename = uniqid('face_', true) . '.png';
$filepath = $facesDir . '/' . $filename;
file_put_contents($filepath, $imageData);
$relativePath = 'user_faces/' . $filename;

try {
    $userId = addUser($pdo, $name, $email, $password, '', 0, $relativePath);
    echo json_encode(['success' => true, 'userId' => $userId, 'imagePath' => $relativePath]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to register user']);
}
?>
