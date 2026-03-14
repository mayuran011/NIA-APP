<?php
/**
 * Admin (moderator) helpers.
 */
if (!defined('in_nia_app')) exit;

/** Admin base URL (e.g. /moderator/) */
function admin_url($path = '') {
    $base = defined('ADMINCP') ? ADMINCP : 'moderator';
    return url($base . ($path !== '' ? '/' . ltrim($path, '/') : ''));
}

/** Current admin section (first segment). */
function admin_section() {
    $s = isset($_GET['section']) ? trim($_GET['section']) : '';
    $parts = $s === '' ? [] : explode('/', $s);
    return $parts[0] ?? '';
}

/** Admin subsection (e.g. edit/5 → edit). */
function admin_subsection() {
    $s = isset($_GET['section']) ? trim($_GET['section']) : '';
    $parts = $s === '' ? [] : explode('/', $s);
    return $parts[1] ?? '';
}

/** Admin segment by index (0 = section, 1 = subsection, 2 = id, etc.). */
function admin_segment($i) {
    $s = isset($_GET['section']) ? trim($_GET['section']) : '';
    $parts = $s === '' ? [] : explode('/', $s);
    return $parts[$i] ?? '';
}

/**
 * Normalize fetchAll rows to objects with named properties (handles numeric-keyed arrays).
 * @param array|\Traversable|null $items Rows from fetchAll
 * @param array $columnNames Property names in SELECT order (empty = auto-detect from first row)
 * @return array
 */
function admin_normalize_rows($items, array $columnNames = []) {
    if ($items === null || $items === false) return [];
    if (!is_array($items)) {
        $items = $items instanceof \Traversable ? iterator_to_array($items) : (array) $items;
    }
    if (empty($items)) return [];
    if (empty($columnNames)) {
        $first = $items[0];
        $keys = is_array($first) ? array_keys($first) : array_keys(get_object_vars($first));
        $columnNames = array_filter($keys, 'is_string') ?: array_values($keys);
    }
    $out = [];
    foreach ($items as $row) {
        $arr = is_array($row) ? $row : (array) $row;
        $o = new \stdClass();
        foreach ($columnNames as $i => $key) {
            $val = null;
            if (array_key_exists($key, $arr)) {
                $val = $arr[$key];
            } elseif (array_key_exists($i, $arr)) {
                $val = $arr[$i];
            } elseif (is_string($key)) {
                foreach ($arr as $k => $v) {
                    if (!is_string($k)) continue;
                    if (strtolower($k) === strtolower($key)) { $val = $v; break; }
                    if (strpos($k, '.') !== false && strtolower(substr($k, strrpos($k, '.') + 1)) === strtolower($key)) { $val = $v; break; }
                }
            }
            $o->$key = $val;
        }
        $out[] = $o;
    }
    return $out;
}

/**
 * Normalize a single fetch row to an object with named properties (handles array or object).
 * @param array|object|null $row One row from fetch()
 * @param array $columnNames Property names in SELECT order (empty = use array keys or object vars)
 * @return object|null
 */
function admin_normalize_row($row, array $columnNames = []) {
    if ($row === null) return null;
    if (is_object($row) && isset($row->id)) {
        if (empty($columnNames)) return $row;
        $o = new \stdClass();
        foreach ($columnNames as $key) {
            $o->$key = $row->$key ?? null;
        }
        return $o;
    }
    $arr = is_array($row) ? $row : (array) $row;
    if (empty($columnNames)) {
        $keys = array_keys($arr);
        if (array_filter($keys, 'is_string') === $keys) {
            return (object) $arr;
        }
        return $row;
    }
    $o = new \stdClass();
    foreach ($columnNames as $i => $key) {
        $o->$key = $arr[$key] ?? $arr[$i] ?? null;
    }
    return $o;
}

/**
 * Safe count from fetch('SELECT COUNT(*) AS c ...') (handles array or object).
 * @param array|object|null $row
 * @return int
 */
function admin_fetch_count($row) {
    if ($row === null) return 0;
    if (is_array($row)) return (int) ($row['c'] ?? $row[0] ?? 0);
    return (int) ($row->c ?? 0);
}
