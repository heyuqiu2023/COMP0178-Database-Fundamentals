<?php
// Database helper: centralised MySQLi connection
function get_db() {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'auction';

    $mysqli = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_errno) {
        // In production, do not echo DB errors; here we return null to allow pages to handle gracefully
        return null;
    }
    // set charset
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}
?>