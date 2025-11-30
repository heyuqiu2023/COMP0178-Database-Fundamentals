<?php include_once('header.php'); ?>
<?php require_once('db.php'); ?>
<?php require_once('utilities.php'); ?>

<div class="container">
  <h2 class="my-4 text-center">My Watchlist</h2>
<?php
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
  echo '<div class="alert alert-warning">You must be logged in to view your watchlist.</div>';
} else {
  $user_id = $_SESSION['user_id'] ?? null;
  if (!$user_id) {
    echo '<div class="alert alert-warning">User not identified.</div>';
  } else {
    $pdo = get_db();
    $stmt = $pdo->prepare("
      SELECT a.auction_id AS id, a.title, a.description AS desc, 
             COALESCE(MAX(b.bid_amount), a.starting_price) AS price,
             COUNT(b.bid_id) AS bids,
             a.end_time
      FROM Watchlist w
      JOIN Auction a ON a.auction_id = w.auction_id
      LEFT JOIN Bid b ON b.auction_id = a.auction_id AND b.is_active = TRUE
      WHERE w.user_id = ?
      GROUP BY a.auction_id, a.title, a.description, a.starting_price, a.end_time
      ORDER BY a.end_time ASC
    ");
    $stmt->execute([$user_id]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
      echo '<p>You are not watching any auctions yet.</p>';
    } else {
      echo '<div class="row">';
      foreach ($items as $item) {
        $end_time = new DateTime($item['end_time']);
        print_listing_card(
          (int)$item['id'],
          $item['title'],
          $item['desc'] ?? '',
          (float)$item['price'],
          (int)$item['bids'],
          $end_time
        );
      }
      echo '</div>';
    }
  }
}
?>
</div>
<?php include_once('footer.php'); ?>
