<?php 
include_once('header.php'); 
require('utilities.php'); 
date_default_timezone_set('Europe/London'); 
session_start();
?>

<?php
// ========================
// 获取 auction_id
// ========================
$auction_id = isset($_GET['auction_id']) ? intval($_GET['auction_id']) : 0;

// ========================
// 初始化变量
// ========================
$title = 'Item not found';
$description = '';
$current_price = 0.00;
$num_bids = 0;
$img_url = null;
$reserve_price = 0.00;
$seller_id = null;

// ========================
// 连接数据库
// ========================
if ($auction_id) {
  $db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'auction_system';
  $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
  if (!$mysqli->connect_errno) {

    // ========= 获取 auction 信息 =========
    $sql = "SELECT a.*, u.username AS seller_name 
            FROM Auction a 
            JOIN `User` u ON a.seller_id = u.user_id 
            WHERE a.auction_id = $auction_id 
            LIMIT 1";

    $res = $mysqli->query($sql);
    if ($res && $row = $res->fetch_assoc()) {
      $title = $row['title'];
      $description = $row['description'];
      $end_time = new DateTime($row['end_time']);
      $img_url = $row['img_url'];
      $seller_name = $row['seller_name'];
      $category_id = $row['category_id'];
      $status = $row['status'];
      $reserve_price = $row['reserve_price'];
      $seller_id = (int)$row['seller_id'];
      $starting_price = $row['starting_price'];
    }
  }
}

// ========================
// 获取最高出价
// ========================
$sql2 = "SELECT MAX(bid_amount) AS highest_bid 
        FROM Bid 
        WHERE auction_id = $auction_id";
$res2 = $mysqli->query($sql2);
$row2 = $res2->fetch_assoc();
$current_price = $row2['highest_bid'] ? (float)$row2['highest_bid'] : (float)$starting_price;

// ========================
// 获取 bid 数量
// ========================
$sql3 = "SELECT COUNT(*) AS bid_count 
         FROM Bid 
         WHERE auction_id = $auction_id";
$res3 = $mysqli->query($sql3);
$row3 = $res3->fetch_assoc();
$num_bids = $row3['bid_count'];

// ========================
// 判断是否是拍卖卖家
// ========================
$is_seller = isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $seller_id);

// ========================
// 删除 auction 请求处理（POST）
// ========================
if ($is_seller && isset($_POST['delete_auction'])) {
    if ($num_bids == 0) {
        $mysqli->query("DELETE FROM Auction WHERE auction_id = $auction_id");
        echo "<div class='container'><div class='alert alert-success'>Auction deleted successfully!</div></div>";
        include_once('footer.php');
        exit();
    } else {
        echo "<div class='container'><div class='alert alert-danger'>Cannot delete auction because it already has bids!</div></div>";
    }
}
// ========================
// 编辑 auction 处理
// ========================
if ($is_seller && isset($_POST['edit_auction']) && $num_bids == 0) {

    $new_description = $mysqli->real_escape_string($_POST['edit_description']);
    $new_start_price = floatval($_POST['edit_starting_price']);
    $new_reserve_price = ($_POST['edit_reserve_price'] !== '') ? floatval($_POST['edit_reserve_price']) : null;
    $new_end_time = $mysqli->real_escape_string($_POST['edit_end_time']);


  // 图片处理（支持保留/删除已有图片并添加新上传）
  $new_images_sql = "";

  // Start from existing images stored in DB
  $existing_images = [];
  if (!empty($img_url)) {
    $parts = array_map('trim', explode(',', $img_url));
    foreach ($parts as $p) {
      if ($p !== '') $existing_images[] = $p;
    }
  }

  // Handle deletions requested by the seller
  $delete_list = $_POST['delete_images'] ?? [];
  if (!empty($delete_list) && is_array($delete_list)) {
    foreach ($delete_list as $d) {
      $d = trim($d);
      if ($d === '') continue;
      $key = array_search($d, $existing_images);
      if ($key !== false) {
        // remove file from disk if exists
        $file_path = __DIR__ . '/' . ltrim($d, '/');
        if (file_exists($file_path)) {
          @unlink($file_path);
        }
        unset($existing_images[$key]);
      }
    }
    // reindex
    $existing_images = array_values($existing_images);
  }

  // Process newly uploaded images and append to remaining existing images (max 5 total)
  $maxImages = 5;
  $currentCount = count($existing_images);
  $remaining = $maxImages - $currentCount;
  if ($remaining <= 0) {
    // no room for new images
    // ignore any uploaded files and optionally add an error message
    if (!empty($_FILES['edit_images']['name'][0])) {
      // preserve previous errors array if exists
      $_SESSION['create_errors'] = array_merge($_SESSION['create_errors'] ?? [], ["You already have $currentCount images. Remove images before uploading more (max $maxImages)."]);
    }
  } else {
    if (!empty($_FILES['edit_images']['name'][0])) {
      $saved_images = [];
      $upload_dir = __DIR__ . '/img/auctions/';
      $web_dir = 'img/auctions/';
      if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);

      $files = $_FILES['edit_images'];
      // only attempt up to $remaining files
      $attempt = min(count($files['name']), $remaining);
      $added = 0;
      for ($i = 0; $i < count($files['name']) && $added < $remaining; $i++) {
        if (empty($files['name'][$i])) continue;
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
          $tmp = $files['tmp_name'][$i];
          $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
          $new_name = bin2hex(random_bytes(8))."_".time().".".$ext;
          if (move_uploaded_file($tmp, $upload_dir.$new_name)) {
            $saved_images[] = $web_dir.$new_name;
            $added++;
          }
        }
      }
      // merge saved images into existing set
      if (!empty($saved_images)) {
        $existing_images = array_merge($existing_images, $saved_images);
      }
      // if there were more uploaded files than remaining, inform seller
      $totalUploaded = count(array_filter($files['name']));
      if ($totalUploaded > $remaining) {
        $_SESSION['create_errors'] = array_merge($_SESSION['create_errors'] ?? [], ["Only $remaining additional images were accepted (max $maxImages total)."]);
      }
    }
  }

  // Build SQL fragment for images (NULL if no images remain)
  $new_image_url = !empty($existing_images) ? implode(',', $existing_images) : null;
  $new_images_sql = ", img_url=" . ($new_image_url === null ? "NULL" : "'" . $mysqli->real_escape_string($new_image_url) . "'");

    $sql_update = "
      UPDATE Auction 
      SET description='$new_description',
          starting_price=$new_start_price,
          reserve_price=" . ($new_reserve_price === null ? "NULL" : $new_reserve_price) . ",
          end_time='$new_end_time'
          $new_images_sql
      WHERE auction_id=$auction_id
    ";

    if ($mysqli->query($sql_update)) {
        echo "<div class='container mt-3'><div class='alert alert-success'>Auction updated successfully!</div></div>";
        echo "<script>setTimeout(() => { window.location.href='manage_listing.php?auction_id=$auction_id'; }, 1200);</script>";
        exit();
    } else {
        echo "<div class='container mt-3'><div class='alert alert-danger'>Update failed: ".$mysqli->error."</div></div>";
    }
}

?>


<div class="container">

  <div class="row">
    <div class="col-sm-8">
        <h2 class="my-3"><?php echo htmlspecialchars($title); ?></h2>

        <!-- 卖家显示 -->
        <p><small class="text-muted">Sold by <?php echo htmlspecialchars($seller_name); ?></small></p>

        <!-- 图片显示 -->
        <?php
          if (!empty($img_url)) {
              $parts = array_filter(array_map('trim', explode(',', $img_url)));
              if (count($parts) > 1) {
                  $cid = 'mainCarousel_' . htmlspecialchars($auction_id);
                  echo '<div id="' . $cid . '" class="carousel slide" data-ride="carousel">';
                  echo '<div class="carousel-inner">';
                  $first = true;
                  foreach ($parts as $p) {
                      $src = htmlspecialchars(normalize_image_src($p));
                      echo '<div class="carousel-item' . ($first ? ' active' : '') . '">';
                      echo '<img src="' . $src . '" style="width:100%; max-height:420px; object-fit:cover;">';
                      echo '</div>';
                      $first = false;
                  }
                  echo '</div>';
                  echo '<a class="carousel-control-prev" href="#' . $cid . '" role="button" data-slide="prev">';
                  echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
                  echo '<span class="sr-only">Previous</span>';
                  echo '</a>';
                  echo '<a class="carousel-control-next" href="#' . $cid . '" role="button" data-slide="next">';
                  echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
                  echo '<span class="sr-only">Next</span>';
                  echo '</a>';
                  echo '</div>';

                  // thumbnails below
                  echo '<div class="d-flex mt-2">';
                  foreach ($parts as $idx => $p) {
                      $p = trim($p);
                      $img = htmlspecialchars(normalize_image_src($p));
                      echo '<a href="#' . $cid . '" data-slide-to="' . intval($idx) . '"><img src="' . $img . '" style="width:80px; height:80px; object-fit:cover; margin-right:4px; border:1px solid #ddd;"></a>';
                  }
                  echo '</div>';
              } else {
                  $first = normalize_image_src($parts[0]);
                  echo '<div><img src="' . htmlspecialchars($first) . '" style="width:100%; max-height:420px; object-fit:cover;"></div>';
              }
            }
        ?>
    </div>

    <div class="col-sm-4">

        <p>Auction ends at: <?= $end_time->format('d M Y H:i') ?></p>

        <p class="lead">Highest bid:  
          <strong>£<?= number_format($current_price, 2) ?></strong>
        </p>

        <?php if ($is_seller): ?>
        <p>Reserve price:
           <strong>£<?= number_format($reserve_price, 2) ?></strong>  </p>
        <?php endif; ?>

        <p>Number of bids:
          <strong><?= $num_bids ?></strong>
        </p>

        <?php if ($is_seller): ?>
            
            <hr>

            <?php if ($num_bids == 0): ?>

                <!-- 编辑按钮 -->
                <button class="btn btn-secondary mt-2" onclick="document.getElementById('edit-auction-form').style.display='block'">Edit Auction</button>
                
                <!-- 删除表单 -->
                <form method="POST">
                    <button type="submit" name="delete_auction" class="btn btn-danger"
                      onclick="return confirm('Are you sure you want to delete this auction?')">
                      Delete Auction
                    </button>
                </form>

            <?php else: ?>

                <p class="text-muted">You cannot delete this auction because it already has bids.</p>

            <?php endif; ?>

        <?php endif; ?>

    </div>
  </div>

 <!-- =============================
  编辑 Auction 表单
  ============================= -->
  <?php if ($is_seller && $num_bids == 0): ?>

  <div id="edit-auction-form" style="display:none; margin-top:30px;" class="border p-3 bg-light">
    <h4>Edit Auction</h4>

    <form method="POST" enctype="multipart/form-data">

      <div class="form-group mt-2">
        <label>Description</label>
        <textarea name="edit_description" class="form-control" rows="4"><?= htmlspecialchars($description) ?></textarea>
      </div>

      <div class="form-group mt-2">
        <label>Starting price (£)</label>
        <input type="number" name="edit_starting_price" step="0.01" class="form-control" 
          value="<?= htmlspecialchars($starting_price) ?>" required>
      </div>

      <div class="form-group mt-2">
        <label>Reserve price (£)</label>
        <input type="number" name="edit_reserve_price" step="0.01" class="form-control"
          value="<?= htmlspecialchars($reserve_price) ?>">
      </div>

      <div class="form-group mt-2">
        <label>End time</label>
        <input type="datetime-local" name="edit_end_time" 
          class="form-control"
          value="<?= $end_time->format('Y-m-d\TH:i') ?>" required>
      </div>

      <div class="form-group mt-2">
        <label>Replace images (optional)</label>
        <?php
          $parts = !empty($img_url) ? array_filter(array_map('trim', explode(',', $img_url))) : [];
          $currentCount = count($parts);
          $maxImages = 5;
          $remaining = max(0, $maxImages - $currentCount);
        ?>
        <?php if (!empty($parts)): ?>
          <div class="mb-2">
            <label>Current images (check to delete)</label>
            <div class="d-flex flex-wrap">
              <?php
                foreach ($parts as $p) {
                  if (empty($p)) continue;
                  $safe = htmlspecialchars($p);
                  echo '<div class="text-center mr-2" style="margin-right:10px">';
                  echo '<img src="' . $safe . '" style="width:100px; height:100px; object-fit:cover; display:block; border:1px solid #ddd;">';
                  echo '<div class="form-check"><input class="form-check-input" type="checkbox" name="delete_images[]" value="' . $safe . '" id="del_' . md5($p) . '">';
                  echo '<label class="form-check-label" for="del_' . md5($p) . '">Delete</label></div>';
                  echo '</div>';
                }
              ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($remaining <= 0): ?>
          <div class="alert alert-info">You have reached the maximum of <?= $maxImages ?> images. Delete existing images to upload more.</div>
        <?php else: ?>
          <small class="form-text text-muted">You can upload up to <?= $remaining ?> more image(s).</small>
          <input type="file" id="editImagesInput" name="edit_images[]" class="form-control" multiple data-remaining="<?= $remaining ?>">
          <div id="editImagesPreview" class="mt-2 d-flex flex-wrap"></div>
        <?php endif; ?>
      </div>

      <button type="submit" name="edit_auction" class="btn btn-primary mt-3">Save Changes</button>
    </form>
  </div>

  <?php endif; ?>

</div>

<script>
// Improved cumulative file selection for edit images (#editImagesInput)
document.addEventListener('DOMContentLoaded', function(){
  var input = document.getElementById('editImagesInput');
  var preview = document.getElementById('editImagesPreview');
  var form = document.querySelector('#edit-auction-form form');
  if (!input || !preview || !form) return;

  var existingCount = preview.querySelectorAll('img').length || 0;
  var MAX_TOTAL = 5;
  var remaining = Math.max(0, MAX_TOTAL - existingCount);

  if (!window._accFilesEdit) window._accFilesEdit = new DataTransfer();

  // ensure container for new previews exists
  var newArea = document.getElementById('editNewPreview');
  if (!newArea){
    newArea = document.createElement('div');
    newArea.id = 'editNewPreview';
    preview.parentNode.insertBefore(newArea, preview.nextSibling);
  }

  function renderEditPreview(){
    newArea.innerHTML = '';
    Array.from(window._accFilesEdit.files).forEach(function(file){
      var div = document.createElement('div');
      div.className = 'm-1 position-relative';
      div.style.width = '100px';
      var img = document.createElement('img');
      img.style.width = '100%';
      img.style.height = '80px';
      img.style.objectFit = 'cover';
      var reader = new FileReader();
      reader.onload = function(e){ img.src = e.target.result; };
      reader.readAsDataURL(file);
      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn btn-sm btn-danger position-absolute';
      removeBtn.style.top = '2px';
      removeBtn.style.right = '2px';
      removeBtn.textContent = '×';
      removeBtn.addEventListener('click', function(){
        var dt = new DataTransfer();
        Array.from(window._accFilesEdit.files).forEach(function(f){
          if (!(f.name === file.name && f.size === file.size && f.type === file.type)) dt.items.add(f);
        });
        window._accFilesEdit = dt;
        renderEditPreview();
      });
      div.appendChild(img);
      div.appendChild(removeBtn);
      newArea.appendChild(div);
    });

    var rem = MAX_TOTAL - existingCount - window._accFilesEdit.files.length;
    if (rem <= 0){
      input.disabled = true;
      input.title = '已达到 ' + MAX_TOTAL + ' 张上限，删除后可上传';
    } else {
      input.disabled = false;
      input.title = '你还可以上传 ' + rem + ' 张';
    }
  }

  input.addEventListener('change', function(e){
    var files = Array.from(e.target.files || []);
    if (files.length === 0) return;
    var canAdd = Math.max(0, MAX_TOTAL - existingCount - window._accFilesEdit.files.length);
    if (canAdd <= 0){
      alert('已达到 ' + MAX_TOTAL + ' 张图片上限。');
      e.target.value = '';
      return;
    }
    var added = 0;
    files.forEach(function(f){
      if (added >= canAdd) return;
      if (f.size > 2*1024*1024) { console.warn('Skip file (too large):', f.name); return; }
      window._accFilesEdit.items.add(f);
      added++;
    });
    e.target.value = '';
    renderEditPreview();
    if (added < files.length) alert('只接受前 ' + added + ' 个文件（总数上限 ' + MAX_TOTAL + '）。');
  });

  form.addEventListener('submit', function(e){
    try{
      input.files = window._accFilesEdit.files;
      if (input.files && input.files.length === window._accFilesEdit.files.length) {
        return; // allow normal submit
      }
    } catch(err){
      console.warn('assigning accumulated edit files failed', err);
    }

    // AJAX fallback: build FormData manually including delete_images[] and other fields
    e.preventDefault();
    var fd = new FormData();
    Array.from(form.elements).forEach(function(el){
      if (!el.name) return;
      if (el.type === 'file') return; // we'll append files separately
      if (el.type === 'checkbox') { if (el.checked) fd.append(el.name, el.value); return; }
      if (el.tagName.toLowerCase() === 'select' && el.multiple) { Array.from(el.options).forEach(function(opt){ if (opt.selected) fd.append(el.name, opt.value); }); return; }
      fd.append(el.name, el.value);
    });
    Array.from(window._accFilesEdit.files).forEach(function(f){ fd.append('edit_images[]', f); });

    var submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) { submitBtn.disabled = true; submitBtn.dataset.origText = submitBtn.textContent; submitBtn.textContent = 'Uploading...'; }

    fetch(window.location.href, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(resp){ return resp.text(); })
      .then(function(text){ document.open(); document.write(text); document.close(); })
      .catch(function(err){ console.error('AJAX edit upload failed', err); alert('Upload failed: '+err); if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = submitBtn.dataset.origText || 'Save Changes'; } });
  });

  renderEditPreview();
});
</script>

<?php include_once('footer.php'); ?>