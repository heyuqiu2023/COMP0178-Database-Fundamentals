<?php
require "db.php";

$auction_id = $_GET["auction_id"];

// 1. 结束拍卖
$pdo->prepare("UPDATE Auction SET status='ended' WHERE auction_id=?")
    ->execute([$auction_id]);

// 2. 读取拍卖数据
$sql = "SELECT * FROM Auction WHERE auction_id=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$auction_id]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) die("Auction not found.");


// 3. 获取最高出价
$sql = "
    SELECT bidder_id, bid_amount
    FROM Bid 
    WHERE auction_id=? AND is_active=1
    ORDER BY bid_amount DESC
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$auction_id]);
$bid = $stmt->fetch(PDO::FETCH_ASSOC);

$winner_id = $bid["bidder_id"] ?? null;
$final_price = $bid["bid_amount"] ?? null;

$reserve_met = ($final_price !== null && $final_price >= $auction["reserve_price"]) ? 1 : 0;


// 4. 设置 seller acceptance deadline
$deadline = (!$reserve_met && $final_price !== null)
    ? date("Y-m-d H:i:s", strtotime("+24 hours"))
    : null;


// 5. 插入 AuctionOutcome
$sql = "
INSERT INTO AuctionOutcome
(auction_id, winner_id, final_price, reserve_met, seller_accepted, 
 acceptance_deadline, concluded_at, seller_notified, winner_notified)
VALUES (?, ?, ?, ?, 0, ?, NOW(), 0, 0)
ON DUPLICATE KEY UPDATE
 winner_id=VALUES(winner_id)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $auction_id,
    $winner_id,
    $final_price,
    $reserve_met,
    $deadline
]);


// 6. 自动通知逻辑
if ($winner_id === null) {

    // 无人出价 -> 通知卖家拍卖结束
    $pdo->prepare("
        INSERT INTO Notification (user_id, auction_id, type) 
        VALUES (?, ?, 'auction_ended')
    ")->execute([$auction["seller_id"], $auction_id]);

} elseif ($reserve_met) {

    // 达到保留价 -> 通知买家和卖家
    $pdo->prepare("
        INSERT INTO Notification (user_id, auction_id, type) 
        VALUES (?, ?, 'auction_ended')
    ")->execute([$auction["seller_id"], $auction_id]);

    $pdo->prepare("
        INSERT INTO Notification (user_id, auction_id, type) 
        VALUES (?, ?, 'you_won')
    ")->execute([$winner_id, $auction_id]);

} else {

    // reserve 未达到 -> 通知卖家“未达保留价”
    $pdo->prepare("
        INSERT INTO Notification (user_id, auction_id, type) 
        VALUES (?, ?, 'reserve_not_met')
    ")->execute([$auction["seller_id"], $auction_id]);
}

echo "Auction outcome created.";
?>
