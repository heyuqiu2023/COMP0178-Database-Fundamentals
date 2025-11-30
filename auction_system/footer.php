<?php
// Footer file for the auction site.  This includes JavaScript libraries
// necessary for Bootstrap and closes the HTML document.  If desired,
// additional footer content (links, copyright notices, etc.) can be added
// before the closing </body> tag.
?>

    <!-- jQuery, Popper.js and Bootstrap JS from CDNs -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
      $(function () {
        var $carousel = $('#heroCarousel');
        var $dots = $('.hero-dots li');

        // 点击小圆点：切换到对应图片，后面 slide 事件会自动同步高亮
        $dots.on('click', function (e) {
          e.preventDefault();
          var index = parseInt($(this).attr('data-slide-to'), 10);  // ✅ 用 attr 直接拿属性
          $carousel.carousel(index);                                // 切到第 index 张
        });

        // 轮播（自动/手动）结束后：根据当前图片 index 更新小圆点高亮
        $carousel.on('slid.bs.carousel', function (e) {
          // 当前显示的 .carousel-item 是哪个
          var index = $(e.relatedTarget).index();

          $dots.removeClass('active')
              .eq(index).addClass('active');
        });
      });
</script>

  </body>
</html>