<?php

$localConfig = __DIR__ . '/db.local.php';

if (file_exists($localConfig)) {
    require $localConfig;
}

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'cyberquest_db';
$username = getenv('DB_USER') ?: 'cyberquest_user';
$password = getenv('DB_PASSWORD') ?: 'CyberQuest123!';

if ($password === false) {
    die("Database configuration is missing.");
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
