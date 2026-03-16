<?php
/**
 * Hostinger deployment check – run once at https://brown-cobra-381591.hostingersite.com/hostinger_check.php
 * DELETE THIS FILE after fixing the 500 error (security).
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Hostinger check</h1>";
echo "<p>PHP version: <strong>" . PHP_VERSION . "</strong></p>";

// Extensions
$exts = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
echo "<p>Extensions: ";
foreach ($exts as $e) {
    echo $e . '=' . (extension_loaded($e) ? 'ok' : 'MISSING') . ' ';
}
echo "</p>";

// Paths
$root = __DIR__ . DIRECTORY_SEPARATOR;
$tmp = $root . 'tmp';
echo "<p>tmp exists: " . (is_dir($tmp) ? 'yes' : 'no') . ", writable: " . (is_writable($tmp) ? 'yes' : 'no') . "</p>";
if (!is_dir($tmp)) {
    @mkdir($tmp, 0755, true);
    echo "<p>tmp after mkdir: " . (is_dir($tmp) ? 'yes' : 'no') . "</p>";
}

echo "<h2>Database connection</h2>";
echo "<p>Tested when loading app below (uses nia_config.php).</p>";

echo "<h2>App bootstrap</h2>";
try {
    define('ABSPATH', $root);
    require_once $root . 'nia_config.php';
    echo "<p style='color:green'>Bootstrap loaded.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr><p><strong>Delete this file (hostinger_check.php) after use.</strong></p>";
