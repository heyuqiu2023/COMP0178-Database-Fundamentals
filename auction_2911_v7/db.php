<?php
// db.php
$DB_HOST = 'localhost';
$DB_NAME = 'auction_db';
$DB_USER = 'root';
$DB_PASS = '';

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host={$GLOBALS['DB_HOST']};dbname={$GLOBALS['DB_NAME']};charset=utf8mb4";
        $pdo = new PDO($dsn, $GLOBALS['DB_USER'], $GLOBALS['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
