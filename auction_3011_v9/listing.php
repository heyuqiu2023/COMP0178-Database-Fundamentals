<?php
/**
 * Secure and functional listing page.
 * - Uses PDO prepared statements to fetch auction details and bids.
 * - Displays auction info, seller info, current price, and time remaining.
 * - Shows watchlist controls when user is logged in.
 * - Restricts bidding to logged‑in buyers only (is_seller == 0).
 */

include_once('header.php');
require('utilities.php');

// Start session to access user login and role
session_start();

// Include database connection (assumes $pdo is defined)
require_once 'db_connection.php';

// Get auction ID from query parameter
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;

if ($auction_id <= 0) {
    echo '<div class="container"><div class="alert alert-warning mt-5">Invalid auction ID.</div></div>';
    include_once('footer.php');
    exit;
}

// Fetch auction with seller info and category
$stmt = $pdo->prepare(
    "SELECT a.*, u.username AS seller_name, u.email AS seller_email, c.category_name
     FROM auction a
     JOIN user u ON u.user_id = a.seller_id
     LEFT JOIN category c ON c.id = a.category
     WHERE a.auction_id = ?"
);
$stmt->execute([$auction_id]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    echo '<div class="container"><div class="alert alert-warning mt-5">Auction not found.</div></div>';
    include_once('footer.php');
    exit;
}

// Extract fields
$title         = $auction['name'] ?? '';
$description   = $auction['description'] ?? '';
$start_price   = (float)($auction['start_price'] ?? 0);
$reserve_price = (float)($auction['reserve_price'] ?? 0);
$picture       = $auction['picture'] ?? '';
$seller_name   = $auction['seller_name'] ?? '';
$seller_email  = $auction['seller_email'] ?? '';
$status        = $auction['status']; // 0=active,1=success,2=failed

// End time and current time
$end_time = new DateTime($auction['end_date']);
$now      = new DateTime();
$is_active = ($status == 0) && ($now < $end_time);

// Fetch highest bid (if any) and count bids
$stmt = $pdo->prepare("SELECT price FROM bid WHERE auction_id = ? ORDER BY price DESC, bid_time ASC LIMIT 1");
$stmt->execute([$auction_id]);
$highest_bid = $stmt->fetch(PDO::FETCH_ASSOC);
$current_price = $highest_bid ? (float)$highest_bid['price'] : $start_price;

$stmt = $pdo->prepare("SELECT COUNT(*) AS bid_count FROM bid WHERE auction_id = ?");
$stmt->execute([$auction_id]);
$num_bids = (int)$stmt->fetch(PDO::FETCH_ASSOC)['bid_count'];

// Remaining time string
$time_remaining = '';
if ($is_active) {
    $interval = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($interval);
}

// Determine if current user is watching
$has_session = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$watching    = false;
if ($has_session && isset($_SESSION['user_id'])) {
    $watch_stmt = $pdo->prepare("SELECT 1 FROM watchlist WHERE user_id = ? AND auction_id = ?");
    $watch_stmt->execute([$_SESSION['user_id'], $auction_id]);
    $watching = (bool)$watch_stmt->fetchColumn();
}

// Handle auction ending: if active flag false but status still 0, update status and send notifications
if (!$is_active && $status == 0) {
    // Determine highest bidder
    $highest_stmt = $pdo->prepare("SELECT user_id, price FROM bid WHERE auction_id = ? ORDER BY price DESC, bid_time ASC LIMIT 1");
    $highest_stmt->execute([$auction_id]);
    $high_bid = $highest_stmt->fetch(PDO::FETCH_ASSOC);
    if ($high_bid && $high_bid['price'] >= $reserve_price) {
        // Success: update status to 1
        $update_stmt = $pdo->prepare("UPDATE auction SET status = 1 WHERE auction_id = ?");
        $update_stmt->execute([$auction_id]);
        // TODO: Send emails to buyer and seller here
    } else {
        // Failed: update status to 2
        $update_stmt = $pdo->prepare("UPDATE auction SET status = 2 WHERE auction_id = ?");
        $update_stmt->execute([$auction_id]);
        // TODO: Send failure email to seller here
    }
    $is_active = false;
}
?>

<div class="container mt-5">
  <div class="row">
    <!-- Left column: title, seller, description, image -->
    <div class="col-md-8">
      <h2 class="mb-3"><?php echo htmlspecialchars($title); ?></h2>
      <p class="text-muted">Sold by <?php echo htmlspecialchars($seller_email ?: $seller_name); ?></p>
      <div class="mb-3">
        <?php echo nl2br(htmlspecialchars($description)); ?>
      </div>
      <?php if ($picture): ?>
        <img class="img-fluid" src="<?php echo htmlspecialchars($picture); ?>" alt="<?php echo htmlspecialchars($title); ?>">
      <?php endif; ?>
    </div>
    <!-- Right column: watchlist, pricing, time, bidding -->
    <div class="col-md-4">
      <?php if ($is_active): ?>
        <!-- Watchlist controls: only show for logged in users -->
        <?php if ($has_session): ?>
          <div id="watch_nowatch" <?php if ($watching) echo 'style="display:none"'; ?>>
            <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addToWatchlist()">+ Add to watchlist</button>
          </div>
          <div id="watch_watching" <?php if (!$watching) echo 'style="display:none"'; ?>>
            <button type="button" class="btn btn-success btn-sm mb-1" disabled>Watching</button>
            <button type="button" class="btn btn-danger btn-sm mb-3" onclick="removeFromWatchlist()">Remove watch</button>
          </div>
        <?php endif; ?>
      <?php endif; ?>
      <p>Auction ends: <?php echo htmlspecialchars($end_time->format('j M Y H:i')); ?>
        <?php if ($is_active): ?> (<?php echo htmlspecialchars($time_remaining); ?> left)<?php endif; ?></p>
      <p class="lead">Current bid: £<?php echo number_format($current_price, 2); ?></p>
      <p>Number of bids: <?php echo $num_bids; ?></p>
      <?php if ($is_active): ?>
        <?php
        // Show bid form only if logged in and user is a buyer (is_seller = 0)
        $can_bid = false;
        if ($has_session && isset($_SESSION['is_seller'])) {
            $can_bid = ($_SESSION['is_seller'] == 0);
        }
        ?>
        <?php if ($can_bid): ?>
          <form method="POST" action="place_bid.php?item_id=<?php echo $auction_id; ?>">
            <div class="input-group mb-2">
              <div class="input-group-prepend"><span class="input-group-text">£</span></div>
              <input type="number" step="0.01" min="<?php echo htmlspecialchars($current_price); ?>" name="new_price" class="form-control" required>
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

<!-- Watchlist AJAX handlers -->
<script>
function addToWatchlist() {
  $.ajax('watchlist_funcs.php', {
    type: 'POST',
    data: { functionname: 'add_to_watchlist', auction: <?php echo $auction_id; ?>, user: <?php echo $has_session ? (int)$_SESSION['user_id'] : 'null'; ?> },
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
    data: { functionname: 'remove_from_watchlist', auction: <?php echo $auction_id; ?>, user: <?php echo $has_session ? (int)$_SESSION['user_id'] : 'null'; ?> },
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