<?php
/**
 * Conversations: two-way private messages (vibe_conversation, vibe_conversation_data).
 */

if (!defined('in_nia_app')) {
    exit;
}

global $db;

/**
 * Get or create a conversation between two users (order of ids normalized for unique key).
 */
function get_or_create_conversation($user_id, $other_user_id) {
    global $db;
    $user_id = (int) $user_id;
    $other_user_id = (int) $other_user_id;
    if ($user_id <= 0 || $other_user_id <= 0 || $user_id === $other_user_id) return null;
    $one = min($user_id, $other_user_id);
    $two = max($user_id, $other_user_id);
    $pre = $db->prefix();
    $row = $db->fetch("SELECT id FROM {$pre}conversation WHERE user_one_id = ? AND user_two_id = ?", [$one, $two]);
    if ($row) return $row;
    $db->query("INSERT INTO {$pre}conversation (user_one_id, user_two_id) VALUES (?, ?)", [$one, $two]);
    return (object) ['id' => (int) $db->pdo()->lastInsertId()];
}

/**
 * Get conversation by id; ensure current user is participant.
 * @return object|null { id, user_one_id, user_two_id, other_user, last_message }
 */
function get_conversation($id, $user_id = null) {
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return null;
    $id = (int) $id;
    $pre = $db->prefix();
    $row = $db->fetch("SELECT * FROM {$pre}conversation WHERE id = ?", [$id]);
    if (!$row) return null;
    $one = (int) $row->user_one_id;
    $two = (int) $row->user_two_id;
    if ($user_id !== $one && $user_id !== $two) return null;
    $other_id = $user_id === $one ? $two : $one;
    $other = get_user($other_id);
    $last = $db->fetch("SELECT body, created_at, user_id FROM {$pre}conversation_data WHERE conversation_id = ? ORDER BY id DESC LIMIT 1", [$id]);
    $row->other_user = $other;
    $row->other_user_id = $other_id;
    $row->last_message = $last;
    return $row;
}

/**
 * List conversations for user (inbox); with last message and other participant.
 * @return array
 */
function get_conversations($user_id = null) {
    global $db;
    $user_id = $user_id ?? current_user_id();
    if ($user_id <= 0) return [];
    $pre = $db->prefix();
    $list = $db->fetchAll(
        "SELECT c.* FROM {$pre}conversation c WHERE c.user_one_id = ? OR c.user_two_id = ? ORDER BY c.updated_at DESC, c.id DESC",
        [$user_id, $user_id]
    );
    $out = [];
    foreach ($list as $c) {
        $conv = get_conversation($c->id, $user_id);
        if ($conv) $out[] = $conv;
    }
    return $out;
}

/**
 * Get messages in a conversation (paginated).
 * @return array
 */
function get_conversation_messages($conversation_id, $limit = 50, $offset = 0) {
    global $db;
    $conversation_id = (int) $conversation_id;
    $limit = (int) $limit;
    $offset = (int) $offset;
    $pre = $db->prefix();
    return $db->fetchAll(
        "SELECT d.*, u.name AS sender_name, u.username AS sender_username FROM {$pre}conversation_data d LEFT JOIN {$pre}users u ON u.id = d.user_id WHERE d.conversation_id = ? ORDER BY d.id ASC LIMIT ? OFFSET ?",
        [$conversation_id, $limit, $offset]
    );
}

/**
 * Send a message in a conversation; updates conversation.updated_at.
 */
function send_conversation_message($conversation_id, $user_id, $body) {
    global $db;
    $conversation_id = (int) $conversation_id;
    $user_id = (int) $user_id;
    $body = trim($body);
    if ($user_id <= 0 || $body === '') return false;
    $conv = get_conversation($conversation_id, $user_id);
    if (!$conv) return false;
    $pre = $db->prefix();
    $db->query("INSERT INTO {$pre}conversation_data (conversation_id, user_id, body) VALUES (?, ?, ?)", [$conversation_id, $user_id, $body]);
    $db->query("UPDATE {$pre}conversation SET updated_at = NOW() WHERE id = ?", [$conversation_id]);
    return true;
}
