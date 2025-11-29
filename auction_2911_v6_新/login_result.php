<?php include_once('header.php'); ?>

<div class="container">
  <h2 class="my-3">Login result</h2>

<?php
// Handle the login form submission.  In a real application this page
// would validate the user's credentials against the users table.  For now,
// we accept any email/password combination and set session variables for
// demonstration purposes.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = isset($_POST['email']) ? $_POST['email'] : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';

  require_once('db.php');
  $db = get_db();
  if (!$db) {
    echo '<div class="alert alert-danger">Database unavailable. Please try again later.</div>';
    exit();
  }

  $stmt = $db->prepare('SELECT user_id, password_hash, username, role FROM `User` WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) {
    $row = $res->fetch_assoc();
    if (password_verify($password, $row['password_hash'])) {
      $_SESSION['logged_in'] = true;
      $_SESSION['user_id'] = $row['user_id'];
      $_SESSION['username'] = $row['username'];
      $_SESSION['account_type'] = $row['role'];
      echo '<div class="alert alert-success">You are now logged in as ' . htmlspecialchars($row['username']) . '. Redirecting...</div>';
      header('refresh:1;url=browse.php');
    } else {
      echo '<div class="alert alert-danger">Invalid credentials.</div>';
      echo '<p><a href="browse.php">Back</a></p>';
    }
  } else {
    echo '<div class="alert alert-danger">No account found with that email. <a href="register.php">Register</a></div>';
  }
  $stmt->close();
  $db->close();
} else {
  echo '<div class="alert alert-warning">No login details received.  Please log in from the home page.</div>';
}
?>

</div>

<?php include_once('footer.php'); ?>