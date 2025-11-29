<?php
/*
 * Utility functions for the auction site.  These functions provide helper
 * functionality for displaying remaining time and rendering auction
 * listings.  They can be included in multiple pages using `require` or
 * `include_once`.
 */

// display_time_remaining:
// Helper function to calculate a human‑friendly time remaining string.
function display_time_remaining($interval) {
    if ($interval->days == 0 && $interval->h == 0) {
        // Less than one hour remaining: print minutes and seconds
        $time_remaining = $interval->format('%im %Ss');
    } elseif ($interval->days == 0) {
        // Less than one day remaining: print hours and minutes
        $time_remaining = $interval->format('%hh %im');
    } else {
        // At least one day remaining: print days and hours
        $time_remaining = $interval->format('%ad %hh');
    }
    return $time_remaining;
}

// normalize_image_src:
// Ensure image src is a valid URL for the browser. If the stored value is a full URL (http/https)
// or an absolute path starting with '/', return as-is. Otherwise, prefix with the current
// script directory so relative paths like 'img/auctions/foo.jpg' resolve correctly.
function normalize_image_src($src) {
    if (empty($src)) return null;
    $src = trim($src);
    // if multiple images provided, caller should pick the one it needs
    if (preg_match('#^https?://#i', $src)) {
        return $src;
    }
    if (strpos($src, '/') === 0) {
        // already root-relative
        return $src;
    }
    // prefix with script directory
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
    if ($base === '' || $base === '.') $base = '';
    return $base . '/' . ltrim($src, '\/');
}

// print_listing_li:
// This function prints an HTML <li> element containing an auction listing.
// It accepts the item ID, title, description, current price, number of
// bids and the auction end time.  The function truncates long
// descriptions and uses display_time_remaining() to show how much time
// remains until the auction ends.
function print_listing_li($item_id, $title, $desc, $price, $num_bids, $end_time) {
    // Truncate long descriptions
    if (strlen($desc) > 250) {
        $desc_shortened = substr($desc, 0, 250) . '...';
    } else {
        $desc_shortened = $desc;
    }

    // Fix language of bid vs. bids
    $bid_label = ($num_bids == 1) ? ' bid' : ' bids';

    // Calculate time to auction end
    $now = new DateTime();
    if ($now > $end_time) {
        $time_remaining = 'This auction has ended';
    } else {
        $time_to_end = date_diff($now, $end_time);
        $time_remaining = display_time_remaining($time_to_end) . ' remaining';
    }

    // Print HTML for the listing
    echo('  <li class="list-group-item d-flex justify-content-between">
      <div class="p-2 mr-5"><h5><a href="listing.php?item_id=' . htmlspecialchars($item_id) . '">' . htmlspecialchars($title) . '</a></h5>' . htmlspecialchars($desc_shortened) . '</div>
      <div class="text-center text-nowrap"><span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>' . $num_bids . $bid_label . '<br/>' . $time_remaining . '</div>
    </li>\n');
}

// print_listing_card:
// This function prints a Bootstrap card within a grid layout representing an auction listing.
// It accepts the same parameters as print_listing_li but outputs a more visual layout using
// cards.  The card includes a placeholder image (replace with actual item images once
// available), a truncated description, price, number of bids and time remaining.
function print_listing_card($item_id, $title, $desc, $price, $num_bids, $end_time, $image_src = null, $seller_name = null, $category_id = null) {
    // Truncate long descriptions for card layout
    $desc_short = (strlen($desc) > 80) ? substr($desc, 0, 80) . '…' : $desc;
    $bid_label = ($num_bids == 1) ? ' bid' : ' bids';
    // Calculate time to auction end
    $now = new DateTime();
    if ($now > $end_time) {
        $time_remaining = 'Ended';
    } else {
        $interval = date_diff($now, $end_time);
        $time_remaining = display_time_remaining($interval) . ' left';
    }
    // Output card markup
    echo '<div class="col-sm-6 col-md-4 col-lg-3 mb-4">';
    echo '<div class="card auction-card h-100">';
    // Image: use provided image_src if present; otherwise try DB lookup; fall back to placeholder
    $img_src_final = null;
    if (!empty($image_src)) {
        // if multiple images stored comma-separated, pick first
        if (strpos($image_src, ',') !== false) {
            $parts = explode(',', $image_src);
            $first = trim($parts[0]);
            if ($first !== '') $img_src_final = $first;
        } else {
            $img_src_final = $image_src;
        }
    }
    // If still empty, try DB lookup
    if (empty($img_src_final) && !empty($item_id) && is_numeric($item_id)) {
        $db_host = 'localhost'; $db_user = 'root'; $db_pass = ''; $db_name = 'auction';
        $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        if (!$mysqli->connect_errno) {
            $stmt = $mysqli->prepare('SELECT image_url FROM `Auction` WHERE auction_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $item_id);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($res && $row = $res->fetch_assoc()) {
                        $img_field = $row['image_url'];
                        if (!empty($img_field)) {
                            $parts = explode(',', $img_field);
                            $first = trim($parts[0]);
                            if ($first !== '') $img_src_final = $first;
                        }
                    }
                    $res->free();
                }
                $stmt->close();
            }
            $mysqli->close();
        }
    }
    // Normalize and fallback
    if (empty($img_src_final) && !empty($category_id)) {
        $cat_map = [1 => 'img/category_electronics.svg', 2 => 'img/category_fashion.svg', 3 => 'img/category_books.svg', 4 => 'img/category_sports.svg', 5 => 'img/category_home.svg'];
        if (isset($cat_map[intval($category_id)])) {
            $img_src_final = $cat_map[intval($category_id)];
        }
    }
    $img_src_final = normalize_image_src($img_src_final) ?: normalize_image_src('img/placeholder.png');
    echo '<a href="listing.php?item_id=' . htmlspecialchars($item_id) . '">';
    echo '<img src="' . htmlspecialchars($img_src_final) . '" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
    echo '</a>';
    echo '<div class="card-body d-flex flex-column">';
    echo '<h5 class="card-title"><a href="listing.php?item_id=' . htmlspecialchars($item_id) . '" class="text-dark">' . htmlspecialchars($title) . '</a></h5>';
    if (!empty($seller_name)) {
        echo '<small class="text-muted d-block mb-2">by ' . htmlspecialchars($seller_name) . '</small>';
    }
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