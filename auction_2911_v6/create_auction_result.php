<?php include_once('header.php'); ?>

<div class="container">
  <h2 class="my-3">Auction creation result</h2>

<?php
// When the form is submitted from create_auction.php, the POST values
// will be available here.  In a real application this page would
// validate the inputs, insert a new row into the auctions table and
// redirect or show success/failure messages.  For now we simply
// display a confirmation message using the submitted title and end date.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title  = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '(no title)';
  $end_date = isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : '(unspecified)';
  echo '<div class="alert alert-success">Your auction "' . $title . '" has been created and will end on ' . $end_date . ' (placeholder).</div>';
  echo '<p>You can now <a href="browse.php">return to browse listings</a> or <a href="create_auction.php">create another auction</a>.</p>';
} else {
  echo '<div class="alert alert-warning">No auction details were received.  Please create your auction from the <a href="create_auction.php">Create Auction</a> page.</div>';
}
?>

</div>

<?php include_once('footer.php'); ?>