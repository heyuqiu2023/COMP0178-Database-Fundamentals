<?php
// notification_service.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Create a notification row.
 */
function create_notification(PDO $db, int $userId, int $auctionId, ?int $bidId, string $type): int {
    $stmt = $db->prepare(
        "INSERT INTO Notification (user_id, auction_id, bid_id, type, email_sent)
         VALUES (?, ?, ?, ?, FALSE)"
    );
    $stmt->execute([$userId, $auctionId, $bidId, $type]);
    return (int)$db->lastInsertId();
}

/**
 * Queue email sending (simple: insert unread notifications; email_worker will send them).
 * If you want immediate send, call send_email_now($to, $subject, $body).
 */
function enqueue_bid_notifications(PDO $db, int $auctionId, int $triggerBidId, ?int $outbidUserId = null): void {
    // Notify watchers for new bid
    $stmt = $db->prepare(
        "SELECT w.user_id
         FROM Watchlist w
         WHERE w.auction_id = ? AND w.email_notifications = TRUE"
    );
    $stmt->execute([$auctionId]);
    $watchers = $stmt->fetchAll();

    foreach ($watchers as $w) {
        create_notification($db, (int)$w['user_id'], $auctionId, $triggerBidId, 'new_bid');
    }

    // Notify outbid user if provided
    if ($outbidUserId !== null) {
        create_notification($db, $outbidUserId, $auctionId, $triggerBidId, 'outbid');
    }
}

/**
 * Minimal email sender stub. Replace with your mailer.
 */
function send_email_now(string $to, string $subject, string $body): bool {
    // You can use mail(), PHPMailer, or SMTP here.
    // return mail($to, $subject, $body);
    return true;
}
