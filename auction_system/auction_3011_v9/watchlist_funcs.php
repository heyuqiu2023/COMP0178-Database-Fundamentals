<?php
session_start();
require_once 'db_connection.php';
require_once 'notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo 'Invalid request';
  exit();
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
  echo 'User not logged in';
  exit();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo 'User not identified';
  exit();
}

$function_name = isset($_POST['functionname']) ? $_POST['functionname'] : '';
$arguments = isset($_POST['arguments']) ? $_POST['arguments'] : [];
$auction_id = (is_array($arguments) && count($arguments) > 0) ? (int)$arguments[0] : 0;

if ($auction_id <= 0) {
  echo 'Invalid auction';
  exit();
}

switch ($function_name) {
  case 'add_to_watchlist':
    try {
      $stmt = $pdo->prepare("INSERT INTO Watchlist (user_id, auction_id) VALUES (?, ?)");
      $stmt->execute([$user_id, $auction_id]);
      // activity & notification (optional immediate)
      $pdo->prepare("INSERT INTO UserActivity (user_id, auction_id, action) VALUES (?, ?, 'watch')")->execute([$user_id, $auction_id]);
      echo 'success';
    } catch (PDOException $e) {
      if ($e->errorInfo[1] === 1062) { // duplicate key
        echo 'success'; // already watching is OK
      } else {
        echo 'DB error';
      }
    }
    break;

  case 'remove_from_watchlist':
    $stmt = $pdo->prepare("DELETE FROM Watchlist WHERE user_id = ? AND auction_id = ?");
    $stmt->execute([$user_id, $auction_id]);
    echo 'success';
    break;

  default:
    echo 'Unknown function';
    break;
}

exit();