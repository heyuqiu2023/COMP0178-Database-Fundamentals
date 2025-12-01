<?php
/**
 * 拍卖详情页面 (供买家查看并出价，卖家查看拍品情况)
 * - 使用 PDO 预编译查询拍卖详情和出价。
 * - 显示拍卖信息、卖家信息、当前价格及剩余时间。
 * - 登录用户可加入关注列表；只有买家才能出价。
 */

include_once('header.php');
require('utilities.php');

session_start();
require_once 'db_connection.php';

// 获取拍卖ID
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;
if ($auction_id <= 0) {
    echo '<div class="container"><div class="alert alert-warning mt-5">Invalid auction ID.</div></div>';
    include_once('footer.php');
    exit;
}

// 查询拍卖信息及卖家、类别信息
$stmt = $pdo->prepare(
    "SELECT a.*, u.username AS seller_name, u.email AS seller_email, c.category_name
     FROM Auction a
       JOIN User u ON u.user_id = a.seller_id
       LEFT JOIN Category c ON c.category_id = a.category_id
     WHERE a.auction_id = ?"
);
$stmt->execute([$auction_id]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    echo '<div class="container"><div class="alert alert-warning mt-5">Auction not found.</div></div>';
    include_once('footer.php');
    exit;
}

// 读取拍卖字段
$title         = $auction['title'] ?? '';
$description   = $auction['description'] ?? '';
$start_price   = (float)($auction['starting_price'] ?? 0);
$reserve_price = (float)($auction['reserve_price'] ?? 0);
$picture       = $auction['img_url'] ?? '';
$seller_name   = $auction['seller_name'] ?? '';
$seller_email  = $auction['seller_email'] ?? '';
$status        = $auction['status'] ?? 'active'; // 'active', 'ended', 'cancelled'

// 结束时间与当前时间比较判断是否还在进行
$end_time = new DateTime($auction['end_time']);
$now      = new DateTime();
$is_active = ($status === 'active') && ($now < $end_time);

// 查询最高出价与出价总数
$stmt = $pdo->prepare("SELECT bid_amount FROM Bid WHERE auction_id = ? ORDER BY bid_amount DESC, bid_time ASC LIMIT 1");
$stmt->execute([$auction_id]);
$highest_bid = $stmt->fetch(PDO::FETCH_ASSOC);
$current_price = $highest_bid ? (float)$highest_bid['bid_amount'] : $start_price;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM Bid WHERE auction_id = ?");
$stmt->execute([$auction_id]);
$num_bids = (int)$stmt->fetchColumn();

// 计算剩余时间
$time_remaining = '';
if ($is_active) {
    $interval = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($interval);
}

// 判断当前用户是否在关注此拍品
$has_session = isset($_SESSION['user_id']);
$watching    = false;
if ($has_session) {
    $watch_stmt = $pdo->prepare("SELECT 1 FROM Watchlist WHERE user_id = ? AND auction_id = ?");
    $watch_stmt->execute([$_SESSION['user_id'], $auction_id]);
    $watching = (bool)$watch_stmt->fetchColumn();
}

// 若拍卖已结束但状态仍为 active，则更新状态并根据最高出价判断成功与否
if (!$is_active && $status === 'active') {
    // 查找最高出价人
    $highest_stmt = $pdo->prepare("SELECT user_id, bid_amount FROM Bid WHERE auction_id = ? ORDER BY bid_amount DESC, bid_time ASC LIMIT 1");
    $highest_stmt->execute([$auction_id]);
    $high_bid = $highest_stmt->fetch(PDO::FETCH_ASSOC);
    if ($high_bid && $high_bid['bid_amount'] >= $reserve_price) {
        // 成功：更新状态为 ended
        $update_stmt = $pdo->prepare("UPDATE Auction SET status = 'ended' WHERE auction_id = ?");
        $update_stmt->execute([$auction_id]);
        // TODO: 给买家和卖家发送成功通知
    } else {
        // 流拍：更新状态为 cancelled
        $update_stmt = $pdo->prepare("UPDATE Auction SET status = 'cancelled' WHERE auction_id = ?");
        $update_stmt->execute([$auction_id]);
        // TODO: 给卖家发送流拍通知
    }
    $is_active = false;
}

?>
<div class="container mt-5">
  <div class="row">
    <!-- 左侧：标题、卖家、描述及图片 -->
    <div class="col-md-8">
      <h2 class="mb-3"><?php echo htmlspecialchars($title); ?></h2>
      <p class="text-muted">
        Sold by <?php echo htmlspecialchars($seller_email ?: $seller_name); ?>
      </p>
      <div class="mb-3">
        <?php echo nl2br(htmlspecialchars($description)); ?>
      </div>
      <?php if ($picture): ?>
        <img class="img-fluid" src="<?php echo htmlspecialchars($picture); ?>"
             alt="<?php echo htmlspecialchars($title); ?>">
      <?php endif; ?>
    </div>

    <!-- 右侧：关注列表、价格、剩余时间、出价 -->
    <div class="col-md-4">
      <?php if ($is_active): ?>
        <!-- 关注列表按钮：仅登录用户可见 -->
        <?php if ($has_session): ?>
          <div id="watch_nowatch" <?php if ($watching) echo 'style="display:none"'; ?>>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3"
                    onclick="addToWatchlist()">+ Add to watchlist</button>
          </div>
          <div id="watch_watching" <?php if (!$watching) echo 'style="display:none"'; ?>>
            <button type="button" class="btn btn-success btn-sm mb-1" disabled>Watching</button>
            <button type="button" class="btn btn-danger btn-sm mb-3"
                    onclick="removeFromWatchlist()">Remove watch</button>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <p>Auction ends:
        <?php echo htmlspecialchars($end_time->format('j M Y H:i')); ?>
        <?php if ($is_active): ?>
          (<?php echo htmlspecialchars($time_remaining); ?> left)
        <?php endif; ?>
      </p>
      <p class="lead">Current bid: £<?php echo number_format($current_price, 2); ?></p>
      <p>Number of bids: <?php echo $num_bids; ?></p>

      <?php if ($is_active): ?>
        <?php
        // 只有登录且角色为买家 (is_seller == 0) 才能出价
        $can_bid = false;
        if ($has_session && isset($_SESSION['is_seller'])) {
            $can_bid = ($_SESSION['is_seller'] == 0);
        }
        ?>
        <?php if ($can_bid): ?>
          <!-- 出价表单 -->
          <form method="POST" action="place_bid.php">
            <!-- 隐藏字段传递拍卖ID -->
            <input type="hidden" name="auction_id" value="<?php echo htmlspecialchars($auction_id); ?>">
            <div class="input-group mb-2">
              <div class="input-group-prepend"><span class="input-group-text">£</span></div>
              <!-- 出价金额的最小值是当前出价 -->
              <input type="number" step="0.01"
                     min="<?php echo htmlspecialchars($current_price); ?>"
                     name="bid" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Place bid</button>
          </form>
        <?php else: ?>
          <p class="text-muted">Only buyers can place bids. Please log in as a buyer to bid.</p>
        <?php endif; ?>
      <?php else: ?>
        <p class="text-muted">This auction has ended.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- 关注列表的 AJAX 调用 -->
<script>
function addToWatchlist() {
  $.ajax('watchlist_funcs.php', {
    type: 'POST',
    data: {
      functionname: 'add_to_watchlist',
      auction: <?php echo $auction_id; ?>,
      user: <?php echo $has_session ? (int)$_SESSION['user_id'] : 'null'; ?>
    },
    success: function(res) {
      if (res.trim() === 'success') {
        $('#watch_nowatch').hide();
        $('#watch_watching').show();
      }
    }
  });
}
function removeFromWatchlist() {
  $.ajax('watchlist_funcs.php', {
    type: 'POST',
    data: {
      functionname: 'remove_from_watchlist',
      auction: <?php echo $auction_id; ?>,
      user: <?php echo $has_session ? (int)$_SESSION['user_id'] : 'null'; ?>
    },
    success: function(res) {
      if (res.trim() === 'success') {
        $('#watch_watching').hide();
        $('#watch_nowatch').show();
      }
    }
  });
}
</script>

<?php include_once('footer.php'); ?>
