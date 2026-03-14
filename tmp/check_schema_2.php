<?php
define('in_nia_app', true);
require 'nia_config.php';
require 'lib/class.db.php';
$db = new NiaDb($db_config);
$pre = $db->prefix();
$cols = $db->fetchAll("DESCRIBE {$pre}videos");
foreach($cols as $c) {
    echo $c->Field . " (" . $c->Type . ")\n";
}
echo "---\n";
$cols2 = $db->fetchAll("DESCRIBE {$pre}channels");
foreach($cols2 as $c) {
    echo $c->Field . " (" . $c->Type . ")\n";
}
