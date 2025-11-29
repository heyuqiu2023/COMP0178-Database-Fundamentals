<?php
// email_worker.php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/notification_service.php';

/**
 * Process unread notifications and send emails.
 * Run this periodically via cron.
 */

$db = get_db();

// Fetch notifications not sent yet
$stmt = $db->query(
    "SELECT n.notification_id, n.user_id, n.auction_id, n.bid_id, n.type, u.email, a.title
     FROM Notification n
     JOIN User u ON u.user_id = n.user_id
     JOIN Auction a ON a.auction_id = n.auction_id
     WHERE n.email_sent = FALSE
     ORDER BY n.notification_id ASC
     LIMIT 100"
);
$notifications = $stmt->fetchAll();

foreach ($notifications as $n) {
    $subject = '';
    $body = '';

    switch ($n['type']) {
        case 'new_bid':
            $subject = 'New bid on ' . $n['title'];
            $body    = "A new bid has been placed on the auction: {$n['title']}.\nVisit your watchlist to check details.";
            break;
        case 'outbid':
            $subject = 'You have been outbid on ' . $n['title'];
            $body    = "Your bid has been surpassed in the auction: {$n['title']}.\nConsider placing a higher bid.";
            break;
        case 'auction_ended':
            $subject = 'Auction ended: ' . $n['title'];
            $body    = "The auction has ended.\nCheck your account for the outcome.";
            break;
        case 'reserve_not_met':
            $subject = 'Reserve not met: ' . $n['title'];
            $body    = "The reserve price was not met for the auction: {$n['title']}.";
            break;
        default:
            $subject = 'Auction notification';
            $body    = 'There is an update regarding your auction.';
    }

    // Send email
    $sent = send_email_now($n['email'], $subject, $body);

    if ($sent) {
        $upd = $db->prepare("UPDATE Notification SET email_sent = TRUE WHERE notification_id = ?");
        $upd->execute([(int)$n['notification_id']]);
    }
}
