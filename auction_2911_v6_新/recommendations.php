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
  $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
  if (!$user_id) {
    echo '<div class="alert alert-info">Log in as a buyer to see recommendations.</div>';
  } else {
    // Simple recommendations: use Recommendation table
    $db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'auction';
    $items = [];
    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if (!$mysqli->connect_errno) {
      $sql = "SELECT a.auction_id, a.title, a.description, a.end_time, a.image_url, a.category_id, u.username AS seller_name, " .
        "(SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bids, " .
        "(SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) AS current_price " .
        "FROM Recommendation r JOIN Auction a ON r.auction_id = a.auction_id JOIN `User` u ON a.seller_id = u.user_id WHERE r.user_id = " . intval($user_id) . " ORDER BY r.created_at DESC";
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
            'image_url' => $r['image_url'],
            'seller_name' => $r['seller_name'],
            'category_id' => $r['category_id']
          ];
        }
        $res->free();
      }
      $mysqli->close();
    }

    if (empty($items)) {
      echo '<p>No recommendations at this time. Place some bids to receive recommendations.</p>';
    } else {
      echo '<div class="row">';
      foreach ($items as $item) {
        print_listing_card($item['id'], $item['title'], $item['desc'], $item['price'], $item['bids'], $item['end_time'], isset($item['image_url']) ? $item['image_url'] : null, isset($item['seller_name']) ? $item['seller_name'] : null, isset($item['category_id']) ? $item['category_id'] : null);
      }
      echo '</div>';
    }
  }
}
?>

</div>

<?php include_once('footer.php'); ?>