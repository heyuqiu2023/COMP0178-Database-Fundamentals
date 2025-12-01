<?php
// 通知相关的通用函数：写入通知表 + 模拟发邮件

require_once 'db_connection.php';

// 写入一条通知到 Notification 表
// $user_id    接收用户
// $auction_id 相关拍卖（可以为 null）
// $bid_id     相关出价（可以为 null）
// $type       通知类型：new_bid / outbid / auction_ended / reserve_not_met / winner_confirmed / winner_declined ...
// $content    文本内容（可以为 null，展示时也可以根据 type + ID 动态生成）
function queue_notification($user_id, $auction_id, $bid_id, $type, $content = null) {
    global $pdo;
    $stmt = $pdo->prepare(
        "INSERT INTO Notification (user_id, auction_id, bid_id, type, content)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$user_id, $auction_id, $bid_id, $type, $content]);
}

// 模拟发邮件：这里只是 echo 一段内容，不真正发邮件
function send_email($to, $subject, $body) {
    echo '<div class="alert alert-info mt-2">';
    echo '<strong>Simulated email to ' . htmlspecialchars($to) . '</strong><br>';
    echo 'Subject: ' . htmlspecialchars($subject) . '<br>';
    echo 'Body: ' . htmlspecialchars($body);
    echo '</div>';
}

// 处理所有还没发过邮件的通知：生成邮件内容 + 调用 send_email + 标记 email_sent = 1
function process_email_queue() {
    global $pdo;

    $sql = "SELECT n.notification_id, n.type, u.email, a.title
            FROM Notification n
            JOIN User u ON u.user_id = n.user_id
            LEFT JOIN Auction a ON a.auction_id = n.auction_id
            WHERE n.email_sent = 0
            ORDER BY n.created_at ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $title = $row['title'] ?? '';
        $type  = $row['type'];

        switch ($type) {
            case 'new_bid':
                $subject = 'New bid on ' . $title;
                $body    = 'There is a new bid on "' . $title . '".';
                break;
            case 'outbid':
                $subject = 'You were outbid on ' . $title;
                $body    = 'Your bid on "' . $title . '" was surpassed by another bidder.';
                break;
            case 'auction_ended':
                $subject = 'Auction ended: ' . $title;
                $body    = 'The auction "' . $title . '" has ended.';
                break;
            case 'reserve_not_met':
                $subject = 'Reserve not met: ' . $title;
                $body    = 'The auction "' . $title . '" ended without meeting the reserve price.';
                break;
            case 'winner_confirmed':
                $subject = 'Winner confirmed: ' . $title;
                $body    = 'The winner has confirmed the purchase for "' . $title . '".';
                break;
            case 'winner_declined':
                $subject = 'Winner declined: ' . $title;
                $body    = 'The winner has declined the purchase for "' . $title . '".';
                break;
            default:
                $subject = 'Notification about ' . $title;
                $body    = 'You have a new notification regarding "' . $title . '".';
        }

        // 本地模拟发邮件
        send_email($row['email'], $subject, $body);

        // 标记这条通知已发送邮件
        $upd = $pdo->prepare("UPDATE Notification SET email_sent = 1 WHERE notification_id = ?");
        $upd->execute([$row['notification_id']]);
    }
}
