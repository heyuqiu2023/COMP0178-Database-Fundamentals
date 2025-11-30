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


    // 图片处理
    $new_images_sql = "";
    if (!empty($_FILES['edit_images']['name'][0])) {

        $saved_images = [];
        $upload_dir = __DIR__ . '/img/auctions/';
        $web_dir = 'img/auctions/';
        if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);

        $files = $_FILES['edit_images'];
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = $files['tmp_name'][$i];
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $new_name = bin2hex(random_bytes(8))."_".time().".".$ext;
                move_uploaded_file($tmp, $upload_dir.$new_name);
                $saved_images[] = $web_dir.$new_name;
            }
        }
        $new_image_url = implode(',', $saved_images);
        $new_images_sql = ", img_url='$new_image_url'";
    }

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
            $parts = explode(',', $img_url);
            $first = normalize_image_src(trim($parts[0]));
            echo '<div><img src="' . htmlspecialchars($first) . '" style="width:100%; max-height:420px; object-fit:cover;"></div>';

            if (count($parts) > 1) {
              echo '<div class="d-flex mt-2">';
              foreach ($parts as $p) {
                $p = trim($p);
                echo '<img src="' . htmlspecialchars(normalize_image_src($p)) . '" style="width:80px; height:80px; object-fit:cover; margin-right:4px; border:1px solid #ddd;">';
              }
              echo '</div>';
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
        <input type="file" name="edit_images[]" class="form-control" multiple>
      </div>

      <button type="submit" name="edit_auction" class="btn btn-primary mt-3">Save Changes</button>
    </form>
  </div>

  <?php endif; ?>

</div>

<?php include_once('footer.php'); ?>