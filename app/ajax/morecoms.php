<?php
/** Load more comments (threaded); object_type, object_id, parent_id, offset, limit. */
define('ABSPATH', dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR);
require_once ABSPATH . 'nia_config.php';
header('Content-Type: application/json; charset=utf-8');
$object_type = isset($_GET['object_type']) ? trim($_GET['object_type']) : 'video';
$object_id = (int) ($_GET['object_id'] ?? 0);
$parent_id = (int) ($_GET['parent_id'] ?? 0);
$offset = (int) ($_GET['offset'] ?? 0);
$limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
if ($object_id <= 0) {
    echo json_encode(['ok' => false, 'comments' => []]);
    exit;
}
$comments = get_comments($object_type, $object_id, $parent_id, $limit, $offset);
echo json_encode(['ok' => true, 'comments' => $comments]);
