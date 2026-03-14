<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
require_once 'nia_config.php';

echo "Testing connection to " . DB_NAME . " on " . DB_HOST . " as " . DB_USER . "...\n";

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "Connection successful!\n";

    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(", ", $tables) . "\n";

    // Check specifically for nia_options
    if (in_array(DB_PREFIX . 'options', $tables)) {
        echo "Table " . DB_PREFIX . "options exists.\n";
    } else {
        echo "Table " . DB_PREFIX . "options is MISSING!\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
