<?php
// Functions used to add or remove an auction item from a user's watchlist.
// This script is called via AJAX from listing.php and returns a simple
// string response of either "success" or an error message.  When the
// database is implemented, this will insert/delete records into the
// Watchlist table.

session_start();

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
    // TODO: Insert watchlist entry into database for user
    // For now, always succeed
    echo 'success';
    break;

  case 'remove_from_watchlist':
    // TODO: Remove watchlist entry from database
    echo 'success';
    break;

  default:
    echo 'Unknown function';
    break;
}

exit();