<?php
/**
 * Upload for playlist or batch (redirects to upload.php or handles multiple files).
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

if (!is_logged()) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'login']);
    exit;
}

$_REQUEST['pl'] = 1;
require __DIR__ . DIRECTORY_SEPARATOR . 'upload.php';
