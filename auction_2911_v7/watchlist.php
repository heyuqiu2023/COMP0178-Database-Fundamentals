<?php
// watchlist.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$userId = require_login();
$db = get_db();

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$auctionId = (int)($_POST['auction_id'] ?? $_GET['auction_id'] ?? 0);

if (!in_array($action, ['add', 'remove'], true) || $auctionId <= 0) {
    http_response_code(400);
    exit('Invalid parameters');
}

try {
    if ($action === 'add') {
        $stmt = $db->prepare(
            "INSERT IGNORE INTO Watchlist (user_id, auction_id, email_notifications)
             VALUES (?, ?, TRUE)"
        );
        $stmt->execute([$userId, $auctionId]);

        // Record activity
        $db->prepare("INSERT INTO UserActivity (user_id, auction_id, action) VALUES (?, ?, 'watch')")
           ->execute([$userId, $auctionId]);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'message' => 'Added to watchlist']);
    } else {
        $stmt = $db->prepare(
            "DELETE FROM Watchlist WHERE user_id = ? AND auction_id = ?"
        );
        $stmt->execute([$userId, $auctionId]);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok', 'message' => 'Removed from watchlist']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
