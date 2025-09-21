<?php
// config/connect.php
// Centralised MySQL connection helper for the Origin Driving project.

$DB_CONFIG = [
    'host' => '127.0.0.1',
    'port' => 3308,
    'dbname' => 'origin_driving',
    'username' => 'root',
    'password' => '', // Default XAMPP root password is empty; adjust if needed.
    'charset' => 'utf8mb4',
];

/**
 * Returns a shared PDO connection configured for the project.
 * Remember to import origin_driving_schema.sql before using.
 */
function db(): PDO
{
    static $pdo = null;
    global $DB_CONFIG;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $DB_CONFIG['host'],
        $DB_CONFIG['port'],
        $DB_CONFIG['dbname'],
        $DB_CONFIG['charset']
    );

    try {
        $pdo = new PDO($dsn, $DB_CONFIG['username'], $DB_CONFIG['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    }

    return $pdo;
}
