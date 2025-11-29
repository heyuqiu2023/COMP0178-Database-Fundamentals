<?php
require_once __DIR__ . '/db.php';

function queue_notification(int $user_id, int $auction_id, ?int $bid_id, string $type): void {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("
        INSERT INTO Notification (user_id, auction_id, bid_id, type, sent_at, is_read, email_sent)
        VALUES (?, ?, ?, ?, NOW(), 0, 0)
    ");
    $stmt->execute([$user_id, $auction_id, $bid_id, $type]);

    send_email_for_notification($pdo->lastInsertId());
}

function send_email_for_notification(int $notification_id): void {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("
        SELECT n.type, u.email, u.username, a.title
        FROM Notification n
        JOIN User u ON u.user_id = n.user_id
        JOIN Auction a ON a.auction_id = n.auction_id
        WHERE n.notification_id = ?
    ");
    $stmt->execute([$notification_id]);
    $row = $stmt->fetch();
    if (!$row) return;

    $subject = "[Auction] " . ucfirst(str_replace('_', ' ', $row['type']));
    $body = "Hi {$row['username']},\n\nAuction: {$row['title']}\nType: {$row['type']}\n";

    @mail($row['email'], $subject, $body);

    $upd = $pdo->prepare("UPDATE Notification SET email_sent = 1 WHERE notification_id = ?");
    $upd->execute([$notification_id]);
}
