<?php
require_once __DIR__ . '/action_helpers.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/notification_functions.php';

header('Content-Type: application/json');
$request = getRequestPayload();
$action = $_GET['action'] ?? $request['action'] ?? '';

switch ($action) {
    case 'unread':
        $notifications = getUnreadNotifications($pdo);
        $ids = array_column($notifications, 'Notification_ID');
        if (!empty($ids)) {
            markNotificationsAsRead($pdo, $ids);
        }
        echo json_encode($notifications);
        break;
    case 'all':
    case '':
        $notifications = getAllNotifications($pdo);
        $unreadIds = array_column(
            array_filter($notifications, function ($n) {
                return $n['Is_Read'] == 0;
            }),
            'Notification_ID'
        );
        if (!empty($unreadIds)) {
            markNotificationsAsRead($pdo, $unreadIds);
        }
        echo json_encode($notifications);
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>
