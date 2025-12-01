<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>
<?php require_once('db_connection.php'); ?>

<div class="container">
  <h2 class="my-4 text-center">My Bids</h2>

<?php
// This page shows a user the auctions they have bid on.

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
  echo '<div class="alert alert-warning">You must be logged in to view your bids.</div>';
} else {
  // 获取当前用户 ID
  $userId = (int)$_SESSION['user_id'];

  // 查询用户投过标的拍卖，并计算每个拍卖的当前最高价与出价次数
  $stmt = $pdo->prepare(
    "SELECT 
       a.auction_id,
       a.title,
       a.description,
       a.end_time,
       a.img_url,
       -- 当前价格：拍卖上的最高出价，如无出价则为起拍价
       COALESCE(MAX(b_all.bid_amount), a.starting_price) AS current_price,
       COUNT(b_all.bid_id) AS bid_count
     FROM Auction a
       -- 用于筛选出用户参与过的拍卖
       JOIN Bid b_user ON a.auction_id = b_user.auction_id AND b_user.bidder_id = ?
       -- 用于统计该拍卖的所有有效出价
       LEFT JOIN Bid b_all ON a.auction_id = b_all.auction_id AND b_all.is_active = TRUE
     GROUP BY a.auction_id
     ORDER BY a.end_time ASC"
  );
  $stmt->execute([$userId]);
  $myBids = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($myBids)) {
    echo '<p>You have not placed any bids yet.</p>';
  } else {
    echo '<div class="row">';
    foreach ($myBids as $item) {
      // $item['end_time'] 是字符串；需要转换成 DateTime 对象以便 print_listing_card 计算剩余时间
      $endTime = new DateTime($item['end_time']);
      print_listing_card(
        $item['auction_id'],     // 拍卖ID
        $item['title'],          // 标题
        $item['description'],    // 描述
        $item['current_price'],  // 当前价格
        $item['bid_count'],      // 出价次数
        $endTime,                // 结束时间 (DateTime 对象)
        $item['img_url']         // 图片URL（可为空）
      );
    }
    echo '</div>';
  }
}
?>

</div>

<?php include_once('footer.php'); ?>
