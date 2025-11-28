<?php
require "db.php";

// 假设用户已经登录
$user_id = $_SESSION["user_id"];   // 或硬编码测试：$user_id = 5;

// 1. 清空旧推荐
$sql = "DELETE FROM Recommendation WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

// 2. 计算协同过滤推荐
$sql = "
INSERT INTO Recommendation (user_id, auction_id, reason, created_at)
SELECT DISTINCT
    :uid AS user_id,
    b2.auction_id AS recommended_auction,
    CONCAT('Users similar to you also bid on item \"', a.title, '\"') AS reason,
    NOW()
FROM Bid b1
JOIN Bid b2 
    ON b1.bidder_id = :uid
    AND b2.bidder_id <> :uid
    AND b1.auction_id = b2.auction_id
JOIN Auction a
    ON a.auction_id = b2.auction_id
WHERE a.status = 'active'
  AND b2.auction_id NOT IN (
        SELECT auction_id
        FROM Bid
        WHERE bidder_id = :uid
  );
";

$stmt = $pdo->prepare($sql);
$stmt->execute(["uid" => $user_id]);

echo "Recommendations updated!";
?>
