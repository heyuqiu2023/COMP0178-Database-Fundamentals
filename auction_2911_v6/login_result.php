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
  // Simple logic: if the email contains "seller" then assign seller role,
  // otherwise assign buyer role.  This is purely for demonstration.
  $account_type = (stripos($email, 'seller') !== false) ? 'seller' : 'buyer';

  $_SESSION['logged_in'] = true;
  $_SESSION['username'] = $email;
  $_SESSION['account_type'] = $account_type;

  echo '<div class="alert alert-success">You are now logged in as ' . htmlspecialchars($email) . ' with role ' . htmlspecialchars($account_type) . '. You will be redirected shortly.</div>';
  // Redirect to index after 5 seconds
  header('refresh:5;url=index.php');
} else {
  echo '<div class="alert alert-warning">No login details received.  Please log in from the home page.</div>';
}
?>

</div>

<?php include_once('footer.php'); ?>