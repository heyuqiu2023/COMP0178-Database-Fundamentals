<?php
require "db.php";

$user_id = $_SESSION["user_id"];

$sql = "
SELECT r.rec_id, a.auction_id, a.title, a.description, r.reason, r.created_at
FROM Recommendation r
JOIN Auction a ON a.auction_id = r.auction_id
WHERE r.user_id = ?
ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Your Recommendations</h2>

<?php foreach ($rows as $r): ?>
    <div style="border:1px solid #aaa;margin:10px;padding:10px;">
        <h3><?= htmlspecialchars($r['title']) ?></h3>
        <p><?= nl2br(htmlspecialchars($r['description'])) ?></p>
        <p><b>Why:</b> <?= htmlspecialchars($r['reason']) ?></p>
        <a href="auction.php?id=<?= $r['auction_id'] ?>">View Auction</a>
    </div>
<?php endforeach; ?>
