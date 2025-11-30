<?php

//检查登陆
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /auction_system/index.php");
        exit();
    }
}

//检查角色
function hasRole($role) {
    if (!isset($_SESSION['role'])) {
        return false;              // 还没登录
    }
    $roleNow = $_SESSION['role'];  // 比如 'buyer' 或 'seller'
   // 只允许这两个
    if (!in_array($role, ['buyer', 'seller'])) {
        return false;
    }

    return $roleNow === $role;     // 完全相等才算有这个角色
}

//加上£
function formatPrice($n) { 
    return '£' . number_format($n, 2); 
}

//时间格式
function formatTime($dt) {
   return date('Y-m-d H:i', strtotime($dt)); 
}

//获取状态
function getAuctionStatus($end, $cancelled = false) {
    if ($cancelled) return 'cancelled';
    $now = time();
    $et = strtotime($end);
    if ($now >= $et) return 'ended';
    return 'active';
}


// display_time_remaining:
// Helper function to help figure out what time to display
function display_time_remaining($interval) {

    if ($interval->days == 0 && $interval->h == 0) {
      // Less than one hour remaining: print mins + seconds:
      $time_remaining = $interval->format('%im %Ss');
    }
    else if ($interval->days == 0) {
      // Less than one day remaining: print hrs + mins:
      $time_remaining = $interval->format('%hh %im');
    }
    else {
      // At least one day remaining: print days + hrs:
      $time_remaining = $interval->format('%ad %hh');
    }

  return $time_remaining;

}

// print_listing_li:
// This function prints an HTML <li> element containing an auction listing
function print_listing_li($auction_id, $title, $desc, $price, $num_bids, $end_time)
{
  // Truncate long descriptions
  if (strlen($desc) > 250) {
    $desc_shortened = substr($desc, 0, 250) . '...';
  }
  else {
    $desc_shortened = $desc;
  }
  
  // Fix language of bid vs. bids
  if ($num_bids == 1) {
    $bid = ' bid';
  }
  else {
    $bid = ' bids';
  }
  
  // Calculate time to auction end
  $now = new DateTime();
  if ($now > $end_time) {
    $time_remaining = 'This auction has ended';
  }
  else {
    // Get interval:
    $time_to_end = date_diff($now, $end_time);
    $time_remaining = display_time_remaining($time_to_end) . ' remaining';
  }
  
  // Print HTML
  echo('
    <li class="list-group-item d-flex justify-content-between">
    <div class="p-2 mr-5"><h5><a href="manage_listing.php?auction_id=' . $auction_id . '">' . $title . '</a></h5>' . $desc_shortened . '</div>
    <div class="text-center text-nowrap"><span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>' . $num_bids . $bid . '<br/>' . $time_remaining . '</div>
  </li>'
  );
}

function print_listing_card($auction_id, $title, $desc, $price, $num_bids, $end_time, $img_url = null, $seller_name = null, $category_id = null) {

    $desc_short = (strlen($desc) > 80) ? substr($desc, 0, 80) . '…' : $desc;
    $bid_label = ($num_bids == 1) ? ' bid' : ' bids';

    $now = new DateTime();
    if ($now > $end_time) {
        $time_remaining = 'Ended';
    } else {
        $interval = date_diff($now, $end_time);
        $time_remaining = display_time_remaining($interval) . ' left';
    }

    echo '<div class="col-sm-6 col-md-4 col-lg-3 mb-4">';
    echo '<div class="card auction-card h-100">';

    // correct link
    echo '<a href="manage_listing.php?auction_id=' . htmlspecialchars($auction_id) . '">';

    // use real auction image if exists
    if (!empty($img_url)) {
        $parts = explode(',', $img_url);
        $first = trim($parts[0]);
        $img = normalize_image_src($first);
        echo '<img src="' . htmlspecialchars($img) . '" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
    } else {
        echo '<img src="img/placeholder.png" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
    }

    echo '</a>';

    echo '<div class="card-body d-flex flex-column">';
    echo '<h5 class="card-title"><a href="manage_listing.php?auction_id=' . htmlspecialchars($auction_id) . '" class="text-dark">' . htmlspecialchars($title) . '</a></h5>';
    echo '<p class="card-text text-muted flex-fill">' . htmlspecialchars($desc_short) . '</p>';
    echo '</div>';

    echo '<div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">';
    echo '<span class="font-weight-bold">£' . number_format($price, 2) . '</span>';
    echo '<small class="text-muted">' . $num_bids . $bid_label . '</small>';
    echo '</div>';

    echo '<div class="card-footer bg-white pt-0 border-top-0">';
    echo '<small class="text-muted">' . $time_remaining . '</small>';
    echo '</div>';

    echo '</div>';
    echo '</div>';
}

?>