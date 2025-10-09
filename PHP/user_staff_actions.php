<?php
require_once __DIR__ . '/action_helpers.php';

startJsonResponse();
requirePostRequest();

require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/store_staff_functions.php';
require_once __DIR__ . '/user_functions.php';

requireDatabaseConnection($pdo);

$action = $_POST['action'] ?? '';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = !empty($_SESSION['admin_logged_in']);
if (!$isLoggedIn) {
    sendJsonResponse(['success' => false, 'message' => 'Authentication required.'], 401);
}

$currentAdminId = isset($_SESSION['admin_user_id']) ? (int) $_SESSION['admin_user_id'] : 0;
$currentIsSuperAdmin = !empty($_SESSION['admin_is_super_admin']);

$supportedActions = ['mark_employee', 'remove_employee', 'promote_super_admin', 'demote_super_admin'];

if (!in_array($action, $supportedActions, true)) {
    sendJsonResponse(['success' => false, 'message' => 'Unsupported action'], 400);
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) {
    sendJsonResponse(['success' => false, 'message' => 'Invalid or missing user ID'], 400);
}

$user = getUserById($pdo, $userId);
if (!$user) {
    sendJsonResponse(['success' => false, 'message' => 'User not found'], 404);
}

$existingStaff = getStoreStaffByUserId($pdo, $userId);
$isTargetSuperAdmin = $existingStaff && !empty($existingStaff['Is_Super_Admin']) && (int) $existingStaff['Is_Super_Admin'] === 1;

if (in_array($action, ['promote_super_admin', 'demote_super_admin'], true) && !$currentIsSuperAdmin) {
    sendJsonResponse(['success' => false, 'message' => 'Only a super admin can modify super admin access.'], 403);
}

if ($action === 'demote_super_admin' && $userId === $currentAdminId) {
    sendJsonResponse(['success' => false, 'message' => 'You cannot remove your own super admin access.'], 400);
}

if ($action === 'mark_employee' && !$currentIsSuperAdmin) {
    sendJsonResponse(['success' => false, 'message' => 'Only the super admin can mark users as employees.'], 403);
}

if ($action === 'remove_employee' && !$currentIsSuperAdmin) {
    sendJsonResponse(['success' => false, 'message' => 'Only the super admin can remove employee status.'], 403);
}

try {
    if ($action === 'mark_employee') {
        if ($existingStaff) {
            sendJsonResponse(['success' => true, 'message' => 'User is already marked as an employee.']);
        }

        addStoreStaff($pdo, $userId);
        sendJsonResponse(['success' => true, 'message' => 'User marked as employee successfully.']);
    }

    if ($action === 'remove_employee') {
        if ($isTargetSuperAdmin) {
            sendJsonResponse(['success' => false, 'message' => 'Remove super admin access before removing this employee.'], 400);
        }
        if (!$existingStaff) {
            sendJsonResponse(['success' => true, 'message' => 'User is not currently marked as an employee.']);
        }

        deleteStoreStaffByUserId($pdo, $userId);
        sendJsonResponse(['success' => true, 'message' => 'Employee status removed successfully.']);
    }

    if ($action === 'promote_super_admin') {
        setStoreStaffSuperAdmin($pdo, $userId, true);
        sendJsonResponse(['success' => true, 'message' => 'Super admin updated successfully.', 'super_admin_user_id' => $userId]);
    }

    if ($action === 'demote_super_admin') {
        if (!$isTargetSuperAdmin) {
            sendJsonResponse(['success' => true, 'message' => 'User is not currently a super admin.']);
        }

        setStoreStaffSuperAdmin($pdo, $userId, false);
        sendJsonResponse(['success' => true, 'message' => 'Super admin access removed.', 'super_admin_user_id' => null]);
    }
} catch (PDOException $exception) {
    sendJsonResponse([
        'success' => false,
        'message' => 'Failed to update employee status.',
    ], 500);
}
