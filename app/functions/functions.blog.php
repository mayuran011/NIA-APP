<?php
/**
 * Blog (posts/articles) and static pages.
 * article_url(), article-seo-url; page_url(), page-seo-url. /read/:name/:id for both.
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * @return object|null
 */
function get_post($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}posts WHERE id = ? AND status = 'publish' LIMIT 1", [(int) $id]);
}

/**
 * @return object|null Post by slug
 */
function get_post_by_slug($slug) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}posts WHERE slug = ? AND status = 'publish' LIMIT 1", [trim($slug)]);
}

/**
 * @return array
 */
function get_posts($args = []) {
    global $db;
    $pre = $db->prefix();
    $category_id = isset($args['category_id']) ? (int) $args['category_id'] : null;
    $limit = isset($args['limit']) ? (int) $args['limit'] : 20;
    $offset = isset($args['offset']) ? (int) $args['offset'] : 0;
    $status = isset($args['status']) ? $args['status'] : 'publish';
    $where = '1=1';
    $params = [];
    if ($category_id > 0) {
        $where .= ' AND category_id = ?';
        $params[] = $category_id;
    }
    if ($status !== null && $status !== '') {
        $where .= ' AND status = ?';
        $params[] = $status;
    }
    $params[] = $limit;
    $params[] = $offset;
    return $db->fetchAll(
        "SELECT * FROM {$pre}posts WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?",
        $params
    );
}

/**
 * @return object|null
 */
function get_blogcat($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}blogcat WHERE id = ? LIMIT 1", [(int) $id]);
}

/**
 * @return object|null Category by slug
 */
function get_blogcat_by_slug($slug) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}blogcat WHERE slug = ? LIMIT 1", [trim($slug)]);
}

/**
 * @return array
 */
function get_blogcats() {
    global $db;
    $pre = $db->prefix();
    return $db->fetchAll("SELECT * FROM {$pre}blogcat ORDER BY name");
}

/**
 * @return object|null
 */
function get_page($id) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}pages WHERE id = ? LIMIT 1", [(int) $id]);
}

/**
 * @return object|null Page by slug
 */
function get_page_by_slug($slug) {
    global $db;
    $pre = $db->prefix();
    return $db->fetch("SELECT * FROM {$pre}pages WHERE slug = ? LIMIT 1", [trim($slug)]);
}

/**
 * @return array
 */
function get_pages() {
    global $db;
    $pre = $db->prefix();
    return $db->fetchAll("SELECT * FROM {$pre}pages ORDER BY title");
}

/** Blog category URL. */
function blogcat_url($slug) {
    return url('blogcat/' . preg_replace('/[^a-z0-9\-]/i', '-', trim($slug)));
}
