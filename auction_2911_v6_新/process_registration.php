<?php
// Process user registration form submission.
// This script validates the input, creates a new user in the database (to be
// implemented) and sets session variables so that the user is logged in.  For
// now, it simulates the registration process without persistent storage.

session_start();
require_once('db.php');

// Only proceed if the registration form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Registration Error</title></head><body>';
  echo '<p>No registration data received.</p>';
  echo '<p><a href="register.php">Return to registration</a></p>';
  echo '</body></html>';
  exit();
}

// Retrieve form values
$email    = isset($_POST['email'])    ? trim($_POST['email'])    : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password']       : '';
$confirm  = isset($_POST['confirm'])  ? $_POST['confirm']        : '';
$role     = isset($_POST['role'])     ? $_POST['role']           : '';

// Simple validation: ensure that all fields are provided and passwords match
$errors = [];
if ($email === '' || $username === '' || $password === '' || $confirm === '' || $role === '') {
  $errors[] = 'All fields are required.';
}
if ($password !== $confirm) {
  $errors[] = 'Passwords do not match.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Invalid email address.';
}
if ($role !== 'buyer' && $role !== 'seller') {
  $errors[] = 'Invalid role selected.';
}

if (!empty($errors)) {
  // If there are validation errors, display them and stop processing
  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Registration Error</title>';
  echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">';
  echo '</head><body><div class="container"><h2 class="my-3">Registration Error</h2>';
  echo '<div class="alert alert-danger" role="alert">';
  foreach ($errors as $error) {
    echo htmlspecialchars($error) . '<br>';
  }
  echo '</div>';
  echo '<p><a href="register.php" class="btn btn-primary">Return to registration</a></p>';
  echo '</div></body></html>';
  exit();
}

// Insert the new user into the database
$db = get_db();
if (!$db) {
  echo '<div class="alert alert-danger">Database unavailable. Please try again later.</div>';
  exit();
}

// Check if email already exists
$stmt = $db->prepare('SELECT user_id FROM `User` WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  echo '<div class="alert alert-danger">Email already registered. Please log in.</div>';
  echo '<p><a href="login_result.php">Login</a></p>';
  $stmt->close(); $db->close();
  exit();
}
$stmt->close();

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt2 = $db->prepare('INSERT INTO `User` (email, password_hash, username, role) VALUES (?, ?, ?, ?)');
$stmt2->bind_param('ssss', $email, $password_hash, $username, $role);
if ($stmt2->execute()) {
  $new_user_id = $stmt2->insert_id;
  $_SESSION['logged_in'] = true;
  $_SESSION['user_id'] = $new_user_id;
  $_SESSION['username'] = $username;
  $_SESSION['account_type'] = $role;
  // Redirect to the browse page after successful registration
  header('Refresh: 3; URL=browse.php');
} else {
  echo '<div class="alert alert-danger">Failed to register: ' . htmlspecialchars($stmt2->error) . '</div>';
}
$stmt2->close();
$db->close();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <title>Registration Successful</title>
</head>
<body>
  <div class="container">
    <h2 class="my-3">Registration Successful</h2>
    <p>Thank you for registering, <?php echo htmlspecialchars($username); ?>.</p>
    <p>You are now logged in as a <?php echo htmlspecialchars($role); ?>.  You will be redirected to the browse page shortly.</p>
    <p>If you are not redirected automatically, <a href="browse.php">click here</a>.</p>
  </div>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>