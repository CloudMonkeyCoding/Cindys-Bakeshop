<?php
require 'db_connect.php';
if (!$pdo) {
    die('Database connection failed');
}

$stmt = $pdo->query("SELECT * FROM User");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "Name: " . htmlspecialchars($user['Name']) . "<br>";
    echo "Email: " . htmlspecialchars($user['Email']) . "<br><br>";
}
?>
