<?php include_once('header.php'); ?>
<?php require_once('db_connection.php'); ?>
<?php require_once('utilities.php'); ?>

<div class="container">
  <h2 class="my-4 text-center">Recommended for You</h2>

<?php
// 本页面根据买家的投标历史，提供个性化的拍卖品推荐（协同过滤算法）
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in'] || $_SESSION['account_type'] !== 'buyer') {
  echo '<div class="alert alert-warning">You must be logged in as a buyer to view recommendations.</div>';
} else {
  $user_id = $_SESSION['user_id'] ?? null;
  if (!$user_id) {
    echo '<div class="alert alert-warning">User not identified.</div>';
  } else {
    // 基于投标历史查询推荐的拍卖商品
    $stmt = $pdo->prepare("
      SELECT a.auction_id AS id, a.title, a.description AS desc,
             (SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) AS price,
             (SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bids,
             a.end_time
      FROM Auction a
      WHERE a.auction_id IN (
          SELECT DISTINCT b2.auction_id
          FROM Bid b2
          WHERE b2.user_id IN (
              SELECT DISTINCT b1.user_id
              FROM Bid b1
              WHERE b1.auction_id IN (
                  SELECT DISTINCT b3.auction_id
                  FROM Bid b3
                  WHERE b3.user_id = ?
              )
          )
      )
      AND a.auction_id NOT IN (
          SELECT DISTINCT b4.auction_id
          FROM Bid b4
          WHERE b4.user_id = ?
      )
      AND a.end_time > NOW()
      ORDER BY a.end_time ASC
    ");
    $stmt->execute([$user_id, $user_id]);
    $recommendations = $stmt->fetchAll();

    if (empty($recommendations)) {
      echo '<p>No recommendations at this time. Place some bids to receive recommendations.</p>';
    } else {
      echo '<div class="row">';
      foreach ($recommendations as $item) {
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
