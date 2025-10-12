<?php
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/notification_functions.php';
require_once __DIR__ . '/audit_log_functions.php';

header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

record_api_call($pdo, 'notification_api', [
    'action' => $action,
]);

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
