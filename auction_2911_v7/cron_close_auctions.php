<?php
require_once 'db.php';
require_once 'notify.php';

// Close any auctions past end_time but still active
$pdo = get_db();
$pdo->beginTransaction();
try {
  // Select auctions to close and lock
  $stmt = $pdo->query("SELECT * FROM Auction WHERE status = 'active' AND end_time <= NOW() FOR UPDATE");
  $auctions = $stmt->fetchAll();

  foreach ($auctions as $auction) {
    // Reuse the same closeAuction helper as in place_bid.php:
    closeAuction($pdo, $auction);
  }

  $pdo->commit();
} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  // Log error
}

// Local definition for cron
function closeAuction(PDO $pdo, array $auctionRow): void {
  $auction_id = (int)$auctionRow['auction_id'];
  $reserve_price = (float)$auctionRow['reserve_price'];
  $seller_id = (int)$auctionRow['seller_id'];

  $stmt = $pdo->prepare("SELECT bid_id, bidder_id, bid_amount FROM Bid WHERE auction_id = ? AND is_active = TRUE ORDER BY bid_amount DESC, bid_time ASC LIMIT 1 FOR UPDATE");
  $stmt->execute([$auction_id]);
  $winning = $stmt->fetch();

  $winner_id = $winning ? (int)$winning['bidder_id'] : null;
  $final_price = $winning ? (float)$winning['bid_amount'] : null;
  $reserve_met = $winning ? ($final_price >= $reserve_price) : false;

  $pdo->prepare("UPDATE Auction SET status = 'ended' WHERE auction_id = ?")->execute([$auction_id]);

  $accept_deadline = (new DateTimeImmutable('now'))->modify('+48 hours')->format('Y-m-d H:i:s');
  $stmt = $pdo->prepare(
    "INSERT INTO AuctionOutcome (auction_id, winner_id, final_price, reserve_met, seller_accepted, acceptance_deadline, concluded_at)
     VALUES (?, ?, ?, ?, FALSE, ?, NOW())
     ON DUPLICATE KEY UPDATE winner_id = VALUES(winner_id), final_price = VALUES(final_price), reserve_met = VALUES(reserve_met), concluded_at = VALUES(concluded_at)"
  );
  $stmt->execute([$auction_id, $winner_id, $final_price, $reserve_met, $accept_deadline]);

  // Notify seller and winner; reserve_not_met if applicable
  queue_notification($seller_id, $auction_id, $winning ? (int)$winning['bid_id'] : null, 'auction_ended');
  if ($winner_id) queue_notification($winner_id, $auction_id, (int)$winning['bid_id'], 'auction_ended');
  if (!$reserve_met) queue_notification($seller_id, $auction_id, $winning ? (int)$winning['bid_id'] : null, 'reserve_not_met');
}
