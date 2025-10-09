<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse(true);
requirePostRequest('Only POST requests are allowed');

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/user_functions.php';
require_once __DIR__ . '/store_staff_functions.php';

requireDatabaseConnection($pdo);

$rawInput = file_get_contents('php://input');
$data = [];
if ($rawInput !== false && $rawInput !== '') {
    $decoded = json_decode($rawInput, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $data = $decoded;
    }
}

if (!$data) {
    $data = $_POST;
}

$email = isset($data['email']) ? trim((string) $data['email']) : '';
$password = isset($data['password']) ? (string) $data['password'] : '';

if ($email === '' || $password === '') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Email and password are required.'
    ], 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ], 422);
}

$user = authenticateUser($pdo, $email, $password);
if (!$user) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Invalid email or password.'
    ], 401);
}

$staffRecord = getStoreStaffByUserId($pdo, (int) $user['User_ID']);
if (!$staffRecord) {
    sendJsonResponse([
        'success' => false,
        'message' => 'You do not have permission to access the admin portal.'
    ], 403);
}

session_regenerate_id(true);
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_user_id'] = (int) $user['User_ID'];
$_SESSION['admin_name'] = $user['Name'] ?? 'Admin';
$_SESSION['admin_email'] = $user['Email'] ?? $email;

sendJsonResponse([
    'success' => true,
    'message' => 'Login successful.'
]);
