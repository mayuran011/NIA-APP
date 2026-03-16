<?php
/**
 * 500 error diagnostic – run at https://vdo.nia.yt/diagnose_500.php
 * Shows the real PHP error/exception. DELETE THIS FILE after fixing (security).
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
header('Content-Type: text/html; charset=utf-8');

$root = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
echo "<h1>500 diagnostic</h1>";
echo "<p>PHP " . PHP_VERSION . " &middot; ABSPATH: <code>" . htmlspecialchars($root) . "</code></p>";

// Extensions
$exts = ['pdo', 'pdo_mysql', 'mbstring', 'json'];
echo "<p>Extensions: ";
foreach ($exts as $e) {
    echo $e . '=' . (extension_loaded($e) ? 'ok' : '<strong>MISSING</strong>') . ' ';
}
echo "</p>";

echo "<h2>Loading app (nia_config → bootstrap)</h2>";
try {
    define('ABSPATH', $root);
    require_once $root . 'nia_config.php';
    echo "<p style='color:green'><strong>Bootstrap OK.</strong> If the home page still 500s, the error may be in router/dispatch or later.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre style='background:#f5f5f5;padding:1em;overflow:auto'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " (line " . $e->getLine() . ")</p>";
}

echo "<hr><p><strong>Delete this file (diagnose_500.php) after use.</strong></p>";
