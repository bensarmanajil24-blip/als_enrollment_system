<?php
declare(strict_types=1);

$host = '127.0.0.1';
$dbname = 'als_enrollment_db';
$dbuser = 'root';
$dbpass = ''; // Default XAMPP MySQL password is usually blank. Change if needed.

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        $options
    );
} catch (PDOException $e) {
    // Attempt fallback to localhost if 127.0.0.1 fails
    try {
        $pdo = new PDO(
            "mysql:host=localhost;dbname={$dbname};charset=utf8mb4",
            $dbuser,
            $dbpass,
            $options
        );
    } catch (PDOException $fallbackException) {
        error_log($e->getMessage());
        http_response_code(500);
        $err = $e->getMessage();
        exit("Hindi makakonekta sa database.<br><br><strong>Detalye:</strong> " . htmlspecialchars($err) . "<br><br>Siguraduhing naka-<strong>Start</strong> ang MySQL sa XAMPP Control Panel at nai-import ang <code>database.sql</code> sa phpMyAdmin (http://localhost/phpmyadmin).");
    }
}
