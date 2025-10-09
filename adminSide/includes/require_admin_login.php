<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$adminSession = [
    'id' => $_SESSION['admin_user_id'] ?? null,
    'name' => $_SESSION['admin_name'] ?? 'Admin',
    'email' => $_SESSION['admin_email'] ?? ''
];
