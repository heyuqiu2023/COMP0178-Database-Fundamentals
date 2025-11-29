<?php
// my_watchlist.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$userId = require_login();
$db = get_db();

$stmt = $db->prepare(
    "SELECT a.auction_id, a.title, a.description, a.end_time, a.status,
            COALESCE(MAX(b.bid_amount), a.starting_price) AS current_price
     FROM Watchlist w
     JOIN Auction a ON a.auction_id = w.auction_id
     LEFT JOIN Bid b ON b.auction_id = a.auction_id AND b.is_active = TRUE
     WHERE w.user_id = ?
     GROUP BY a.auction_id, a.title, a.description, a.end_time, a.status, a.starting_price
     ORDER BY a.end_time ASC"
);
$stmt->execute([$userId]);
$rows = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Watchlist</title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>
<body class="container py-4">
    <h1>My Watchlist</h1>
    <?php if (empty($rows)): ?>
        <p>You are not watching any auctions yet.</p>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Current price</th>
                    <th>Ends</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['title']) ?></td>
                    <td>£<?= number_format((float)$r['current_price'], 2) ?></td>
                    <td><?= htmlspecialchars($r['end_time']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td>
                        <a class="btn btn-sm btn-primary" href="auction.php?id=<?= (int)$r['auction_id'] ?>">View</a>
                        <form action="watchlist.php" method="post" style="display:inline;">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="auction_id" value="<?= (int)$r['auction_id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
