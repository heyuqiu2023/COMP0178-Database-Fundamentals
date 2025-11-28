<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php'; // 或 config.php，根据你的文件名

$stmt = $pdo->query("
    SELECT 
        a.auction_id,
        a.title,
        a.description,
        a.starting_price,
        a.end_time,
        u.username AS seller_name,
        c.category_name,
        (SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bid_count,
        (SELECT MAX(bid_amount) FROM Bid b WHERE b.auction_id = a.auction_id) AS current_price
    FROM Auction a
    JOIN User u ON u.user_id = a.seller_id
    LEFT JOIN Category c ON c.category_id = a.category_id
    WHERE a.status = 'active'
    ORDER BY a.end_time ASC
");

$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<h1>Browse Listings</h1>

<?php foreach ($auctions as $a): ?>
<div>
    <h3><?= htmlspecialchars($a['title']) ?></h3>
    <p><?= htmlspecialchars($a['description']) ?></p>
    <p><strong>£<?= $a['current_price'] ?? $a['starting_price'] ?></strong></p>
    <p><?= $a['bid_count'] ?> bids</p>
    <p>Ends: <?= $a['end_time'] ?></p>
    <p>Seller: <?= htmlspecialchars($a['seller_name']) ?></p>
</div>
<hr>
<?php endforeach; ?>

<?php include 'footer.php'; ?>



