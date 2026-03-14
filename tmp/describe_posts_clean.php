<?php
include 'vibe1_config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $db->query('DESCRIBE nia_posts');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . PHP_EOL;
}
?>
