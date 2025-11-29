<?php include_once('header.php'); ?>
<?php require('utilities.php'); ?>

<?php
// Retrieve query parameters early so they are available for the hero section and filters
$keyword   = isset($_GET['keyword']) ? $_GET['keyword'] : '';
$category  = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$ordering  = isset($_GET['order_by']) ? $_GET['order_by'] : 'pricelow';
$curr_page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
$min_price = (isset($_GET['min_price']) && $_GET['min_price'] !== '') ? floatval($_GET['min_price']) : null;
$max_price = (isset($_GET['max_price']) && $_GET['max_price'] !== '') ? floatval($_GET['max_price']) : null;
?>

<div class="container">
  <!-- Hero carousel with overlayed search and heading -->
  <div class="hero-carousel mt-3 position-relative">
    <div id="heroCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">

      <div class="carousel-inner">
        <div class="carousel-item active">
          <a href="recommendations.php"><img src="img/carousel1.png" class="d-block w-100" alt="Featured item"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel2.png" class="d-block w-100" alt="Featured item"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel3.png" class="d-block w-100" alt="Featured item"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel4.png" class="d-block w-100" alt="Featured item"></a>
        </div>
        <div class="carousel-item">
          <a href="recommendations.php"><img src="img/carousel5.png" class="d-block w-100" alt="Featured item"></a>
        </div>
      </div>

    </div>

    <div class="hero-overlay d-flex flex-column justify-content-center align-items-center text-center">
      <h1 class="display-4 text-white font-weight-bold">Discover Unique Items</h1>
      <p class="lead text-white mb-4">Explore, bid and sell extraordinary items from sellers worldwide.</p>
      <form method="get" action="browse.php" class="w-100 hero-search-form">
        <!-- Preserve existing category and ordering when performing a new search -->
        <input type="hidden" name="cat" value="<?php echo htmlspecialchars($category); ?>">
        <input type="hidden" name="order_by" value="<?php echo htmlspecialchars($ordering); ?>">
        <div class="input-group input-group-lg justify-content-center" style="max-width: 600px; margin: 0 auto;">
          <input type="text" class="form-control" id="keyword" name="keyword" placeholder="Search for anything" value="<?php echo(isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''); ?>">
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
  <!-- End hero carousel -->

<!-- Filter toggle button aligned to the right of the listings container -->
<div class="container my-4 d-flex justify-content-end filter-toggle-wrapper">
  <!-- Filter icon button toggles the off-canvas filter panel -->
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
  // Use search parameters retrieved at the top of the file

  /* TODO: Use the above values to construct a query.  Use this query to
     retrieve data from the database.  If there is no form data entered,
     decide on appropriate default values. */

  // Fetch real auction items from DB
  $results_per_page = 12;
  $offset = ($curr_page - 1) * $results_per_page;
  $dummy_items = [];
  $num_results = 0;
  $max_page = 1;

  $db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'auction';
  $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
  if (!$mysqli->connect_errno) {
    $where = "a.status = 'active'";
    if (!empty($keyword)) {
      $kw = $mysqli->real_escape_string($keyword);
      $where .= " AND (a.title LIKE '%".$kw."%' OR a.description LIKE '%".$kw."%')";
    }
    if ($category !== 'all') {
      $map = ['electronics' => 1, 'fashion' => 2, 'home' => 5];
      if (isset($map[$category])) $where .= ' AND a.category_id = '.intval($map[$category]);
    }
    if ($min_price !== null) $where .= ' AND a.starting_price >= '.floatval($min_price);
    if ($max_price !== null) $where .= ' AND a.starting_price <= '.floatval($max_price);

    switch ($ordering) {
      case 'pricehigh':
        $order_by = '(SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) DESC';
        break;
      case 'date':
        $order_by = 'a.end_time ASC';
        break;
      case 'pricelow':
      default:
        $order_by = '(SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) ASC';
        break;
    }

    $count_sql = "SELECT COUNT(*) AS cnt FROM Auction a WHERE " . $where;
    $count_res = $mysqli->query($count_sql);
    if ($count_res) {
      $row = $count_res->fetch_assoc();
      $num_results = intval($row['cnt']);
      $count_res->free();
    }
    $max_page = ($results_per_page > 0) ? max(1, ceil($num_results / $results_per_page)) : 1;

    $sql = "SELECT a.auction_id, a.title, a.description, a.category_id, a.starting_price, a.reserve_price, a.end_time, a.image_url, u.username AS seller_name, " .
           "(SELECT COUNT(*) FROM Bid b WHERE b.auction_id = a.auction_id) AS bids, " .
           "(SELECT COALESCE(MAX(b.bid_amount), a.starting_price) FROM Bid b WHERE b.auction_id = a.auction_id) AS current_price " .
           "FROM Auction a JOIN `User` u ON a.seller_id = u.user_id WHERE " . $where . " ORDER BY " . $order_by . " LIMIT " . intval($offset) . "," . intval($results_per_page);

    $res = $mysqli->query($sql);
    if ($res) {
      while ($r = $res->fetch_assoc()) {
        $dummy_items[] = [
          'id' => $r['auction_id'],
          'title' => $r['title'],
          'desc' => $r['description'],
          'price' => $r['current_price'],
          'bids' => $r['bids'],
          'end_time' => new DateTime($r['end_time']),
          'image_url' => $r['image_url'],
          'seller_name' => $r['seller_name'],
          'category_id' => $r['category_id']
        ];
      }
      $res->free();
    }
    $mysqli->close();
  }
?>

  <div class="container mt-5">
    <!-- If result set is empty, print an informative message. Otherwise, show cards -->
    <?php if (empty($dummy_items)): ?>
      <p>No results found. Try adjusting your search criteria.</p>
    <?php else: ?>
      <div class="row">
      <?php
        // Loop through the dummy data and print each auction listing as a card
        foreach ($dummy_items as $item) {
          print_listing_card($item['id'], $item['title'], $item['desc'], $item['price'], $item['bids'], $item['end_time'], isset($item['image_url']) ? $item['image_url'] : null, isset($item['seller_name']) ? $item['seller_name'] : null, isset($item['category_id']) ? $item['category_id'] : null);
        }
      ?>
      </div>

      <!-- Pagination for results listings -->
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
  </div> <!-- End container mt-5 -->

</div> <!-- End main container -->

<!-- Inline script to handle filter panel toggling -->
<script>
// Toggles the off‑canvas filter panel by adding or removing the 'open' class
function toggleFilter() {
  var panel = document.getElementById('filterPanel');
  if (panel) {
    panel.classList.toggle('open');
  }
}
</script>


<?php include_once('footer.php'); ?>