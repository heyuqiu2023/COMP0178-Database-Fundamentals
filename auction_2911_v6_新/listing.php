<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<?php
// Get info from the URL: item_id parameter determines which item to display
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : null;

// Fetch item details from DB
$title = 'Item not found';
$description = '';
$current_price = 0.00;
$num_bids = 0;
$end_time = new DateTime();
$image_urls = null;

if ($item_id) {
  $db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'auction';
  $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
  if (!$mysqli->connect_errno) {
    $sql = "SELECT a.*, u.username AS seller_name, (SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bids, (SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) AS current_price FROM Auction a JOIN `User` u ON a.seller_id = u.user_id WHERE a.auction_id = " . intval($item_id) . " LIMIT 1";
    $res = $mysqli->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
      $title = $row['title'];
      $description = $row['description'];
      $current_price = $row['current_price'];
      $num_bids = $row['bids'];
      $end_time = new DateTime($row['end_time']);
      $image_urls = $row['image_url'];
      $seller_name = isset($row['seller_name']) ? $row['seller_name'] : null;
      $category_id = isset($row['category_id']) ? $row['category_id'] : null;
    }
    if ($res) $res->free();
    $mysqli->close();
  }
}

// Determine if the auction is still active
$now = new DateTime();
$is_active = $now < $end_time;
$time_remaining = '';
if ($is_active) {
  $time_to_end = date_diff($now, $end_time);
  $time_remaining = display_time_remaining($time_to_end);
}

// TODO: If the user has a session, use it to determine if they are watching this item.
// For now, this is hardcoded.
$has_session = isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
$watching = false;
?>

<div class="container">

  <div class="row"> <!-- Row #1 with auction title + watch button -->
    <div class="col-sm-8"> <!-- Left col -->
        <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>
        <?php
          // Show seller info
          if (!empty($seller_name)) {
            echo '<p class="mb-2"><small class="text-muted">Sold by ' . htmlspecialchars($seller_name) . '</small></p>';
          }
          // Show image gallery if available
          if (!empty($image_urls)) {
            $parts = explode(',', $image_urls);
            $first = trim($parts[0]);
            if ($first !== '') {
              // normalize path using helper in utilities.php
              $main = normalize_image_src($first);
              echo '<div class="mb-3"><img src="' . htmlspecialchars($main) . '" alt="' . htmlspecialchars($title) . '" style="width:100%; max-height:420px; object-fit:cover; border-radius:0.5rem;"></div>';
              if (count($parts) > 1) {
                echo '<div class="d-flex">';
                foreach ($parts as $p) {
                  $p = trim($p);
                  if ($p === '') continue;
                  $thumb = normalize_image_src($p);
                  echo '<div class="mr-2" style="width:80px; height:80px; overflow:hidden; border-radius:4px;"><img src="' . htmlspecialchars($thumb) . '" style="width:100%; height:100%; object-fit:cover;"></div>';
                }
                echo '</div>';
              }
            }
          } else {
            // No image_urls: use category-specific placeholder if known
            $cat_map = [1 => 'img/category_electronics.svg', 2 => 'img/category_fashion.svg', 3 => 'img/category_books.svg', 4 => 'img/category_sports.svg', 5 => 'img/category_home.svg'];
            if (!empty($category_id) && isset($cat_map[$category_id])) {
              echo '<div class="mb-3"><img src="' . htmlspecialchars(normalize_image_src($cat_map[$category_id])) . '" alt="' . htmlspecialchars($title) . '" style="width:100%; max-height:420px; object-fit:cover; border-radius:0.5rem;"></div>';
            }
          }
        ?>
      </div>
    <div class="col-sm-4 align-self-center"> <!-- Right col -->
    <?php if ($is_active): ?>
      <div id="watch_nowatch" <?php if ($has_session && $watching) echo 'style="display: none"'; ?>>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addToWatchlist()">+ Add to watchlist</button>
      </div>
      <div id="watch_watching" <?php if (!$has_session || !$watching) echo 'style="display: none"'; ?>>
        <button type="button" class="btn btn-success btn-sm" disabled>Watching</button>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeFromWatchlist()">Remove watch</button>
      </div>
    <?php endif; ?>
    </div>
  </div>

  <div class="row"> <!-- Row #2 with auction description + bidding info -->
    <div class="col-sm-8"> <!-- Left col with item info -->
      <div class="itemDescription">
        <?php echo nl2br(htmlspecialchars($description)); ?>
      </div>
    </div>

    <div class="col-sm-4"> <!-- Right col with bidding info -->
      <?php if (!$is_active): ?>
        <p>This auction ended on <?php echo htmlspecialchars($end_time->format('j M H:i')); ?></p>
        <!-- TODO: Print the result of the auction here -->
      <?php else: ?>
        <p>Auction ends <?php echo htmlspecialchars($end_time->format('j M H:i')) . ' (' . htmlspecialchars($time_remaining) . ')'; ?></p>
        <p class="lead">Current bid: £<?php echo number_format($current_price, 2); ?></p>
        <!-- Bidding form -->
        <form method="POST" action="place_bid.php">
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text">£</span>
            </div>
            <input type="number" class="form-control" id="bid" name="bid" min="0" step="0.01" required>
          </div>
          <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
          <button type="submit" class="btn btn-primary form-control mt-2">Place bid</button>
        </form>
      <?php endif; ?>
    </div> <!-- End of right col with bidding info -->

  </div> <!-- End of row #2 -->

</div> <!-- End container -->

<?php include_once('footer.php'); ?>

<script>
// JavaScript functions: addToWatchlist and removeFromWatchlist.

function addToWatchlist() {
  // Perform an asynchronous call to a PHP function using POST.  Sends item ID as an argument.
  $.ajax('watchlist_funcs.php', {
    type: 'POST',
    data: {functionname: 'add_to_watchlist', arguments: [<?php echo json_encode($item_id); ?>]},
    success: function (obj, textstatus) {
      console.log('Success');
      var objT = obj.trim();
      if (objT === 'success') {
        $('#watch_nowatch').hide();
        $('#watch_watching').show();
      } else {
        var mydiv = document.getElementById('watch_nowatch');
        mydiv.appendChild(document.createElement('br'));
        mydiv.appendChild(document.createTextNode('Add to watch failed. Try again later.'));
      }
    },
    error: function (obj, textstatus) {
      console.log('Error');
    }
  });
}

function removeFromWatchlist() {
  $.ajax('watchlist_funcs.php', {
    type: 'POST',
    data: {functionname: 'remove_from_watchlist', arguments: [<?php echo json_encode($item_id); ?>]},
    success: function (obj, textstatus) {
      console.log('Success');
      var objT = obj.trim();
      if (objT === 'success') {
        $('#watch_watching').hide();
        $('#watch_nowatch').show();
      } else {
        var mydiv = document.getElementById('watch_watching');
        mydiv.appendChild(document.createElement('br'));
        mydiv.appendChild(document.createTextNode('Watch removal failed. Try again later.'));
      }
    },
    error: function (obj, textstatus) {
      console.log('Error');
    }
  });
}
</script>