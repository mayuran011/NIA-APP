<?php
include 'vibe1_config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query('SHOW TABLES');
while($row = $res->fetch_array()) {
    echo $row[0].PHP_EOL;
}
?>
