<?php include_once('header.php'); ?>
<?php
// Default end date for the datetime-local input: one day from now
$default_end = date('Y-m-d\TH:i', strtotime('+1 day'));
?>

<div class="container">

<?php
if (isset($_SESSION['create_errors'])) {
    foreach ($_SESSION['create_errors'] as $error) {
        echo '<div class="alert alert-danger text-center" role="alert" style="margin-top: 20px; font-size: 18px;">' . $error . '</div>';
    }
    unset($_SESSION['create_errors']);
}
?>

  <!-- Create auction form -->
  <div style="max-width: 800px; margin: 10px auto">
    <h2 class="my-3">Create new auction</h2>
    <div class="card">
      <div class="card-body">
        <!-- Note: This form does not yet do any dynamic/client‑side validation of data.
             Validation should be added once database functionality is complete. -->
  <form method="post" action="create_auction_result.php" enctype="multipart/form-data">
          <div class="form-group row">
            <label for="auctionTitle" class="col-sm-3 col-form-label text-right">Title of auction</label>
            <div class="col-sm-9">
              <input type="text" 
                     class="form-control" 
                     id="auctionTitle" 
                     name="title" 
                     placeholder="e.g. Black mountain bike" 
                     required value="<?php echo isset($_SESSION['old_input']['title']) ? htmlspecialchars($_SESSION['old_input']['title']) : ''; ?>">
              <small id="titleHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> A short description of the item you're selling, which will display in listings.</small>
            </div>
          </div>
          <div class="form-group row">
            <label for="auctionDetails" class="col-sm-3 col-form-label text-right">Details</label>
            <div class="col-sm-9">
              <textarea class="form-control" 
                        id="auctionDetails" 
                        name="description" 
                        rows="4" 
                        placeholder="Full details of the listing to help bidders decide if it's what they're looking for." 
                        required><?php echo isset($_SESSION['old_input']['description']) ? htmlspecialchars($_SESSION['old_input']['description']) : ''; ?></textarea>
            </div>
          </div>
          <div class="form-group row">
            <label for="auctionCategory" class="col-sm-3 col-form-label text-right">Category</label>
            <div class="col-sm-9">
              <select class="form-control" id="auctionCategory" name="category" required>
                <option value="" disabled>Choose a category...</option>
                <option value="1" <?php if(isset($_SESSION['old_input']['category']) && $_SESSION['old_input']['category']==1) echo 'selected'; ?>>Electronics</option>
                <option value="2" <?php if(isset($_SESSION['old_input']['category']) && $_SESSION['old_input']['category']==2) echo 'selected'; ?>>Fashion</option>
                <option value="3" <?php if(isset($_SESSION['old_input']['category']) && $_SESSION['old_input']['category']==3) echo 'selected'; ?>>Books</option>
                <option value="4" <?php if(isset($_SESSION['old_input']['category']) && $_SESSION['old_input']['category']==4) echo 'selected'; ?>>Sports</option>
                <option value="5" <?php if(isset($_SESSION['old_input']['category']) && $_SESSION['old_input']['category']==5) echo 'selected'; ?>>Home & garden</option>
             </select>
              <small id="categoryHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Select a category for this item.</small>
            </div>
          </div>
          <div class="form-group row">
            <label for="auctionStartPrice" class="col-sm-3 col-form-label text-right">Starting price (£)</label>
            <div class="col-sm-9">
              <input type="number" step="0.01" min="0" class="form-control" id="auctionStartPrice" name="start_price" required
                     value="<?php echo isset($_SESSION['old_input']['start_price']) ? htmlspecialchars($_SESSION['old_input']['start_price']) : ''; ?>">
              <small id="startBidHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Initial bid amount.</small>
            </div>
          </div>
          <div class="form-group row">
            <label for="auctionReservePrice" class="col-sm-3 col-form-label text-right">Reserve price (£)</label>
            <div class="col-sm-9">
              <input type="number" step="0.01" min="0" class="form-control" id="auctionReservePrice" name="reserve_price" required
                     value="<?php echo isset($_SESSION['old_input']['reserve_price']) ? htmlspecialchars($_SESSION['old_input']['reserve_price']) : ''; ?>">
              <small id="reservePriceHelp" class="form-text text-muted">Optional. Auctions that end below this price will not go through. This value is not displayed in the auction listing.</small>
            </div>
          </div>
          <div class="form-group row">
            <label for="auctionEndDate" class="col-sm-3 col-form-label text-right">End date</label>
            <div class="col-sm-9">
              <input type="datetime-local" class="form-control" id="auctionEndDate" name="end_date"
                     value="<?php echo isset($_SESSION['old_input']['end_date']) ? htmlspecialchars($_SESSION['old_input']['end_date']) : $default_end; ?>">
              <small id="endDateHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Day and time for the auction to end.</small>
            </div>
          </div>
          <div class="form-group row">
            <label for="auctionImages" class="col-sm-3 col-form-label text-right">Images</label>
            <div class="col-sm-9">
              <input type="file" class="form-control-file" id="auctionImages" name="images[]" accept="image/*" multiple>
              <small id="imagesHelp" class="form-text text-muted">Optional. You can upload up to 5 images (jpg, png, gif). Each image max 2MB.</small>
            </div>
          </div>
          <button type="submit" class="btn btn-primary form-control">Create Auction</button>
        </form>
      </div>
    </div>
  </div>

</div> <!-- End container -->

<?php include_once('footer.php'); ?>