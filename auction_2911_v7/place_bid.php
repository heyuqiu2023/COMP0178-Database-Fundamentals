<?php include_once('header.php'); ?>

<div class="container">
  <h2 class="my-3">Place bid result</h2>

<?php
// This page handles POST requests from the bidding form on listing.php.
// It should validate the bid amount, compare it to the current highest bid
// and insert a new bid record into the database.  For now, we simply
// display a placeholder message.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $item_id = isset($_POST['item_id']) ? htmlspecialchars($_POST['item_id']) : '(unknown item)';
  $bid_amount = isset($_POST['bid']) ? floatval($_POST['bid']) : null;
  if ($bid_amount !== null && $bid_amount > 0) {
    echo '<div class="alert alert-success">Your bid of £' . number_format($bid_amount, 2) . ' has been placed for item ' . $item_id . ' (placeholder).</div>';
    echo '<p><a href="listing.php?item_id=' . urlencode($item_id) . '">Return to listing</a> or <a href="browse.php">continue browsing</a>.</p>';
  } else {
    echo '<div class="alert alert-danger">Invalid bid amount.  Please go back and enter a valid number.</div>';
    echo '<p><a href="javascript:history.back()">Go back</a></p>';
  }
} else {
  echo '<div class="alert alert-warning">No bid details received.  Please submit your bid from the listing page.</div>';
}
?>

</div>

<?php include_once('footer.php'); ?>