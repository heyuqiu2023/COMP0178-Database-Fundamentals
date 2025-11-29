<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<?php
// Get info from the URL: item_id parameter determines which item to display
$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : null;

// TODO: Use $item_id to query the database and retrieve item details.

// Placeholder data for demonstration purposes
$title = 'Placeholder title';
$description = 'Description of the item goes here.  Replace this with the item\'s description retrieved from the database.';
$current_price = 30.50;
$num_bids = 1;
$end_time = new DateTime();
$end_time->add(new DateInterval('P2DT3H'));

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