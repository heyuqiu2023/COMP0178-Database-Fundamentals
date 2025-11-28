<?php
require "db.php";

$auction_id = $_GET["auction_id"];
$seller_id = $_GET["seller_id"];

// 1. 获取当前结果
$stmt = $pdo->prepare("SELECT * FROM AuctionOutcome WHERE auction_id=?");
$stmt->execute([$auction_id]);
$outcome = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$outcome) die("Outcome not found.");

// 2. 检查是否过期
if ($outcome["acceptance_deadline"] && strtotime($outcome["acceptance_deadline"]) < time()) {
    die("Deadline passed. Cannot accept.");
}

// 3. 找最终最高出价者
$stmt = $pdo->prepare("
    SELECT bidder_id 
    FROM Bid 
    WHERE auction_id=? 
      AND is_active=1
    ORDER BY bid_amount DESC
    LIMIT 1
");
$stmt->execute([$auction_id]);
$top = $stmt->fetch(PDO::FETCH_ASSOC);
$winner_id = $top["bidder_id"] ?? null;

if (!$winner_id) die("No bids to accept.");


// 4. 更新 outcome
$stmt = $pdo->prepare("
    UPDATE AuctionOutcome
    SET seller_accepted=1, winner_id=?, seller_notified=0, winner_notified=0
    WHERE auction_id=?
");
$stmt->execute([$winner_id, $auction_id]);


// 5. 通知赢家
$pdo->prepare("
    INSERT INTO Notification (user_id, auction_id, type)
    VALUES (?, ?, 'winner_by_override')
")->execute([$winner_id, $auction_id]);

echo "Seller accepted the bid.";
?>
