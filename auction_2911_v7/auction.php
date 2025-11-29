<?php
// auction.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$db = get_db();
$auctionId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT a.*, u.username AS seller_name,
            COALESCE(MAX(b.bid_amount), a.starting_price) AS current_price
     FROM Auction a
     JOIN User u ON u.user_id = a.seller_id
     LEFT JOIN Bid b ON b.auction_id = a.auction_id AND b.is_active = TRUE
     WHERE a.auction_id = ?
     GROUP BY a.auction_id, u.username, a.starting_price"
);
$stmt->execute([$auctionId]);
$auction = $stmt->fetch();

if (!$auction) {
    http_response_code(404);
    exit('Auction not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($auction['title']) ?></title>
    <link rel="stylesheet" href="assets/bootstrap.min.css">
</head>
<body class="container py-4">
    <h1><?= htmlspecialchars($auction['title']) ?></h1>
    <p><strong>Seller:</strong> <?= htmlspecialchars($auction['seller_name']) ?></p>
    <p><strong>Description:</strong> <?= nl2br(htmlspecialchars($auction['description'])) ?></p>
    <p><strong>Current price:</strong> £<?= number_format((float)$auction['current_price'], 2) ?></p>
    <p><strong>Ends:</strong> <?= htmlspecialchars($auction['end_time']) ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($auction['status']) ?></p>

    <form action="bid.php" method="post" class="mt-3">
        <input type="hidden" name="auction_id" value="<?= (int)$auction['auction_id'] ?>">
        <div class="mb-2">
            <label for="bid_amount" class="form-label">Your bid (£)</label>
            <input type="number" step="0.01" min="0.01" name="bid_amount" id="bid_amount" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Place bid</button>
    </form>

    <form action="watchlist.php" method="post" class="mt-3">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="auction_id" value="<?= (int)$auction['auction_id'] ?>">
        <button type="submit" class="btn btn-outline-primary">Add to watchlist</button>
    </form>

    <h3 class="mt-4">Recent bids</h3>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Bidder</th>
                <th>Amount</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $b = $db->prepare(
            "SELECT b.bid_amount, b.bid_time, u.username
             FROM Bid b JOIN User u ON u.user_id = b.bidder_id
             WHERE b.auction_id = ? AND b.is_active = TRUE
             ORDER BY b.bid_amount DESC, b.bid_time ASC
             LIMIT 10"
        );
        $b->execute([$auctionId]);
        foreach ($b->fetchAll() as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td>£<?= number_format((float)$row['bid_amount'], 2) ?></td>
                <td><?= htmlspecialchars($row['bid_time']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
