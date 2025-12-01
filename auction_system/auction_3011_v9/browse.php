<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<?php
// Display success or error messages from session
session_start();
if (isset($_SESSION['register_success'])) {
    echo '<div id="registerSuccessMsg" class="alert alert-success alert-dismissible fade show text-center" role="alert" 
         style="margin-top: 20px; font-size: 18px;">
        ' . $_SESSION['register_success'] . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
      </div>';
    unset($_SESSION['register_success']);
}
if (isset($_SESSION['login_errors'])) {
    foreach ($_SESSION['login_errors'] as $error) {
        echo '<div id="errorMsg" class="alert alert-danger alert-dismissible fade show text-center"
              role="alert" style="margin-top: 20px; font-size: 18px;">
                ' . $error . '
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
            </div>';
    }
    unset($_SESSION['login_errors']);
}
?>

<?php
// Retrieve query parameters for search/filter/sort
$keyword   = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$category  = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$ordering  = isset($_GET['order_by']) ? $_GET['order_by'] : 'pricelow';
$curr_page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? floatval($_GET['min_price']) : null;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? floatval($_GET['max_price']) : null;
?>

<!-- Hero carousel with overlayed search and heading -->
<div class="container">
  <div class="hero-carousel mt-3 position-relative">
    <div id="heroCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
      <div class="carousel-inner">
        <div class="carousel-item active">
          <a href="recommendations.php"><img src="img/carousel1.png" class="d-block w-100" alt="Featured item 1"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel2.png" class="d-block w-100" alt="Featured item 2"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel3.png" class="d-block w-100" alt="Featured item 3"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel4.png" class="d-block w-100" alt="Featured item 4"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel5.png" class="d-block w-100" alt="Featured item 5"></a>
        </div>
      </div>
    </div>
    <!-- Overlayed heading and search bar -->
    <div class="hero-overlay d-flex flex-column justify-content-center align-items-center text-center">
      <h1 class="display-4 text-white font-weight-bold">Discover Unique Items</h1>
      <p class="lead text-white mb-4">Explore, bid and sell extraordinary items from sellers worldwide.</p>
      <form method="get" action="browse.php" class="w-100 hero-search-form">
        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category); ?>">
        <input type="hidden" name="order_by" value="<?php echo htmlspecialchars($ordering); ?>">
        <div class="input-group input-group-lg justify-content-center" style="max-width: 600px; margin: 0 auto;">
          <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Search for anything" value="<?php echo htmlspecialchars($keyword); ?>">
          <div class="input-group-append">
            <button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
          </div>
        </div>
      </form>
      <ol class="hero-dots carousel-indicators">
        <li data-target="#heroCarousel" data-slide-to="0" class="active"></li>
        <li data-target="#heroCarousel" data-slide-to="1"></li>
        <li data-target="#heroCarousel" data-slide-to="2"></li>
        <li data-target="#heroCarousel" data-slide-to="3"></li>
        <li data-target="#heroCarousel" data-slide-to="4"></li>
      </ol>
    </div>
  </div>
</div>
<!-- End hero carousel -->

<!-- Filter toggle button aligned to the right of the listings container -->
<div class="container my-4 d-flex justify-content-end filter-toggle-wrapper">
  <button id="filterToggle" type="button" class="btn btn-outline-secondary" onclick="toggleFilter()"><i class="fa fa-filter"></i></button>
</div>

<!-- Offcanvas filter panel -->
<div id="filterPanel" class="filter-panel">
  <div class="filter-header d-flex justify-content-between align-items-center p-3 border-bottom">
    <h5 class="mb-0">Filter</h5>
    <button type="button" class="close" aria-label="Close" onclick="toggleFilter()"><span aria-hidden="true">&times;</span></button>
  </div>
  <form method="get" action="browse.php" class="p-3">
    <div class="form-group">
      <label class="font-weight-bold">Categories</label><br/>
      <?php
        $cats = [
          'all' => 'All',
          'electronics' => 'Electronics',
          'fashion' => 'Fashion',
          'home' => 'Home & Garden'
        ];
        foreach ($cats as $cat_code => $cat_name) {
          $checked = ($category === $cat_code) ? 'checked' : '';
          echo '<div class="form-check mb-1"><input class="form-check-input" type="radio" name="cat" id="cat_' . $cat_code . '" value="' . $cat_code . '" ' . $checked . '><label class="form-check-label" for="cat_' . $cat_code . '">' . $cat_name . '</label></div>';
        }
      ?>
    </div>
    <div class="form-group">
      <label class="font-weight-bold">Price range (£)</label>
      <div class="d-flex align-items-center">
        <input type="number" name="min_price" step="0.01" class="form-control mr-1" placeholder="Min" style="max-width: 120px;" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
        <span>–</span>
        <input type="number" name="max_price" step="0.01" class="form-control ml-1" placeholder="Max" style="max-width: 120px;" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="font-weight-bold">Sort by</label>
      <select class="form-control" name="order_by">
        <option value="pricelow" <?php echo ($ordering === 'pricelow') ? 'selected' : ''; ?>>Price (low to high)</option>
        <option value="pricehigh" <?php echo ($ordering === 'pricehigh') ? 'selected' : ''; ?>>Price (high to low)</option>
        <option value="date" <?php echo ($ordering === 'date') ? 'selected' : ''; ?>>Soonest expiry</option>
      </select>
    </div>
    <!-- Preserve keyword for search -->
    <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
    <button type="submit" class="btn btn-primary btn-block">Apply</button>
  </form>
</div>

<?php
// ========================
// Query real auctions from database
// ========================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'auction_system';
$mysqli  = @new mysqli($db_host, $db_user, $db_pass, $db_name);

// Build WHERE filters
$where = 'WHERE 1=1';
// Keyword search
if ($keyword !== '') {
    $safe_keyword = $mysqli->real_escape_string($keyword);
    $where .= " AND a.title LIKE '%$safe_keyword%'";
}
// Category filter
if ($category !== 'all') {
    $cats_map = [
        'electronics' => 1,
        'fashion'     => 2,
        'books'       => 3,
        'sports'      => 4,
        'home'        => 5
    ];
    if (isset($cats_map[$category])) {
        $where .= ' AND a.category_id = ' . intval($cats_map[$category]);
    }
}
// Price filter
if ($min_price !== null) $where .= ' AND a.starting_price >= ' . floatval($min_price);
if ($max_price !== null) $where .= ' AND a.starting_price <= ' . floatval($max_price);
// Sorting
switch ($ordering) {
    case 'pricehigh': $order_sql = 'ORDER BY current_price DESC'; break;
    case 'date':      $order_sql = 'ORDER BY end_time ASC'; break;
    default:          $order_sql = 'ORDER BY current_price ASC';
}
// Pagination
$results_per_page = 12;
$offset = ($curr_page - 1) * $results_per_page;
// Count total available auction results
$count_sql   = "SELECT COUNT(*) FROM Auction a $where";
$count_res   = $mysqli->query($count_sql);
$total_results = $count_res ? $count_res->fetch_row()[0] : 0;
$max_page     = ceil($total_results / $results_per_page);
// Main query
$sql = "
SELECT a.*,\n       (SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bids,\n       (SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) AS current_price\nFROM Auction a\n$where\n$order_sql\nLIMIT $offset, $results_per_page\n";
$res = $mysqli->query($sql);
$items = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}
?>

<!-- Results container -->
<div class="container mt-5">
  <?php if (empty($items)): ?>
    <p>No results found. Try adjusting your search criteria.</p>
  <?php else: ?>
    <div class="row">
      <?php
        foreach ($items as $r) {
            $end_time = new DateTime($r['end_time']);
            // Pass extra parameters if your print_listing_card signature supports them
            print_listing_card(
                $r['auction_id'],
                $r['title'],
                $r['description'],
                $r['current_price'],
                $r['bids'],
                $end_time,
                $r['img_url'],
                null,
                $r['category_id']
            );
        }
      ?>
    </div>
    <!-- Pagination -->
    <nav aria-label="Search results pages" class="mt-5">
      <ul class="pagination justify-content-center">
        <?php
          // Copy any currently-set GET variables to the URL (except page)
          $querystring = '';
          foreach ($_GET as $key => $value) {
            if ($key !== 'page') {
              $querystring .= urlencode($key) . '=' . urlencode($value) . '&';
            }
          }
          // Determine page ranges to show in the pagination
          $high_page_boost = max(3 - $curr_page, 0);
          $low_page_boost  = max(2 - ($max_page - $curr_page), 0);
          $low_page        = max(1, $curr_page - 2 - $low_page_boost);
          $high_page       = min($max_page, $curr_page + 2 + $high_page_boost);
          // Previous page link
          if ($curr_page > 1) {
            echo '<li class="page-item"><a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page - 1) . '" aria-label="Previous"><span aria-hidden="true"><i class="fa fa-arrow-left"></i></span><span class="sr-only">Previous</span></a></li>';
          }
          // Page number links
          for ($i = $low_page; $i <= $high_page; $i++) {
            if ($curr_page === $i) {
              echo '<li class="page-item active"><span class="page-link">' . $i . ' <span class="sr-only">(current)</span></span></li>';
            } else {
              echo '<li class="page-item"><a class="page-link" href="browse.php?' . $querystring . 'page=' . $i . '">' . $i . '</a></li>';
            }
          }
          // Next page link
          if ($curr_page < $max_page) {
            echo '<li class="page-item"><a class="page-link" href="browse.php?' . $querystring . 'page=' . ($curr_page + 1) . '" aria-label="Next"><span aria-hidden="true"><i class="fa fa-arrow-right"></i></span><span class="sr-only">Next</span></a></li>';
          }
        ?>
      </ul>
    </nav>
  <?php endif; ?>
</div>

<!-- Script to toggle filter panel -->
<script>
function toggleFilter() {
  var panel = document.getElementById('filterPanel');
  if (panel) {
    panel.classList.toggle('open');
  }
}
</script>

<!-- Script to auto-dismiss success/error messages -->
<script>
document.addEventListener("click", function(e) {
    var msg = document.getElementById("registerSuccessMsg");
    if (msg) {
        msg.classList.remove("show");
        msg.classList.add("fade");
        setTimeout(function(){ msg.remove(); }, 300);
    }
});
document.addEventListener('click', function(e) {
    const msg = document.getElementById('errorMsg');
    if (msg && !msg.contains(e.target)) {
        msg.style.display = 'none';
    }
});
</script>

<?php include_once('footer.php'); ?>