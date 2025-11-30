<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<div class="container">
  <h2 class="my-4 text-center">Recommended for You</h2>

<?php
// This page shows personalised recommendations for a buyer based on their
// bidding history.  Once database integration is complete, replace the
// placeholder data with results from a recommendation query (e.g. based
// on collaborative filtering).  Only buyers should see recommendations.

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['account_type'] !== 'buyer') {
  echo '<div class="alert alert-warning">You must be logged in as a buyer to view recommendations.</div>';
} else {
  // Placeholder dummy data for demonstration
  $dummy_recommendations = [
    ['id' => 4001, 'title' => 'Smart watch', 'desc' => 'A feature‑packed smart watch with heart rate monitor.', 'price' => 50.00, 'bids' => 6, 'end_time' => (new DateTime())->add(new DateInterval('P3DT1H'))],
    ['id' => 4002, 'title' => 'Cookbook', 'desc' => 'Recipe book with healthy meal ideas.', 'price' => 8.50, 'bids' => 1, 'end_time' => (new DateTime())->add(new DateInterval('P2DT8H'))]
  ];
  if (empty($dummy_recommendations)) {
    echo '<p>No recommendations at this time. Place some bids to receive recommendations.</p>';
  } else {
    echo '<div class="row">';
    foreach ($dummy_recommendations as $item) {
      print_listing_card($item['id'], $item['title'], $item['desc'], $item['price'], $item['bids'], $item['end_time']);
    }
    echo '</div>';
  }
}
?>

</div>

<?php include_once('footer.php'); ?>