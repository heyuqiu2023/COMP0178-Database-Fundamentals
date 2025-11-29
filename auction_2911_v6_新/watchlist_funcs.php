<?php
// Functions used to add or remove an auction item from a user's watchlist.
// This script is called via AJAX from listing.php and returns a simple
// string response of either "success" or an error message.  When the
// database is implemented, this will insert/delete records into the
// Watchlist table.

session_start();
require_once('db.php');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo 'Invalid request';
  exit();
}

// Retrieve function name and arguments from POST data
$function_name = isset($_POST['functionname']) ? $_POST['functionname'] : '';
$arguments     = isset($_POST['arguments']) ? $_POST['arguments'] : [];

// Ensure user is logged in before modifying watchlist
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
  echo 'User not logged in';
  exit();
}

// Extract the item ID from arguments (expecting first argument)
$item_id = null;
if (is_array($arguments) && count($arguments) > 0) {
  $item_id = intval($arguments[0]);
}
if ($item_id === null) {
  echo 'Invalid item';
  exit();
}

switch ($function_name) {
  case 'add_to_watchlist':
    // Insert watchlist entry into database for user
    $db = get_db();
    if (!$db) { echo 'DB error'; exit(); }
    $user_id = intval($_SESSION['user_id']);
    $stmt = $db->prepare('INSERT IGNORE INTO Watchlist (user_id, auction_id, email_notifications) VALUES (?, ?, 1)');
    $stmt->bind_param('ii', $user_id, $item_id);
    if ($stmt->execute()) {
      echo 'success';
    } else {
      echo 'Failed: ' . $stmt->error;
    }
    $stmt->close(); $db->close();
    break;

  case 'remove_from_watchlist':
    $db = get_db();
    if (!$db) { echo 'DB error'; exit(); }
    $user_id = intval($_SESSION['user_id']);
    $stmt = $db->prepare('DELETE FROM Watchlist WHERE user_id = ? AND auction_id = ?');
    $stmt->bind_param('ii', $user_id, $item_id);
    if ($stmt->execute()) {
      echo 'success';
    } else {
      echo 'Failed: ' . $stmt->error;
    }
    $stmt->close(); $db->close();
    break;

  default:
    echo 'Unknown function';
    break;
}

exit();