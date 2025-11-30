<?php
require_once 'db.php';
require_once 'notify.php';

// Pull unsent notifications and send emails (simulate)
$pdo = get_db();
$stmt = $pdo->query("
  SELECT n.notification_id, n.user_id, n.auction_id, n.bid_id, n.type, u.email, a.title
  FROM Notification n
  JOIN User u ON u.user_id = n.user_id
  JOIN Auction a ON a.auction_id = n.auction_id
  WHERE n.email_sent = FALSE
  ORDER BY n.sent_at ASC
");
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
  $subject = '';
  $body = '';

  switch ($row['type']) {
    case 'new_bid':
      $subject = "New bid on {$row['title']}";
      $body = "A new bid has been placed on the auction \"{$row['title']}\".";
      break;
    case 'outbid':
      $subject = "You were outbid on {$row['title']}";
      $body = "Your bid has been surpassed on \"{$row['title']}\". Consider bidding again.";
      break;
    case 'auction_ended':
      $subject = "Auction ended: {$row['title']}";
      $body = "The auction \"{$row['title']}\" has ended.";
      break;
    case 'reserve_not_met':
      $subject = "Reserve not met: {$row['title']}";
      $body = "The auction \"{$row['title']}\" ended without meeting the reserve price.";
      break;
    default:
      $subject = "Notification for {$row['title']}";
      $body = "You have a new notification.";
  }

  // Send (simulated)
  send_email($row['email'], $subject, $body);

  // Mark as sent
  $upd = $pdo->prepare("UPDATE Notification SET email_sent = TRUE WHERE notification_id = ?");
  $upd->execute([(int)$row['notification_id']]);
}
