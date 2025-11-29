<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<div class="container">
  <h2 class="my-4 text-center">My Bids</h2>

<?php
// This page shows a user the auctions they have bid on.  It will be similar
// to browse.php but without a search bar.  Once database integration is
// complete, replace the placeholder data with results from a query that
// selects auctions where the current user is the bidder.

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
  echo '<div class="alert alert-warning">You must be logged in to view your bids.</div>';
} else {
  // Placeholder dummy data for demonstration
  $dummy_bids = [
    ['id' => 2001, 'title' => 'Vintage camera', 'desc' => 'An old film camera in great condition.', 'price' => 45.00, 'bids' => 4, 'end_time' => (new DateTime())->add(new DateInterval('P1DT4H'))],
    ['id' => 2002, 'title' => 'Coffee grinder', 'desc' => 'Electric coffee grinder with multiple settings.', 'price' => 12.50, 'bids' => 2, 'end_time' => (new DateTime())->add(new DateInterval('P0DT20H'))]
  ];
  if (empty($dummy_bids)) {
    echo '<p>You have not placed any bids yet.</p>';
  } else {
    echo '<div class="row">';
    foreach ($dummy_bids as $item) {
      print_listing_card($item['id'], $item['title'], $item['desc'], $item['price'], $item['bids'], $item['end_time']);
    }
    echo '</div>';
  }
}
?>

</div>

<?php include_once('footer.php'); ?>