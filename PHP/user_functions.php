<?php
    function getAllUsers($pdo) {
        $stmt = $pdo->query("SELECT * FROM User");
        return $stmt->fetchAll();
    }

function addUser($pdo, $name, $email, $password, $address, $warning_count = 0, $face_image_path = null) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL query
    $stmt = $pdo->prepare("INSERT INTO User (Name, Email, Password, Address, Warning_Count, Face_Image_Path)
                            VALUES (:name, :email, :password, :address, :warning_count, :face_image_path)");

        // Execute with data
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
        ':password' => $hashed_password,
        ':address' => $address,
        ':warning_count' => $warning_count,
        ':face_image_path' => $face_image_path
    ]);

        return $pdo->lastInsertId(); // returns the new User_ID
    }

    function deleteUserById($pdo, $userId) {
        $stmt = $pdo->prepare("DELETE FROM User WHERE User_ID = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->rowCount(); // returns number of rows deleted (0 or 1)
    }

    function deleteAllUsers($pdo) {
        $stmt = $pdo->prepare("DELETE FROM User");
        $stmt->execute();
        return $stmt->rowCount(); // returns number of rows deleted
    }

    function updateUserById($pdo, $userId, $name, $email, $address, $warning_count) {
        $stmt = $pdo->prepare("
            UPDATE User
            SET Name = :name,
                Email = :email,
                Address = :address,
                Warning_Count = :warning_count
            WHERE User_ID = :user_id
        ");

        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':address' => $address,
            ':warning_count' => $warning_count,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount(); // number of rows updated
    }

    function updateUserPasswordById($pdo, $userId, $newPassword) {
        $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE User
            SET Password = :password
            WHERE User_ID = :user_id
        ");

        $stmt->execute([
            ':password' => $hashed_password,
            ':user_id' => $userId
        ]);

        return $stmt->rowCount(); // number of rows updated
    }

    function getUserById($pdo, $userId) {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE User_ID = :user_id");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // returns single user or false
    }

    function getUserByEmail($pdo, $email) {
        $stmt = $pdo->prepare("SELECT * FROM User WHERE Email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function checkEmailExists($pdo, $email) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM User WHERE Email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    function authenticateUser($pdo, $email, $password) {
        $user = getUserByEmail($pdo, $email);
        if ($user && password_verify($password, $user['Password'])) {
            return $user; // Valid credentials
        }
        return false; // Invalid
    }

    function countUsers($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM User");
        return $stmt->fetchColumn();
    }

    function searchUsers($pdo, $keyword) {
        $stmt = $pdo->prepare("
            SELECT * FROM User
            WHERE Name LIKE :kw OR Email LIKE :kw
        ");
        $stmt->execute([':kw' => "%$keyword%"]);
        return $stmt->fetchAll();
    }

    function incrementWarningCount($pdo, $userId) {
        $stmt = $pdo->prepare("
            UPDATE User
            SET Warning_Count = Warning_Count + 1
            WHERE User_ID = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->rowCount();
    }
?>