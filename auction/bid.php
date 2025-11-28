<?php
require "db.php";

$auction_id = $_POST["auction_id"];
$user_id = $_SESSION["user_id"];
$amount = $_POST["bid_amount"];

// 插入新出价
$sql = "
INSERT INTO Bid (auction_id, bidder_id, bid_amount, bid_time, is_active)
VALUES (?, ?, ?, NOW(), 1)
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$auction_id, $user_id, $amount]);

// 更新推荐
include "collaborative_filter.php";

header("Location: recommendation.php");
?>
