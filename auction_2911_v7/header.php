<?php
// Header file for the auction site.  This script starts the session and
// renders the top‑of‑page HTML, including the navigation bar and a login modal.
//
// TODO: remove the example session defaults once authentication is
// implemented.  For now they are used to demonstrate how the menu adapts
// depending on whether the user is logged in and whether they are a buyer
// or seller.

session_start();

// Example defaults for demonstration purposes.  In a real application
// these would be set after successful login.
if (!isset($_SESSION['logged_in'])) {
  $_SESSION['logged_in'] = false;
  $_SESSION['account_type'] = 'buyer';
}

?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap and FontAwesome CSS from CDNs -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <!-- Custom CSS file -->
  <link rel="stylesheet" href="css/custom.css">

  <title>Auction Site</title>
</head>

<body>

<!-- Top navigation bar: shows site name and login/logout link -->
<nav class="navbar navbar-expand-lg navbar-light bg-light mx-2">
  <a class="navbar-brand" href="browse.php">Auction Site</a>
  <ul class="navbar-nav ml-auto">
    <li class="nav-item">
<?php
// Displays either login or logout on the right, depending on the user's
// current status.  When the user is logged out, a button is shown to
// trigger the login modal.  When logged in, a logout link is displayed.
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
  echo '<a class="nav-link" href="logout.php">Logout</a>';
} else {
  echo '<button type="button" class="btn nav-link" data-toggle="modal" data-target="#loginModal">Login</button>';
}
?>
    </li>
  </ul>
</nav>

<!-- Secondary navigation bar: shows links relevant to buyer or seller roles -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <ul class="navbar-nav align-middle">
    <li class="nav-item mx-1">
      <a class="nav-link" href="browse.php">Browse</a>
    </li>
<?php
// Show different navigation options depending on the account type.
if (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'buyer') {
  echo '<li class="nav-item mx-1"><a class="nav-link" href="mybids.php">My Bids</a></li>';
  echo '<li class="nav-item mx-1"><a class="nav-link" href="recommendations.php">Recommended</a></li>';
}
if (isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'seller') {
  echo '<li class="nav-item mx-1"><a class="nav-link" href="mylistings.php">My Listings</a></li>';
  echo '<li class="nav-item ml-3"><a class="nav-link btn border-light" href="create_auction.php">+ Create auction</a></li>';
}
?>
  </ul>
</nav>

<!-- Login modal -->
<div class="modal fade" id="loginModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Login</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <!-- Modal body -->
      <div class="modal-body">
        <form method="POST" action="login_result.php">
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
          </div>
          <button type="submit" class="btn btn-primary form-control">Sign in</button>
        </form>
        <div class="text-center mt-2">or <a href="register.php">create an account</a></div>
      </div>
    </div>
  </div>
</div> <!-- End modal -->