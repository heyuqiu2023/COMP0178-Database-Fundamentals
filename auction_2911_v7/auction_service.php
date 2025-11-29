<?php
// auction_service.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notification_service.php';

/**
 * Get the current highest active bid for an auction (inside a transaction).
 */
function get_highest_bid_for_update(PDO $db, int $auctionId): ?array {
    $stmt = $db->prepare(
        "SELECT b.bid_id, b.bidder_id, b.bid_amount
         FROM Bid b
         WHERE b.auction_id = ? AND b.is_active = TRUE
         ORDER BY b.bid_amount DESC, b.bid_time ASC
         LIMIT 1
         FOR UPDATE"
    );
    $stmt->execute([$auctionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Mark auction ended and write AuctionOutcome. Notify winner and seller.
 * Call from a cron or after bid attempts when end_time has passed.
 */
function conclude_auction_if_due(PDO $db, int $auctionId): void {
    // Lock auction row
    $stmt = $db->prepare("SELECT * FROM Auction WHERE auction_id = ? FOR UPDATE");
    $stmt->execute([$auctionId]);
    $auction = $stmt->fetch();
    if (!$auction) return;

    $now = new DateTimeImmutable('now');
    $end = new DateTimeImmutable($auction['end_time']);

    if ($auction['status'] === 'ended' || $auction['status'] === 'cancelled') return;
    if ($now < $end) return; // not yet ended

    // Get highest bid (locked)
    $top = get_highest_bid_for_update($db, $auctionId);

    $winnerId = $top ? (int)$top['bidder_id'] : null;
    $finalPrice = $top ? (float)$top['bid_amount'] : null;
    $reserveMet = $top ? ($finalPrice >= (float)$auction['reserve_price']) : false;

    // Update auction status
    $db->prepare("UPDATE Auction SET status = 'ended' WHERE auction_id = ?")
       ->execute([$auctionId]);

    // Insert outcome (idempotent guard)
    $stmt = $db->prepare("SELECT outcome_id FROM AuctionOutcome WHERE auction_id = ?");
    $stmt->execute([$auctionId]);
    $existing = $stmt->fetch();

    $concludedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $deadline = (new DateTimeImmutable('+3 days'))->format('Y-m-d H:i:s');

    if (!$existing) {
        $ins = $db->prepare(
            "INSERT INTO AuctionOutcome
            (auction_id, winner_id, final_price, reserve_met, seller_accepted,
             acceptance_deadline, concluded_at, seller_notified, winner_notified)
             VALUES (?, ?, ?, ?, FALSE, ?, ?, FALSE, FALSE)"
        );
        $ins->execute([
            $auctionId,
            $winnerId,
            $finalPrice,
            $reserveMet ? 1 : 0,
            $deadline,
            $concludedAt
        ]);
    }

    // Notify seller and winner (if reserve met and winner exists)
    $sellerId = (int)$auction['seller_id'];

    create_notification($db, $sellerId, $auctionId, $top ? (int)$top['bid_id'] : null, 'auction_ended');
    if ($winnerId !== null) {
        create_notification($db, $winnerId, $auctionId, $top ? (int)$top['bid_id'] : null, 'auction_ended');
    }
}

/**
 * Utility: ensure auction is active and not ended (with lock).
 */
function assert_auction_active_for_update(PDO $db, int $auctionId): array {
    $stmt = $db->prepare("SELECT * FROM Auction WHERE auction_id = ? FOR UPDATE");
    $stmt->execute([$auctionId]);
    $auction = $stmt->fetch();
    if (!$auction) {
        throw new RuntimeException('Auction not found');
    }
    $now = new DateTimeImmutable('now');
    $start = new DateTimeImmutable($auction['start_time']);
    $end = new DateTimeImmutable($auction['end_time']);

    if ($auction['status'] === 'cancelled') {
        throw new RuntimeException('Auction cancelled');
    }
    if ($now < $start) {
        throw new RuntimeException('Auction has not started');
    }
    if ($now >= $end || $auction['status'] === 'ended') {
        throw new RuntimeException('Auction ended');
    }
    // Optionally ensure status is 'active'
    if ($auction['status'] !== 'active') {
        $db->prepare("UPDATE Auction SET status = 'active' WHERE auction_id = ?")->execute([$auctionId]);
        $auction['status'] = 'active';
    }
    return $auction;
}
