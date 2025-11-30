<?php
require_once 'db_connection.php';

$auction_id = isset($_GET['auction_id']) ? (int)$_GET['auction_id'] : 0;

// Auction
$stmt = $pdo->prepare("
  SELECT a.*, u.username AS seller_name, c.category_name
  FROM Auction a
  JOIN User u ON u.user_id = a.seller_id
  JOIN Category c ON c.category_id = a.category_id
  WHERE a.auction_id = ?
");
$stmt->execute([$auction_id]);
$auction = $stmt->fetch();

if (!$auction) {
  echo '<div class="container"><div class="alert alert-warning">Auction not found.</div></div>';
  include_once('footer.php');
  exit;
}

$title = $auction['title'];
$description = $auction['description'] ?? '';
$end_time = new DateTime($auction['end_time']);
$now = new DateTime();
$is_active = ($auction['status'] === 'active') && ($now < $end_time);

// Highest bid or starting price
$stmt = $pdo->prepare("SELECT bid_amount FROM Bid WHERE auction_id = ? AND is_active = TRUE ORDER BY bid_amount DESC, bid_time ASC LIMIT 1");
$stmt->execute([$auction_id]);
$highest = $stmt->fetch();
$current_price = $highest ? (float)$highest['bid_amount'] : (float)$auction['starting_price'];

$time_remaining = '';
if ($is_active) {
  $time_to_end = date_diff($now, $end_time);
  $time_remaining = display_time_remaining($time_to_end);
}

// Watching state from DB
$has_session = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$watching = false;
if ($has_session && isset($_SESSION['user_id'])) {
  $stmt = $pdo->prepare("SELECT 1 FROM Watchlist WHERE user_id = ? AND auction_id = ?");
  $stmt->execute([$_SESSION['user_id'], $auction_id]);
  $watching = (bool)$stmt->fetch();
}
?>