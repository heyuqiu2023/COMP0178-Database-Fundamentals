<?php
// bid.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auction_service.php';
require_once __DIR__ . '/notification_service.php';

$userId = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$auctionId = (int)($_POST['auction_id'] ?? 0);
$amount    = (float)($_POST['bid_amount'] ?? 0);

if ($auctionId <= 0 || $amount <= 0) {
    http_response_code(400);
    exit('Invalid parameters');
}

$db = get_db();

try {
    $db->beginTransaction();

    // 1) Lock and validate auction
    $auction = assert_auction_active_for_update($db, $auctionId);

    // 2) Get current highest bid under lock
    $currentTop = get_highest_bid_for_update($db, $auctionId);
    $minAcceptable = $currentTop ? ((float)$currentTop['bid_amount'] + 0.01) : (float)$auction['starting_price'];

    if ($amount < $minAcceptable) {
        throw new RuntimeException('Bid too low. Minimum acceptable: ' . number_format($minAcceptable, 2));
    }

    // 3) Insert bid
    $stmt = $db->prepare(
        "INSERT INTO Bid (auction_id, bidder_id, bid_amount, is_active)
         VALUES (?, ?, ?, TRUE)"
    );
    $stmt->execute([$auctionId, $userId, $amount]);
    $newBidId = (int)$db->lastInsertId();

    // 4) Determine outbid user (previous top), then notifications
    $outbidUserId = $currentTop ? (int)$currentTop['bidder_id'] : null;
    enqueue_bid_notifications($db, $auctionId, $newBidId, $outbidUserId);

    // 5) Optionally record activity
    $db->prepare("INSERT INTO UserActivity (user_id, auction_id, action) VALUES (?, ?, 'bid')")
       ->execute([$userId, $auctionId]);

    $db->commit();

    // If auction end_time passed during the bid, conclude gracefully
    conclude_auction_if_due($db, $auctionId);

    header('Content-Type: application/json');
    echo json_encode([
        'status'      => 'ok',
        'new_bid_id'  => $newBidId,
        'outbid_user' => $outbidUserId,
        'message'     => 'Bid placed successfully',
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
