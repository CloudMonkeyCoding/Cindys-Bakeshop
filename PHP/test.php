<?php
require 'db_connect.php';
if (!$pdo) {
    die('Database connection failed');
}
require 'user_functions.php';

$message = "";

// === Handle add user form ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $warning_count = $_POST['warning_count'];

    try {
        $newUserId = addUser($pdo, $name, $email, $password, $address, $warning_count);
        $message = "✅ Added user with ID: " . htmlspecialchars($newUserId);
    } catch (PDOException $e) {
        $message = "❌ Error adding user: " . $e->getMessage();
    }
}

// === Handle delete user form ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_user'])) {
    $userIdToDelete = $_POST['user_id'];

    try {
        $deletedCount = deleteUserById($pdo, $userIdToDelete);
        if ($deletedCount > 0) {
            $message = "✅ User with ID $userIdToDelete deleted successfully.";
        } else {
            $message = "ℹ️ No user found with ID $userIdToDelete.";
        }
    } catch (PDOException $e) {
        $message = "❌ Error deleting user: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_all_users'])) {
    try {
        $deletedCount = deleteAllUsers($pdo);
        $message = "✅ Deleted $deletedCount user(s) from the User table.";
    } catch (PDOException $e) {
        $message = "❌ Error deleting all users: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_user'])) {
    $userId = $_POST['edit_user_id'];
    $name = $_POST['edit_name'];
    $email = $_POST['edit_email'];
    $address = $_POST['edit_address'];
    $warning_count = $_POST['edit_warning_count'];

    try {
        $updatedCount = updateUserById($pdo, $userId, $name, $email, $address, $warning_count);
        if ($updatedCount > 0) {
            $message = "✅ User with ID $userId updated successfully.";
        } else {
            $message = "ℹ️ No user found with ID $userId or no changes made.";
        }
    } catch (PDOException $e) {
        $message = "❌ Error updating user: " . $e->getMessage();
    }
}

// === Handle update user password ===
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_password'])) {
    $userId = $_POST['pass_user_id'];
    $newPassword = $_POST['new_password'];

    try {
        $updatedCount = updateUserPasswordById($pdo, $userId, $newPassword);
        if ($updatedCount > 0) {
            $message = "✅ Password updated for user ID $userId.";
        } else {
            $message = "ℹ️ No user found with ID $userId.";
        }
    } catch (PDOException $e) {
        $message = "❌ Error updating password: " . $e->getMessage();
    }
}
// === Fetch all users ===
$users = getAllUsers($pdo);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test User Functions</title>
</head>
<body>
    <h2>Test User Functions</h2>

    <?php if ($message): ?>
        <p><strong><?php echo $message; ?></strong></p>
    <?php endif; ?>

    <h3>Add User</h3>
    <form method="POST" action="">
        <input type="hidden" name="add_user" value="1">
        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Address:</label><br>
        <input type="text" name="address" required><br><br>

        <label>Warning Count:</label><br>
        <input type="number" name="warning_count" value="0" min="0" required><br><br>

        <button type="submit">Add User</button>
    </form>

    <h3>Delete User by ID</h3>
    <form method="POST" action="">
        <input type="hidden" name="delete_user" value="1">
        <label>User ID to delete:</label><br>
        <input type="number" name="user_id" required><br><br>

        <button type="submit">Delete User</button>
    </form>

    <h3>Delete All Users</h3>
    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete ALL users? This cannot be undone!');">
        <input type="hidden" name="delete_all_users" value="1">
        <button type="submit" style="color: red;">Delete All Users</button>
    </form>

    <h3>Edit User Info (No Password)</h3>
    <form method="POST" action="">
        <input type="hidden" name="update_user" value="1">

        <label>User ID to edit:</label><br>
        <input type="number" name="edit_user_id" required><br><br>

        <label>New Name:</label><br>
        <input type="text" name="edit_name" required><br><br>

        <label>New Email:</label><br>
        <input type="email" name="edit_email" required><br><br>

        <label>New Address:</label><br>
        <input type="text" name="edit_address" required><br><br>

        <label>New Warning Count:</label><br>
        <input type="number" name="edit_warning_count" min="0" required><br><br>

        <button type="submit">Update User Info</button>
    </form>

    <h3>Change User Password</h3>
    <form method="POST" action="">
        <input type="hidden" name="update_password" value="1">

        <label>User ID:</label><br>
        <input type="number" name="pass_user_id" required><br><br>

        <label>New Password:</label><br>
        <input type="password" name="new_password" required><br><br>

        <button type="submit">Update Password</button>
    </form>

    <h3>All Users</h3>
    <?php if (empty($users)): ?>
        <p>No users found.</p>
    <?php else: ?>
        <?php foreach ($users as $user): ?>
            <div style="margin-bottom: 15px;">
                <strong>User_ID:</strong> <?php echo htmlspecialchars($user['User_ID']); ?><br>
                <strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?><br>
                <strong>Address:</strong> <?php echo htmlspecialchars($user['Address']); ?><br>
                <strong>Warnings:</strong> <?php echo htmlspecialchars($user['Warning_Count']); ?><br>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
