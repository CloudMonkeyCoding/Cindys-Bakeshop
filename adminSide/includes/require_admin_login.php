<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('redirectToAdminLogin')) {
    function redirectToAdminLogin(string $messageKey, string $message): void
    {
        session_unset();
        session_destroy();
        session_start();
        session_regenerate_id(true);
        $_SESSION[$messageKey] = $message;
        header('Location: login.php');
        exit;
    }
}

if (empty($_SESSION['admin_logged_in'])) {
    redirectToAdminLogin('admin_error_message', 'Please sign in to access the admin portal.');
}

$maxIdleSeconds = 600; // 10 minutes
$lastActivity = $_SESSION['admin_last_activity'] ?? null;
$now = time();

if ($lastActivity !== null && ($now - (int) $lastActivity) > $maxIdleSeconds) {
    redirectToAdminLogin('admin_timeout_message', 'You have been signed out due to inactivity.');
}

$_SESSION['admin_last_activity'] = $now;

if (empty($_SESSION['admin_has_staff_access'])) {
    redirectToAdminLogin('admin_error_message', 'You must be an employee or super admin to access the admin portal.');
}

$adminId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;
if ($adminId > 0) {
    require_once __DIR__ . '/../../PHP/db_connect.php';

    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare('SELECT Store_Staff_ID, Is_Super_Admin FROM store_staff WHERE User_ID = :user_id LIMIT 1');
        $stmt->execute([':user_id' => $adminId]);
        $staffRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$staffRow) {
            redirectToAdminLogin('admin_error_message', 'Your admin access has been revoked.');
        }

        $isSuperAdmin = !empty($staffRow['Is_Super_Admin']) && (int) $staffRow['Is_Super_Admin'] === 1;
        $_SESSION['admin_is_super_admin'] = $isSuperAdmin;
        $_SESSION['admin_is_employee'] = !$isSuperAdmin;
        $_SESSION['admin_has_staff_access'] = true;

        if (isset($staffRow['Store_Staff_ID'])) {
            $_SESSION['admin_store_staff_id'] = (int) $staffRow['Store_Staff_ID'];
        } else {
            unset($_SESSION['admin_store_staff_id']);
        }
    }
}

$adminSession = [
    'id' => $_SESSION['admin_user_id'] ?? null,
    'name' => $_SESSION['admin_name'] ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? '',
    'is_super_admin' => !empty($_SESSION['admin_is_super_admin']),
    'is_employee' => !empty($_SESSION['admin_is_employee'])
];
