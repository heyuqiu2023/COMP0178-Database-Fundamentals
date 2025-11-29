<?php include_once('header.php'); ?>

<div class="container">
  <h2 class="my-3">Place bid result</h2>

<?php
// This page handles POST requests from the bidding form on listing.php.
// It should validate the bid amount, compare it to the current highest bid
// and insert a new bid record into the database.  For now, we simply
// display a placeholder message.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once('db.php');
  $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
  $bid_amount = isset($_POST['bid']) ? floatval($_POST['bid']) : null;

  if ($item_id <= 0 || $bid_amount === null || $bid_amount <= 0) {
    echo '<div class="alert alert-danger">Invalid bid data. Please enter a valid amount.</div>';
    echo '<p><a href="javascript:history.back()">Go back</a></p>';
    include_once('footer.php');
    exit();
  }

  // Require login
  if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || !isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-warning">You must be logged in to place a bid.</div>';
    echo '<p><a href="login_result.php">Login</a> or <a href="register.php">Register</a></p>';
    include_once('footer.php');
    exit();
  }

  $db = get_db();
  if (!$db) {
    echo '<div class="alert alert-danger">Database connection failed.</div>';
    include_once('footer.php');
    exit();
  }

  // Check auction exists and current highest bid
  $stmt = $db->prepare('SELECT starting_price, reserve_price FROM Auction WHERE auction_id = ? LIMIT 1');
  $stmt->bind_param('i', $item_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if (!$res || $res->num_rows === 0) {
    echo '<div class="alert alert-danger">Auction not found.</div>';
    $stmt->close(); $db->close(); include_once('footer.php'); exit();
  }
  $row = $res->fetch_assoc();
  $stmt->close();

  // Get current highest bid
  $stmt2 = $db->prepare('SELECT COALESCE(MAX(bid_amount), ?) AS current_price FROM Bid WHERE auction_id = ?');
  $stmt2->bind_param('di', $row['starting_price'], $item_id);
  $stmt2->execute();
  $res2 = $stmt2->get_result();
  $cur = $res2->fetch_assoc();
  $current_price = floatval($cur['current_price']);
  $stmt2->close();

  if ($bid_amount <= $current_price) {
    echo '<div class="alert alert-danger">Your bid must be higher than the current price (£' . number_format($current_price,2) . ').</div>';
    echo '<p><a href="listing.php?item_id=' . urlencode($item_id) . '">Return to listing</a></p>';
    $db->close(); include_once('footer.php'); exit();
  }

  // Insert bid
  $user_id = intval($_SESSION['user_id']);
  $stmt3 = $db->prepare('INSERT INTO Bid (auction_id, bidder_id, bid_amount) VALUES (?, ?, ?)');
  $stmt3->bind_param('iid', $item_id, $user_id, $bid_amount);
  if ($stmt3->execute()) {
    echo '<div class="alert alert-success">Your bid of £' . number_format($bid_amount, 2) . ' has been placed for item ' . htmlspecialchars($item_id) . '.</div>';
    echo '<p><a href="listing.php?item_id=' . urlencode($item_id) . '">Return to listing</a> or <a href="browse.php">continue browsing</a>.</p>';
  } else {
    echo '<div class="alert alert-danger">Failed to place bid: ' . htmlspecialchars($stmt3->error) . '</div>';
  }
  $stmt3->close();
  $db->close();

} else {
  echo '<div class="alert alert-warning">No bid details received.  Please submit your bid from the listing page.</div>';
}
?>

</div>

<?php include_once('footer.php'); ?>