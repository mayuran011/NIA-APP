<?php
/**
 * One-off CLI script to import a SQL dump (e.g. test.sql) into the database.
 * Uses DB credentials from nia_config.php. DELETE this file after use on production.
 *
 * Usage (run from project root):
 *   php tmp/import_sql.php "C:\Users\mayur\Downloads\test.sql"
 *   php tmp/import_sql.php tmp/test.sql
 *
 * Or copy test.sql to tmp/test.sql and run:
 *   php tmp/import_sql.php
 */
if (php_sapi_name() !== 'cli') {
    die('Run from command line only: php tmp/import_sql.php [path/to/dump.sql]' . "\n");
}

$projectRoot = dirname(__DIR__);
define('ABSPATH', $projectRoot . DIRECTORY_SEPARATOR);

// Load only DB constants (avoid full bootstrap/redirect)
if (is_file(ABSPATH . 'nia_config.php')) {
    $config = file_get_contents(ABSPATH . 'nia_config.php');
    if (preg_match("/define\s*\(\s*['\"]DB_HOST['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config, $m)) { define('DB_HOST', $m[1]); }
    if (preg_match("/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config, $m)) { define('DB_NAME', $m[1]); }
    if (preg_match("/define\s*\(\s*['\"]DB_USER['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config, $m)) { define('DB_USER', $m[1]); }
    if (preg_match("/define\s*\(\s*['\"]DB_PASS['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $config, $m)) { define('DB_PASS', $m[1]); }
    if (!defined('DB_CHARSET')) { define('DB_CHARSET', 'utf8mb4'); }
} else {
    die("nia_config.php not found.\n");
}

$replace = false;
$args = array_slice($argv, 1);
foreach ($args as $i => $a) {
    if ($a === '--replace' || $a === '-f') {
        $replace = true;
        unset($args[$i]);
        break;
    }
}
$args = array_values($args);
$sqlFile = $args[0] ?? $projectRoot . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'test.sql';
$sqlFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sqlFile);

if (!is_file($sqlFile)) {
    die("SQL file not found: " . $sqlFile . "\nUsage: php tmp/import_sql.php [--replace] \"path/to/dump.sql\"\n");
}

echo "Connecting to " . DB_HOST . " / " . DB_NAME . " ...\n";
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}
$mysqli->set_charset(DB_CHARSET);

if ($replace) {
    echo "Dropping existing nia_* tables ...\n";
    $r = $mysqli->query("SHOW TABLES");
    $prefix = 'nia_';
    while ($row = $r ? $r->fetch_array() : null) {
        if (!$row) break;
        $t = $row[0];
        if (strpos($t, $prefix) === 0) {
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
            $mysqli->query("DROP TABLE IF EXISTS `" . $mysqli->real_escape_string($t) . "`");
            $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
            echo "  Dropped $t\n";
        }
    }
}

echo "Reading " . $sqlFile . " ...\n";
$sql = file_get_contents($sqlFile);
if ($sql === false || $sql === '') {
    die("Could not read or empty file.\n");
}

echo "Executing (this may take a minute) ...\n";
if ($mysqli->multi_query($sql)) {
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    if ($mysqli->errno) {
        echo "Warning: " . $mysqli->error . "\n";
    }
    echo "Import finished.\n";
} else {
    echo "Error: " . $mysqli->error . "\n";
    exit(1);
}
$mysqli->close();
