<?php
// Central PDO connection. Include this in any file that touches the database.
function get_db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $host = 'localhost';
  $db   = 'auction_db';
  $user = 'auction_user';
  $pass = 'auction_password';
  $charset = 'utf8mb4';

  $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];

  $pdo = new PDO($dsn, $user, $pass, $options);
  return $pdo;
}
