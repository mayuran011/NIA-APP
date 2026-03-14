<?php
/**
 * YouTube auto-import: fetch new videos from sources (channels/playlists) that have auto_import=1.
 */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';

if (!function_exists('nia_youtube_process_source')) {
    exit('YouTube import functions not loaded.');
}

global $db;
if (!isset($db)) {
    exit('Database not available.');
}

$pre = $db->prefix();
$table_sources = $pre . 'youtube_import_sources';

// Fetch all automated sources (channels and playlists)
$sources = $db->fetchAll("SELECT id FROM {$table_sources} WHERE auto_import = 1 ORDER BY last_imported_at ASC LIMIT 10");

if (empty($sources)) {
    exit('No auto-import sources found.');
}

$total_imported = 0;

foreach ($sources as $row) {
    // Process source using core function (25 videos per run to avoid timeout)
    $total_imported += nia_youtube_process_source($row->id, 25);
}

echo "YouTube Cron execution finished. Total videos imported: {$total_imported}.\n";
?>
