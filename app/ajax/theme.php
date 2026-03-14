<?php
/** Set dark/light theme (session); redirect back. */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

$mode = isset($_GET['mode']) ? trim($_GET['mode']) : '';
if (in_array($mode, ['light', 'dark'], true)) {
    $_SESSION['vibe_dark'] = $mode === 'dark' ? '1' : '0';
}
$back = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], rtrim(SITE_URL, '/')) === 0
    ? $_SERVER['HTTP_REFERER']
    : url('');
redirect($back);
