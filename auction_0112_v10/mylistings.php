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
  $seller_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
  if (!$seller_id) {
    echo '<div class="alert alert-info">No listings to show for demo user. Log in as a seller to view your listings.</div>';
  } else {
    $db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'auction_system';
    $items = [];
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$mysqli->connect_errno) {
      $sql = "SELECT a.auction_id, a.title, a.description, a.end_time, a.img_url, a.category_id, u.username AS seller_name, " .
             "(SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bids, " .
             "(SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) AS current_price " .
             "FROM Auction a JOIN `User` u ON a.seller_id = u.user_id WHERE a.seller_id = " . intval($seller_id) . " ORDER BY a.created_at DESC";
      $res = $mysqli->query($sql);
      if ($res) {
        while ($r = $res->fetch_assoc()) {
          $items[] = [
            'id' => $r['auction_id'],
            'title' => $r['title'],
            'desc' => $r['description'],
            'price' => $r['current_price'],
            'bids' => $r['bids'],
            'end_time' => new DateTime($r['end_time']),
            'img_url' => $r['img_url'],
            'seller_name' => $r['seller_name'],
            'category_id' => $r['category_id']
          ];
        }
        $res->free();
      }
      $mysqli->close();
    }

    if (empty($items)) {
      echo '<p>You have not created any listings yet.</p>';
    } else {
      echo '<div class="row">';
      foreach ($items as $item) {
        print_listing_card($item['id'], $item['title'], $item['desc'], $item['price'], $item['bids'], $item['end_time'], isset($item['img_url']) ? $item['img_url'] : null, isset($item['seller_name']) ? $item['seller_name'] : null, isset($item['category_id']) ? $item['category_id'] : null);
      }
      echo '</div>';
    }
  }
}
?>

</div>

<?php include_once('footer.php'); ?>