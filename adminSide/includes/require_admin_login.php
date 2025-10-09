<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['admin_logged_in'])) {
    $maxIdleSeconds = 600; // 10 minutes
    $lastActivity = $_SESSION['admin_last_activity'] ?? null;
    $now = time();

    if ($lastActivity !== null && ($now - (int) $lastActivity) > $maxIdleSeconds) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['admin_timeout_message'] = 'You have been signed out due to inactivity.';
        header('Location: login.php');
        exit;
    }

    $_SESSION['admin_last_activity'] = $now;
} else {
    header('Location: login.php');
    exit;
}

$adminSession = [
    'id' => $_SESSION['admin_user_id'] ?? null,
    'name' => $_SESSION['admin_name'] ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? ''
];
