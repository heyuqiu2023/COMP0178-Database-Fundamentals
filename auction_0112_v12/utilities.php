<?php

// 检查是否登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /auction_system/index.php");
        exit();
    }
}

// 检查角色
function hasRole($role) {
    if (!isset($_SESSION['role'])) {
        return false;              // 还没登录
    }
    $roleNow = $_SESSION['role'];  // 比如 'buyer' 或 'seller'
    // 只允许这两个角色
    if (!in_array($role, ['buyer', 'seller'])) {
        return false;
    }

    return $roleNow === $role;     // 完全相等才算有这个角色
}

// 格式化价格，加上£
function formatPrice($n) {
    return '£' . number_format($n, 2);
}

// 格式化时间
function formatTime($dt) {
    return date('Y-m-d H:i', strtotime($dt));
}

// 获取拍卖状态
function getAuctionStatus($end, $cancelled = false) {
    if ($cancelled) return 'cancelled';
    $now = time();
    $et  = strtotime($end);
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
    elseif ($interval->days == 0) {
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
// 输出一个 <li> 元素来展示拍卖信息
function print_listing_li($auction_id, $title, $desc, $price, $num_bids, $end_time)
{
    // 截断过长的描述
    if (strlen($desc) > 250) {
        $desc_shortened = substr($desc, 0, 250) . '...';
    } else {
        $desc_shortened = $desc;
    }

    // 单复数处理
    if ($num_bids == 1) {
        $bid = ' bid';
    } else {
        $bid = ' bids';
    }

    // 计算拍卖结束时间
    $now = new DateTime();
    if ($now > $end_time) {
        $time_remaining = 'This auction has ended';
    } else {
        // 获取剩余时间
        $time_to_end    = date_diff($now, $end_time);
        $time_remaining = display_time_remaining($time_to_end) . ' remaining';
    }

    // 根据用户角色设置跳转页面：卖家跳转到管理页面，买家跳转到公开页面
    $targetPage = 'listing.php';
    if (isset($_SESSION['is_seller']) && $_SESSION['is_seller'] == 1) {
        $targetPage = 'manage_listing.php';
    }

    // 输出 HTML
    echo(
        '<li class="list-group-item d-flex justify-content-between">'
        . '<div class="p-2 mr-5"><h5><a href="' . $targetPage . '?auction_id=' . htmlspecialchars($auction_id) . '">' . htmlspecialchars($title) . '</a></h5>'
        . htmlspecialchars($desc_shortened) . '</div>'
        . '<div class="text-center text-nowrap"><span style="font-size: 1.5em">£' . number_format($price, 2) . '</span><br/>'
        . $num_bids . $bid . '<br/>' . $time_remaining . '</div>'
        . '</li>'
    );
}

function print_listing_card($auction_id, $title, $desc, $price, $num_bids, $end_time, $img_url = null, $seller_name = null, $category_id = null) {

    // 截断描述
    $desc_short = (strlen($desc) > 80) ? substr($desc, 0, 80) . '…' : $desc;
    $bid_label  = ($num_bids == 1) ? ' bid' : ' bids';

    $now = new DateTime();
    if ($now > $end_time) {
        $time_remaining = 'Ended';
    } else {
        $interval       = date_diff($now, $end_time);
        $time_remaining = display_time_remaining($interval) . ' left';
    }

    echo '<div class="col-sm-6 col-md-4 col-lg-3 mb-4">';
    echo '<div class="card auction-card h-100">';

    // 根据用户角色确定跳转页面
    $targetPage = 'listing.php';
    if (isset($_SESSION['is_seller']) && $_SESSION['is_seller'] == 1) {
        $targetPage = 'manage_listing.php';
    }

    // 图片和标题的链接都跳转到同一页面
    echo '<a href="' . $targetPage . '?auction_id=' . htmlspecialchars($auction_id) . '">';

    // 显示图片：多图时显示轮播
    if (!empty($img_url)) {
        $parts = array_filter(array_map('trim', explode(',', $img_url)));
        if (count($parts) > 1) {
            $carouselId = 'carousel_' . htmlspecialchars($auction_id);
            echo '<div id="' . $carouselId . '" class="carousel slide" data-ride="carousel">';
            echo '<div class="carousel-inner">';
            $first = true;
            foreach ($parts as $p) {
                $img = normalize_image_src($p);
                echo '<div class="carousel-item' . ($first ? ' active' : '') . '">';
                echo '<img src="' . htmlspecialchars($img) . '" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
                echo '</div>';
                $first = false;
            }
            echo '</div>';
            echo '<a class="carousel-control-prev" href="#' . $carouselId . '" role="button" data-slide="prev">';
            echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
            echo '<span class="sr-only">Previous</span>';
            echo '</a>';
            echo '<a class="carousel-control-next" href="#' . $carouselId . '" role="button" data-slide="next">';
            echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
            echo '<span class="sr-only">Next</span>';
            echo '</a>';
            echo '</div>';
        } else {
            $first = normalize_image_src($parts[0]);
            echo '<img src="' . htmlspecialchars($first) . '" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
        }
    } else {
        echo '<img src="img/placeholder.png" class="card-img-top" alt="' . htmlspecialchars($title) . '">';
    }

    echo '</a>';

    echo '<div class="card-body d-flex flex-column">';
    // 标题链接指向同一个目标页面
    echo '<h5 class="card-title"><a href="' . $targetPage . '?auction_id=' . htmlspecialchars($auction_id) . '" class="text-dark">' . htmlspecialchars($title) . '</a></h5>';
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

// normalize_image_src:
// 将数据库中存储的图片路径/URL 转换为 <img> 的 src 属性
function normalize_image_src($src) {
    $src = trim($src);
    if ($src === '') return 'img/placeholder.png';

    // 已经是绝对URL，直接返回
    if (preg_match('#^https?://#i', $src)) return $src;

    // 以 / 开头，视为根路径
    if (strpos($src, '/') === 0) return $src;

    // 否则视为相对路径
    return $src;
}

?>
