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
  // Handle uploaded images (optional)
  $saved_images = array();
  $errors = array();

  // Directory to save auction images (relative to project root)
  $uploadDir = __DIR__ . '/img/auctions/';
  $webDir = 'img/auctions/';

  if (!is_dir($uploadDir)) {
    // try to create directory
    if (!mkdir($uploadDir, 0755, true)) {
      $errors[] = 'Unable to create images directory on server.';
    }
  }

  if (isset($_FILES['images'])) {
    // Limit to 5 files to avoid abuse
    $files = $_FILES['images'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    $maxFiles = 5;
    $allowedMime = array('image/jpeg', 'image/png', 'image/gif');
    $maxSize = 2 * 1024 * 1024; // 2MB per file

    for ($i = 0; $i < min($count, $maxFiles); $i++) {
      if ($files['error'][$i] !== UPLOAD_ERR_OK) {
        // skip empty inputs
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        $errors[] = 'Error uploading file ' . htmlspecialchars($files['name'][$i]);
        continue;
      }

      // Basic size check
      if ($files['size'][$i] > $maxSize) {
        $errors[] = 'File ' . htmlspecialchars($files['name'][$i]) . ' is too large. Max 2MB.';
        continue;
      }

      // Validate image type using getimagesize
      $tmpPath = $files['tmp_name'][$i];
      $imgInfo = @getimagesize($tmpPath);
      if ($imgInfo === false) {
        $errors[] = 'File ' . htmlspecialchars($files['name'][$i]) . ' is not a valid image.';
        continue;
      }

      $mime = $imgInfo['mime'];
      if (!in_array($mime, $allowedMime)) {
        $errors[] = 'File ' . htmlspecialchars($files['name'][$i]) . ' has unsupported image type.';
        continue;
      }

      // Generate a unique filename
      $ext = '';
      switch ($mime) {
        case 'image/jpeg': $ext = '.jpg'; break;
        case 'image/png': $ext = '.png'; break;
        case 'image/gif': $ext = '.gif'; break;
      }
      $basename = bin2hex(random_bytes(8));
      $filename = $basename . '_' . time() . $ext;
      $destination = $uploadDir . $filename;

      if (move_uploaded_file($tmpPath, $destination)) {
        $saved_images[] = $webDir . $filename;
      } else {
        $errors[] = 'Failed to move uploaded file: ' . htmlspecialchars($files['name'][$i]);
      }
    }

    if ($count > $maxFiles) {
      $errors[] = 'Only the first ' . $maxFiles . ' files were processed.';
    }
  }

  // Persist auction to database
  // Assumptions: local MySQL with database 'auction', user 'root' and empty password.
  // If your credentials differ, update these variables accordingly.
  $db_host = 'localhost';
  $db_user = 'root';
  $db_pass = '';
  $db_name = 'auction';

  // Seller id: prefer session user_id if present; fall back to 1 for demo purposes.
  $seller_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

  // Prepare image_url value (comma-separated) or NULL
  $image_url_value = !empty($saved_images) ? implode(',', $saved_images) : null;

  // Connect and insert record
  $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
  if ($mysqli->connect_errno) {
    $db_error = 'Database connection failed: ' . $mysqli->connect_error;
  } else {
    // Map form fields to DB columns
    $category_id = isset($_POST['category']) ? intval($_POST['category']) : 1;
    $description = isset($_POST['description']) ? $_POST['description'] : null;
    $starting_price = isset($_POST['start_price']) ? floatval($_POST['start_price']) : 0.00;
    $reserve_price = isset($_POST['reserve_price']) && $_POST['reserve_price'] !== '' ? floatval($_POST['reserve_price']) : 0.00;
    $start_time = date('Y-m-d H:i:s');
    // Convert HTML5 datetime-local to MySQL datetime
    $end_time = isset($_POST['end_date']) ? date('Y-m-d H:i:s', strtotime($_POST['end_date'])) : null;
    $status = 'upcoming';

    $stmt = $mysqli->prepare('INSERT INTO `Auction` (`seller_id`,`category_id`,`title`,`description`,`starting_price`,`reserve_price`,`start_time`,`end_time`,`status`,`image_url`) VALUES (?,?,?,?,?,?,?,?,?,?)');
    if ($stmt) {
      // bind parameters (s = string types converted appropriately)
      $stmt->bind_param('iissddisss', $seller_id, $category_id, $title, $description, $starting_price, $reserve_price, $start_time, $end_time, $status, $image_url_value);
      if ($stmt->execute()) {
        $new_auction_id = $stmt->insert_id;
      } else {
        $db_error = 'Failed to insert auction: ' . $stmt->error;
      }
      $stmt->close();
    } else {
      $db_error = 'Prepare failed: ' . $mysqli->error;
    }
    $mysqli->close();
  }

  echo '<div class="alert alert-success">Your auction "' . htmlspecialchars($title) . '" has been created and will end on ' . htmlspecialchars($end_date) . '.</div>';

  if (!empty($saved_images)) {
    echo '<h5>Uploaded images</h5>';
    echo '<div class="row">';
    foreach ($saved_images as $img) {
      // show thumbnails
      echo '<div class="col-4 col-md-2 mb-2"><a href="' . htmlspecialchars($img) . '" target="_blank"><img src="' . htmlspecialchars($img) . '" alt="uploaded image" style="max-width:100%; height:auto; border:1px solid #ddd; padding:2px; background:#fff"></a></div>';
    }
    echo '</div>';
  }

  if (!empty($errors)) {
    echo '<div class="alert alert-warning"><strong>Upload notes:</strong><ul>';
    foreach ($errors as $e) {
      echo '<li>' . htmlspecialchars($e) . '</li>';
    }
    echo '</ul></div>';
  }

  echo '<p>You can now <a href="browse.php">return to browse listings</a> or <a href="create_auction.php">create another auction</a>.</p>';
} else {
  echo '<div class="alert alert-warning">No auction details were received.  Please create your auction from the <a href="create_auction.php">Create Auction</a> page.</div>';
}
?>

</div>

<?php include_once('footer.php'); ?>