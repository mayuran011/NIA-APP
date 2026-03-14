<?php
define('ABSPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
global $db;
$pre = $db->prefix();
$table = $pre . 'youtube_import_sources';
$res = $db->fetchAll("SHOW INDEX FROM $table");
print_r($res);
