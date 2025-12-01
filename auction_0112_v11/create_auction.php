<?php include_once('header.php'); ?>
<?php
// Default end date for the datetime-local input: one day from now
$default_end = date('Y-m-d\TH:i', strtotime('+1 day'));
// Minimum allowed end date (now) - prevents selecting a past time in supporting browsers
$min_end = date('Y-m-d\TH:i');
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
              step="60" min="<?php echo $min_end; ?>"
              value="<?php echo isset($_SESSION['old_input']['end_date']) ? htmlspecialchars($_SESSION['old_input']['end_date']) : $default_end; ?>">
                      <small id="endDateHelp" class="form-text text-muted"><span class="text-danger">* Required.</span> Day and time for the auction to end.</small>

                      <!-- Quick-select buttons for common offsets -->
                      <div class="btn-group btn-group-sm mt-2" role="group" aria-label="Quick time selectors">
                        <button type="button" id="quickPlus1h" class="btn btn-outline-secondary">+1h</button>
                        <button type="button" id="quickPlus1d" class="btn btn-outline-secondary">+1d</button>
                      </div>
                    </div>
          </div>
          <div class="form-group row">
            <label for="auctionImages" class="col-sm-3 col-form-label text-right">Images</label>
            <div class="col-sm-9">
              <input type="file" class="form-control-file" id="auctionImages" name="images[]" accept="image/*" multiple>
              <small id="imagesHelp" class="form-text text-muted">Optional. You can upload up to 5 images (jpg, png, gif). Each image max 2MB.</small>
              <div id="imagesPreview" class="mt-2 d-flex flex-wrap"></div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary form-control">Create Auction</button>
        </form>
      </div>
    </div>
  </div>

</div> <!-- End container -->

<script>
// Initialize flatpickr for consistent datetime selection (minute precision)
window.addEventListener('load', function(){
  var el = document.getElementById('auctionEndDate');
  if (!el) return;

  // PHP-provided defaults
  var phpDefault = '<?php echo $default_end; ?>';
  var phpMin = '<?php echo $min_end; ?>';
  var phpOld = '<?php echo isset($_SESSION['old_input']['end_date']) ? htmlspecialchars($_SESSION['old_input']['end_date']) : ''; ?>';

  // choose initial date: old input if present, else default
  var initial = phpOld || phpDefault;

  // wait for flatpickr to be available
  if (typeof flatpickr === 'undefined') {
    // if flatpickr not loaded, do nothing; browser will still show native control or text fallback
    return;
  }

  var fp = flatpickr(el, {
    enableTime: true,
    noCalendar: false,
    // Use escaped T (\T) so flatpickr does not insert literal quotes around it
    dateFormat: "Y-m-d\\TH:i",
    time_24hr: true,
    minuteIncrement: 1,
    defaultDate: initial,
    minDate: phpMin,
    onReady: function(selectedDates, dateStr, instance) {
      if (dateStr) {
        // ensure no accidental quotes are present
        el.value = dateStr.replace(/'/g, '');
      }
    },
    onChange: function(selectedDates, dateStr) {
      // keep the input value in the desired format for backend parsing (strip any quotes)
      el.value = dateStr ? dateStr.replace(/'/g, '') : '';
    }
  });

  // Quick-select buttons: +1 hour and +1 day
  function parseMinDate(str){
    // flat string like YYYY-MM-DDTHH:MM -> safe for Date constructor in modern browsers
    try {
      return str ? new Date(str) : null;
    } catch(e){
      return null;
    }
  }

  var minDateObj = parseMinDate(phpMin);

  function addMinutesToPicker(minutes){
    var cur = fp.selectedDates.length ? fp.selectedDates[0] : new Date();
    var newDate = new Date(cur.getTime() + minutes * 60000);
    // ensure newDate >= minDateObj
    if (minDateObj && newDate < minDateObj) {
      newDate = new Date(minDateObj.getTime());
    }
    fp.setDate(newDate, true);
  }

  var btn1h = document.getElementById('quickPlus1h');
  var btn1d = document.getElementById('quickPlus1d');
  if (btn1h) btn1h.addEventListener('click', function(){ addMinutesToPicker(60); });
  if (btn1d) btn1d.addEventListener('click', function(){ addMinutesToPicker(60*24); });
});
</script>

<script>
// Improved cumulative file selection for #auctionImages
document.addEventListener('DOMContentLoaded', function(){
  var input = document.getElementById('auctionImages');
  var preview = document.getElementById('imagesPreview');
  var form = document.querySelector('form[action="create_auction_result.php"]');
  if (!input || !preview || !form) return;

  var MAX_TOTAL = 5;
  // maintain a global accumulator so it persists if other scripts re-run
  if (!window._accFiles) window._accFiles = new DataTransfer();

  function render(){
    preview.innerHTML = '';
    Array.from(window._accFiles.files).forEach(function(file, idx){
      var div = document.createElement('div');
      div.className = 'm-1 position-relative';
      div.style.width = '100px';
      div.dataset.name = file.name;

      var img = document.createElement('img');
      img.style.width = '100%';
      img.style.height = '80px';
      img.style.objectFit = 'cover';
      img.alt = file.name;
      var reader = new FileReader();
      reader.onload = function(e){ img.src = e.target.result; };
      reader.readAsDataURL(file);

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn btn-sm btn-danger position-absolute';
      removeBtn.style.top = '2px';
      removeBtn.style.right = '2px';
      removeBtn.textContent = '×';
      removeBtn.title = 'Remove this file';
      removeBtn.addEventListener('click', function(){
        var dt = new DataTransfer();
        Array.from(window._accFiles.files).forEach(function(f){
          if (!(f.name === file.name && f.size === file.size && f.type === file.type)) dt.items.add(f);
        });
        window._accFiles = dt;
        render();
      });

      div.appendChild(img);
      div.appendChild(removeBtn);
      preview.appendChild(div);
    });

    var remaining = MAX_TOTAL - window._accFiles.files.length;
    if (remaining <= 0){
      input.disabled = true;
      input.title = '已达到 ' + MAX_TOTAL + ' 张上限，删除后可上传';
    } else {
      input.disabled = false;
      input.title = '';
    }
  }

  input.addEventListener('change', function(e){
    var files = Array.from(e.target.files || []);
    if (files.length === 0) return;
    var canAdd = MAX_TOTAL - window._accFiles.files.length;
    if (canAdd <= 0){
      alert('已达到 ' + MAX_TOTAL + ' 张图片上限。');
      e.target.value = '';
      return;
    }
    var added = 0;
    files.forEach(function(f){
      if (added >= canAdd) return;
      if (f.size > 2*1024*1024) { console.warn('Skip file (too large):', f.name); return; }
      window._accFiles.items.add(f);
      added++;
    });
    // clear native input so selecting same files again will fire change
    e.target.value = '';
    render();
    if (added < files.length) alert('只接受前 ' + added + ' 个文件（最大总数 ' + MAX_TOTAL + '）。');
  });

  // before submit, write accumulated files back to input
  form.addEventListener('submit', function(){
    try{ input.files = window._accFiles.files; } catch(err){ console.warn('assigning accumulated files failed', err); }
  });

  // initial render
  render();
});
</script>

<?php include_once('footer.php'); ?>