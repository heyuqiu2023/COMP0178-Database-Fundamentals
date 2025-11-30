<?php
require_once 'db.php';

// Minimal email/notification plumbing. Replace mail() or integrate SMTP later.
function queue_notification(int $user_id, int $auction_id, ?int $bid_id, string $type): void {
  $pdo = get_db();
  $stmt = $pdo->prepare(
    "INSERT INTO Notification (user_id, auction_id, bid_id, type) VALUES (?, ?, ?, ?)"
  );
  $stmt->execute([$user_id, $auction_id, $bid_id, $type]);
}

// Optional: immediate email placeholder
function send_email(string $to, string $subject, string $body): void {
  // Stub for coursework demo. In real use, configure SMTP.
  // mail($to, $subject, $body);
}

// Helper: get user email by id
function user_email(int $user_id): ?string {
  $pdo = get_db();
  $stmt = $pdo->prepare("SELECT email FROM User WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $row = $stmt->fetch();
  return $row ? $row['email'] : null;
}
