<?php
// Redirect the home page to the browse page.  This keeps index.php simple
// and allows browse.php to serve as the primary landing page of the site.
header('Location: browse.php');
exit();
