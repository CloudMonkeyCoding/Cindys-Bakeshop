<?php
$host = getenv('DB_HOST') ?: 'localhost';

$db = getenv('DB_NAME');
if (!$db) {
    throw new \RuntimeException('Database name not specified. Set the DB_NAME environment variable.');
}

$user = getenv('DB_USER');
if (!$user) {
    throw new \RuntimeException('Database user not specified. Set the DB_USER environment variable.');
}

$pass = getenv('DB_PASSWORD');
if ($pass === false) {
    $pass = '';
}

$charset = getenv('DB_CHARSET') ?: 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    $pdo = null;
}
?>
