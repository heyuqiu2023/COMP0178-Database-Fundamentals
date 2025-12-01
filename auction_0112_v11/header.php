<?php
session_start();

// 初始化登录状态（只设置 logged_in，不设置角色）
if (!isset($_SESSION['logged_in'])) {
  $_SESSION['logged_in'] = false;
}
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <link rel="stylesheet" href="css/custom.css">
  <!-- flatpickr CSS for consistent datetime-local UI across browsers -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <title>Auction Site</title>
</head>
<body>

<!-- 顶部导航：站点名 + 右侧欢迎/登录/登出 -->
<nav class="navbar navbar-expand-lg navbar-light bg-light mx-2">
  <a class="navbar-brand" href="index.php">Auction Site</a>

  <ul class="navbar-nav ml-auto">
    <?php if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
      <!-- 已登录：显示欢迎 + Logout -->
      <li class="nav-item mr-3">
        <span class="navbar-text">
          Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>
        </span>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="logout.php">Logout</a>
      </li>
    <?php else: ?>
      <!-- 未登录：显示 Login 按钮 -->
      <li class="nav-item">
        <button type="button"
                class="btn nav-link"
                data-toggle="modal"
                data-target="#loginModal">
          Login
        </button>
      </li>
    <?php endif; ?>
  </ul>
</nav>


<!-- 第二行导航 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <ul class="navbar-nav align-middle">
    <li class="nav-item mx-1">
      <a class="nav-link" href="browse.php">Browse</a>
    </li>

<?php
// 登录后才显示 account-specific 的链接

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['account_type'] ?? '') === 'buyer') {
  // 买家导航：My Bids + My Watchlist + Recommended
  echo '<li class="nav-item mx-1"><a class="nav-link" href="mybids.php">My Bids</a></li>';
  echo '<li class="nav-item mx-1"><a class="nav-link" href="mywatchlist.php">My Watchlist</a></li>';
  echo '<li class="nav-item mx-1"><a class="nav-link" href="recommendations.php">Recommended</a></li>';
}

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['account_type'] ?? '') === 'seller') {
  // 卖家导航：My Listings + 创建拍卖
  echo '<li class="nav-item mx-1"><a class="nav-link" href="mylistings.php">My Listings</a></li>';
  echo '<li class="nav-item mx-1"><a class="nav-link btn border-light" href="create_auction.php">+ Create auction</a></li>';
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
