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
$raw_end = trim($_POST['end_date'] ?? '');

// Normalize datetime-local input (e.g. "2025-11-30T12:30") to a MySQL DATETIME string
$end_time = '';
if ($raw_end !== '') {
    // Try strict parsing for inputs from <input type="datetime-local"> (no seconds)
    $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw_end);
    if (!$dt) {
        // Try with seconds if present
        $dt = DateTime::createFromFormat('Y-m-d\TH:i:s', $raw_end);
    }
    if ($dt) {
        // Store in standard MySQL DATETIME format
        $end_time = $dt->format('Y-m-d H:i:s');
    } else {
        // Fallback: replace 'T' with space and attempt to parse
        $tmp = str_replace('T', ' ', $raw_end);
        $ts = strtotime($tmp);
        if ($ts !== false) {
            $end_time = date('Y-m-d H:i:s', $ts);
        } else {
            // leave as empty string to trigger validation error
            $end_time = '';
        }
    }
}
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
// 3. 处理图片上传（更鲁棒的错误处理与回退）
// ======================
$saved_images = [];
$upload_dir = __DIR__ . '/img/auctions/';
$web_dir = 'img/auctions/';

if (!is_dir($upload_dir)) mkdir($upload_dir,0755,true);

$upload_attempted = false;
$upload_errors = [];

if (isset($_FILES['images'])) {
    $files = $_FILES['images'];
    $maxFiles = 5;
    $maxSize = 2 * 1024 * 1024;
    $allowedMime = ['image/jpeg','image/png','image/gif'];

    $total = min(count($files['name']), $maxFiles);
    for ($i = 0; $i < $total; $i++) {
        // Skip empty file inputs
        if (empty($files['name'][$i])) continue;
        $upload_attempted = true;

        $err = $files['error'][$i];
        if ($err !== UPLOAD_ERR_OK) {
            $upload_errors[] = "File #".($i+1)." upload error code: $err";
            continue;
        }

        $tmp = $files['tmp_name'][$i];
        $info = @getimagesize($tmp);
        if (!$info) {
            $upload_errors[] = "File #".($i+1)." is not a valid image.";
            continue;
        }
        if (!in_array($info['mime'], $allowedMime)) {
            $upload_errors[] = "File #".($i+1)." has disallowed mime type: " . ($info['mime'] ?? 'unknown');
            continue;
        }
        if ($files['size'][$i] > $maxSize) {
            $upload_errors[] = "File #".($i+1)." exceeds maximum size of 2MB.";
            continue;
        }

        // Determine extension from mime
        $ext = '.jpg';
        switch ($info['mime']) {
            case 'image/png': $ext = '.png'; break;
            case 'image/gif': $ext = '.gif'; break;
            case 'image/jpeg':
            default: $ext = '.jpg';
        }

        $new_name = bin2hex(random_bytes(8))."_".time().$ext;
        $dest = $upload_dir.$new_name;
        $web_dest = $web_dir.$new_name;

        // Try move_uploaded_file first, then copy as fallback
        if (@move_uploaded_file($tmp, $dest)) {
            $saved_images[] = $web_dest;
        } else {
            // Attempt copy fallback
            if (@copy($tmp, $dest)) {
                $saved_images[] = $web_dest;
            } else {
                // Try to relax permissions if directory exists but not writable
                if (is_dir($upload_dir) && !is_writable($upload_dir)) {
                    @chmod($upload_dir, 0775);
                    if (@move_uploaded_file($tmp, $dest) || @copy($tmp, $dest)) {
                        $saved_images[] = $web_dest;
                        continue;
                    }
                }
                $upload_errors[] = "Failed to save file #".($i+1)." to server.";
            }
        }
    }
}

$image_url_value = (!empty($saved_images) ? implode(',', $saved_images) : null);

// If files were submitted but none saved, add a user-visible warning (does not block creation)
if ($upload_attempted && empty($saved_images)) {
    if (!empty($upload_errors)) {
        // merge into session errors so user sees what went wrong
        $_SESSION['create_errors'] = array_merge($_SESSION['create_errors'] ?? [], $upload_errors);
    } else {
        $_SESSION['create_errors'] = array_merge($_SESSION['create_errors'] ?? [], ['Images were uploaded but could not be saved.']);
    }
}

// Debug/logging: if upload was attempted but nothing saved, log details to PHP error log for diagnosis
$upload_warning_message = '';
if ($upload_attempted && empty($saved_images)) {
    $log = "Image upload attempted but no files saved. Errors: " . implode(' | ', $upload_errors);
    $log .= "\n_POST: " . json_encode(array_map(function($v){ return is_string($v) ? $v : ''; }, $_POST));
    $log .= "\n_files: " . json_encode(array_map(function($f){ return [
        'name' => $f['name'] ?? null,
        'type' => $f['type'] ?? null,
        'tmp_name' => $f['tmp_name'] ?? null,
        'error' => $f['error'] ?? null,
        'size' => $f['size'] ?? null
    ]; }, $_FILES ?? []));
    $log .= "\nupload_dir_exists:" . (is_dir($upload_dir) ? '1' : '0') . " writable:" . (is_writable($upload_dir) ? '1' : '0');
    error_log($log);
    $upload_warning_message = !empty($upload_errors) ? implode('<br/>', array_map('htmlspecialchars', $upload_errors)) : 'Images were uploaded but could not be saved.';
}

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

// Show a friendly formatted end time
echo '<div class="alert alert-success">Your auction "' . htmlspecialchars($title) . '" has been created and will end on ' . htmlspecialchars(formatTime($end_time)) . '.</div>';

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

// If upload attempted but none saved, show a warning now so user knows immediately
if (!empty($upload_warning_message)) {
    echo '<div class="alert alert-warning">' . $upload_warning_message . '</div>';
}

echo '<p>You can now <a href="browse.php">return to browse listings</a> or <a href="create_auction.php">create another auction</a>.</p>';

include_once('footer.php');

?>
