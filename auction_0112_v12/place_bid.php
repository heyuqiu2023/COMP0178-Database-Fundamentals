<?php include_once('header.php'); ?>
<?php require_once('db_connection.php'); ?>
<?php require_once('notify.php'); ?>

<div class="container">
  <h2 class="my-3">Place bid result</h2>

<?php
// 只接受 POST 提交
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div class="alert alert-warning">No bid details received. Please submit your bid from the listing page.</div>';
    echo '</div>';
    include_once('footer.php');
    exit;
}

// 必须登录且是 buyer
if (
    !isset($_SESSION['logged_in']) || 
    !$_SESSION['logged_in'] || 
    !isset($_SESSION['account_type']) || 
    $_SESSION['account_type'] !== 'buyer'
) {
    echo '<div class="alert alert-warning">You must be logged in as a buyer to place a bid.</div>';
    echo '</div>';
    include_once('footer.php');
    exit;
}

$bidder_id  = $_SESSION['user_id'] ?? null;
$auction_id = isset($_POST['auction_id']) ? (int)$_POST['auction_id'] : 0;
$bid_amount = isset($_POST['bid']) ? (float)$_POST['bid'] : 0.0;

if (!$bidder_id || $auction_id <= 0 || $bid_amount <= 0) {
    echo '<div class="alert alert-danger">Invalid bid details.</div>';
    echo '<p><a href="javascript:history.back()">Go back</a></p>';
    echo '</div>';
    include_once('footer.php');
    exit;
}

try {
    // 开启事务（简单版本，不改隔离级别）
    $pdo->beginTransaction();

    // 锁定这条拍卖记录
    $stmt = $pdo->prepare("SELECT * FROM Auction WHERE auction_id = ? FOR UPDATE");
    $stmt->execute([$auction_id]);
    $auction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$auction) {
        throw new Exception('Auction not found.');
    }

    $now      = new DateTime('now');
    $end_time = new DateTime($auction['end_time']);
    $status   = $auction['status'];

    // 拍卖已经结束 / 不处于 active 状态
    if ($status !== 'active' || $now >= $end_time) {
        if ($status !== 'ended') {
            closeAuction($pdo, $auction); // 结束拍卖，写入结果并发通知
        }
        throw new Exception('Auction has ended.');
    }

    // 找当前最高出价（并锁定那条出价）
    $stmt = $pdo->prepare("SELECT bid_id, bidder_id, bid_amount 
                           FROM Bid 
                           WHERE auction_id = ? 
                           ORDER BY bid_amount DESC, bid_time ASC 
                           LIMIT 1 FOR UPDATE");
    $stmt->execute([$auction_id]);
    $highest = $stmt->fetch(PDO::FETCH_ASSOC);

    $current_high = $highest ? (float)$highest['bid_amount'] : (float)$auction['starting_price'];

    if ($bid_amount <= $current_high) {
        throw new Exception('Your bid must be higher than the current highest bid (£' . number_format($current_high, 2) . ').');
    }

    // 插入新出价
    $stmt = $pdo->prepare("INSERT INTO Bid (auction_id, bidder_id, bid_amount) VALUES (?, ?, ?)");
    $stmt->execute([$auction_id, $bidder_id, $bid_amount]);
    $bid_id = (int)$pdo->lastInsertId();

    // === 通知部分 ===

    // 1. 通知自己：出价成功
    queue_notification($bidder_id, $auction_id, $bid_id, 'new_bid');

    // 2. 通知之前的最高出价者：你被超过出价
    if ($highest && (int)$highest['bidder_id'] !== $bidder_id) {
        queue_notification((int)$highest['bidder_id'], $auction_id, $bid_id, 'outbid');
    }

    // 3. 通知所有关注该拍卖的用户（watchlist）：该拍卖有新出价
    $watchStmt = $pdo->prepare("SELECT user_id FROM Watchlist WHERE auction_id = ?");
    $watchStmt->execute([$auction_id]);
    $watchers = $watchStmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($watchers as $watch_user_id) {
        $watch_user_id = (int)$watch_user_id;
        if ($watch_user_id !== $bidder_id) {
            queue_notification($watch_user_id, $auction_id, $bid_id, 'new_bid');
        }
    }

    // 出价之后再检查一次时间，如果已经过期，则立即结束拍卖
    if (new DateTime('now') >= $end_time) {
        closeAuction($pdo, $auction);
    }

    $pdo->commit();

    echo '<div class="alert alert-success">Your bid of £' . number_format($bid_amount, 2) . ' has been placed.</div>';
    echo '<p><a href="listing.php?auction_id=' . $auction_id . '">Return to listing</a> or <a href="browse.php">continue browsing</a>.</p>';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo '<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p><a href="javascript:history.back()">Go back</a></p>';
}

// 拍卖结束：写 AuctionOutcome + 发通知
function closeAuction($pdo, $auctionRow) {
    $auction_id    = (int)$auctionRow['auction_id'];
    $reserve_price = isset($auctionRow['reserve_price']) ? (float)$auctionRow['reserve_price'] : 0.0;
    $seller_id     = (int)$auctionRow['seller_id'];

    // 找中标者（最高出价）
    $stmt = $pdo->prepare("SELECT bid_id, bidder_id, bid_amount 
                           FROM Bid 
                           WHERE auction_id = ? 
                           ORDER BY bid_amount DESC, bid_time ASC 
                           LIMIT 1 FOR UPDATE");
    $stmt->execute([$auction_id]);
    $winning = $stmt->fetch(PDO::FETCH_ASSOC);

    $winner_id   = $winning ? (int)$winning['bidder_id'] : null;
    $final_price = $winning ? (float)$winning['bid_amount'] : null;
    $reserve_met = $winning ? ($final_price >= $reserve_price) : false;

    // 更新 Auction 状态为 ended
    $pdo->prepare("UPDATE Auction SET status = 'ended' WHERE auction_id = ?")
        ->execute([$auction_id]);

    // 写入 AuctionOutcome
    $accept_deadline = (new DateTime('now'))->modify('+48 hours')->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare(
        "INSERT INTO AuctionOutcome 
         (auction_id, winner_id, final_price, reserve_met, seller_accepted, acceptance_deadline, concluded_at)
         VALUES (?, ?, ?, ?, FALSE, ?, NOW())"
    );
    $stmt->execute([$auction_id, $winner_id, $final_price, $reserve_met, $accept_deadline]);

    // === 结束时通知 ===
    // 通知卖家：拍卖结束
    queue_notification($seller_id, $auction_id, $winning ? (int)$winning['bid_id'] : null, 'auction_ended');

    // 有中标者并且达到保留价：通知中标者
    if ($winner_id && $reserve_met) {
        queue_notification($winner_id, $auction_id, (int)$winning['bid_id'], 'auction_ended');
    }

    // 未达到保留价：通知卖家流拍
    if (!$reserve_met) {
        queue_notification($seller_id, $auction_id, $winning ? (int)$winning['bid_id'] : null, 'reserve_not_met');
    }
}
?>
</div>

<?php include_once('footer.php'); ?>
