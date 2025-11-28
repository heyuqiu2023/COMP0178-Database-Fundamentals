<?php
require "db.php";

$sql = "
UPDATE AuctionOutcome
SET seller_accepted = 0
WHERE reserve_met = 0
  AND seller_accepted = 0
  AND acceptance_deadline IS NOT NULL
  AND NOW() > acceptance_deadline
";

$pdo->exec($sql);
?>
