<?php include_once('header.php'); ?>
<?php require_once('db_connection.php'); ?>
<?php require_once('notify.php'); ?>

<div class="container my-4">
  <h2>通知中心</h2>

<?php
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    echo "<div class='alert alert-warning'>请先登录以查看通知。</div>";
} else {
    $user_id = $_SESSION['user_id'];

    // 1. 查询当前用户所有通知（最新的在前）
    $stmt = $pdo->prepare("
        SELECT n.notification_id, n.type, n.content, n.is_read, n.created_at,
               a.title AS auction_title
        FROM Notification n
        LEFT JOIN Auction a ON n.auction_id = a.auction_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. 把该用户所有通知标记为已读
    $updateStmt = $pdo->prepare("UPDATE Notification SET is_read = 1 WHERE user_id = ?");
    $updateStmt->execute([$user_id]);

    if (!$notifications) {
        echo "<p>暂无通知。</p>";
    } else {
        echo "<ul class='list-group'>";
        foreach ($notifications as $notif) {
            $type    = $notif['type'];
            $title   = $notif['auction_title'] ?? '';
            $created = $notif['created_at'];
            $is_read = (int)$notif['is_read'];

            // 根据类型生成一段文字
            switch ($type) {
                case 'new_bid':
                    $message = "There is a new bid on \"{$title}\".";
                    break;
                case 'outbid':
                    $message = "Your bid on \"{$title}\" was surpassed by another bidder.";
                    break;
                case 'auction_ended':
                    $message = "The auction \"{$title}\" has ended.";
                    break;
                case 'reserve_not_met':
                    $message = "The auction \"{$title}\" ended without meeting the reserve price.";
                    break;
                case 'winner_confirmed':
                    $message = "The winner of \"{$title}\" has confirmed the purchase.";
                    break;
                case 'winner_declined':
                    $message = "The winner of \"{$title}\" has declined to purchase.";
                    break;
                default:
                    $message = "You have a new notification about \"{$title}\".";
            }

            // 未读的高亮（第一次进来时有用）
            $item_class = $is_read ? "list-group-item" : "list-group-item list-group-item-info";

            echo "<li class='{$item_class}'>";
            if (!$is_read) {
                echo "<span class='badge badge-primary mr-1'>新</span>";
            }
            echo htmlspecialchars($message) . "<br><small class='text-muted'>{$created}</small>";
            echo "</li>";
        }
        echo "</ul>";
    }

    // 3. 在同一页面模拟“发送邮件”（只会处理还没发过 email 的通知）
    echo "<hr><h4>模拟发送的邮件</h4>";
    process_email_queue();
}
?>
</div>

<?php include_once('footer.php'); ?>
