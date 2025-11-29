<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<div class="container">
  <h2 class="my-4 text-center">My Listings</h2>

<?php
// This page is for showing a user the auction listings they have created.  It
// will be similar to browse.php but without a search bar.  Once database
// integration is complete, replace the placeholder data with results
// retrieved from the auctions table where the seller_id matches the
// current user.

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['account_type'] !== 'seller') {
  echo '<div class="alert alert-warning">You must be logged in as a seller to view your listings.</div>';
} else {
  // Placeholder dummy data for demonstration
  $dummy_listings = [
    ['id' => 3001, 'title' => 'Antique chair', 'desc' => 'A comfortable antique chair, circa 1920.', 'price' => 75.00, 'bids' => 5, 'end_time' => (new DateTime())->add(new DateInterval('P2DT2H'))],
    ['id' => 3002, 'title' => 'Wireless headphones', 'desc' => 'Noise‑cancelling over‑ear headphones.', 'price' => 25.00, 'bids' => 3, 'end_time' => (new DateTime())->add(new DateInterval('P1DT12H'))]
  ];
  if (empty($dummy_listings)) {
    echo '<p>You have not created any listings yet.</p>';
  } else {
    echo '<div class="row">';
    foreach ($dummy_listings as $item) {
      print_listing_card($item['id'], $item['title'], $item['desc'], $item['price'], $item['bids'], $item['end_time']);
    }
    echo '</div>';
  }
}
?>

</div>

<?php include_once('footer.php'); ?>