<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/blacklist_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);
    if ($action === 'unblock') {
        $id = filter_input(INPUT_POST, 'blacklist_id', FILTER_VALIDATE_INT);
        if ($id) {
            $deleted = deleteBlacklistById($pdo, $id);
            if ($deleted) {
                echo json_encode(['success' => true]);
                exit;
            }
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Blacklist entry not found']);
            exit;
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
