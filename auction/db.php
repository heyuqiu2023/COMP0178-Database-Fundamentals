<?php
$host = "localhost";
$dbname = "auction";
$username = "root";
$password = ""; // XAMPP 默认无密码

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", 
                   $username, 
                   $password,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
?>

