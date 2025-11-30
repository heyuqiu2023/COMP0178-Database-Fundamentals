<?php
session_start();
require_once 'db_connection.php';
require_once 'utilities.php';

include_once('header.php'); 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div class="alert alert-warning">No auction details were received.  
    Please create your auction from the <a href="create_auction.php">Create Auction</a> page.</div>';
    exit();
}

// ======================
// 1. 获取表单数据
// ======================
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$start_price = floatval($_POST['start_price'] ?? 0.00);
$reserve_price = ($_POST['reserve_price'] !== '') ? floatval($_POST['reserve_price']) : null;
$end_time = trim($_POST['end_date'] ?? '');
$category_id = intval($_POST['category'] ?? 0);

// 获取 seller_id
$seller_id = $_SESSION['user_id'] ?? null;

// ======================
// 2. 验证
// ======================
$errors = [];

if (!$seller_id) $errors[] = "You must be logged in to create an auction.";
if (empty($title)) $errors[] = "Title is required.";
if ($category_id === 0) $errors[] = "Please select a category.";
if (empty($end_time) || strtotime($end_time) <= time()) $errors[] = "End date must be in the future.";
if ($reserve_price !== null && $reserve_price < $start_price) $errors[] = "Reserve price must be >= starting price.";

if (!empty($errors)) {
    $_SESSION['create_errors'] = $errors;
    $_SESSION['old_input'] = $_POST;
    header('Location: create_auction.php');
    exit();
}

// ======================
// 3. 处理图片上传
// ======================
$saved_images = [];
$upload_dir = __DIR__ . '/img/auctions/';
$web_dir = 'img/auctions/';

if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);

if (isset($_FILES['images'])) {
    $files = $_FILES['images'];
    $maxFiles = 5;
    $maxSize = 2 * 1024 * 1024;
    $allowedMime = ['image/jpeg','image/png','image/gif'];

    for ($i = 0; $i < min(count($files['name']), $maxFiles); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp = $files['tmp_name'][$i];
            $info = @getimagesize($tmp);
            if ($info && in_array($info['mime'], $allowedMime) && $files['size'][$i] <= $maxSize) {
                $ext = match ($info['mime']) {
                    'image/jpeg' => '.jpg',
                    'image/png' => '.png',
                    'image/gif' => '.gif',
                };
                $new_name = bin2hex(random_bytes(8))."_".time().$ext;
                if (move_uploaded_file($tmp, $upload_dir.$new_name)) {
                    $saved_images[] = $web_dir.$new_name;
                }
            }
        }
    }
}

$image_url_value = (!empty($saved_images) ? implode(',', $saved_images) : null);

// ======================
// 4. 动态决定 status
// ======================
$status = getAuctionStatus($end_time);

// ======================
// 5. 插入数据库
// ======================
try {
    $stmt = $pdo->prepare(
        "INSERT INTO Auction 
        (seller_id, category_id, title, description, starting_price, reserve_price, end_time, status, img_url) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $seller_id,
        $category_id,
        $title,
        $description ?: null,
        $start_price,
        $reserve_price,
        $end_time,
        $status,
        $image_url_value
    ]);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: ".$e->getMessage()."</div>";
    exit();
}

// ======================
// 6. 输出成功页面
// ======================

unset($_SESSION['old_input']);

echo '<div class="alert alert-success">Your auction "' . htmlspecialchars($title) . '" has been created and will end on ' . htmlspecialchars($end_time) . '.</div>';

if (!empty($saved_images)) {
    echo '<h5>Uploaded images</h5><div class="row">';
    foreach ($saved_images as $img) {
        echo '<div class="col-4 col-md-2 mb-2">
            <a href="'.htmlspecialchars($img).'" target="_blank">
            <img src="'.htmlspecialchars($img).'" style="max-width:100%; height:auto; border:1px solid #ddd; padding:2px;">
            </a>
            </div>';
    }
    echo '</div>';
}

echo '<p>You can now <a href="browse.php">return to browse listings</a> or <a href="create_auction.php">create another auction</a>.</p>';

include_once('footer.php');

?>
