<?php
/**
 * One-time 500 diagnostic. Upload to server, open https://vdo.nia.yt/check_500.php
 * to see the real PHP error. DELETE THIS FILE after fixing (security).
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
header('Content-Type: text/html; charset=utf-8');

$root = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
echo "<h1>500 check</h1>";
echo "<p>PHP " . PHP_VERSION . " &middot; " . htmlspecialchars($root) . "</p>";

$envKeys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'SITE_URL'];
echo "<p>Env: ";
foreach ($envKeys as $k) {
    $v = getenv($k);
    $v2 = isset($_ENV[$k]) ? $_ENV[$k] : '';
    $show = ($v !== false && $v !== '') ? substr($v, 0, 20) . (strlen($v) > 20 ? '…' : '') : (($v2 !== '') ? '[ENV]' : '<em>not set</em>');
    if ($k === 'DB_USER' || $k === 'DB_PASS') $show = $v ? '***' : '<em>not set</em>';
    echo $k . '=' . $show . ' ';
}
echo "</p>";

echo "<h2>Load app</h2>";
try {
    define('ABSPATH', $root);
    require_once $root . 'nia_config.php';
    echo "<p style='color:green'><strong>OK.</strong> If home page still 500s, check tmp/error.log.</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<pre style='background:#f5f5f5;padding:1em;overflow:auto'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "<p>" . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
}

echo "<hr><p><strong>Delete check_500.php after use.</strong></p>";
