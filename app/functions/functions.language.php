<?php
/**
 * Multi-language: languages from options (default_language, languages_enabled);
 * init_lang(), _lang(), current_lang(), is_rtl(); language files in lang/{code}.php.
 */

if (!defined('in_nia_app')) {
    exit;
}

/** @var array lang_code => strings */
$vibe_lang_strings = [];

/**
 * Initialize current language from request (?lang=), session, or option default_language.
 * Call once at bootstrap (or start of request).
 */
function init_lang() {
    $enabled = get_option('languages_enabled', 'en');
    $codes = array_map('trim', explode(',', $enabled));
    $codes = array_filter($codes);
    if (empty($codes)) {
        $codes = ['en'];
    }
    $default = get_option('default_language', 'en');
    if (!in_array($default, $codes, true)) {
        $default = $codes[0];
    }
    $requested = isset($_GET['lang']) ? trim($_GET['lang']) : '';
    if ($requested !== '' && in_array($requested, $codes, true)) {
        $_SESSION['vibe_lang'] = $requested;
    }
    if (empty($_SESSION['vibe_lang']) || !in_array($_SESSION['vibe_lang'], $codes, true)) {
        $_SESSION['vibe_lang'] = $default;
    }
}

/**
 * Current language code (e.g. en, es). Call init_lang() first.
 * @return string
 */
function current_lang() {
    return isset($_SESSION['vibe_lang']) ? $_SESSION['vibe_lang'] : get_option('default_language', 'en');
}

/**
 * Translate key. Loads lang/{code}.php (array $lang) and returns $lang[$key] ?? $key.
 * @param string $key
 * @return string
 */
function _lang($key) {
    global $vibe_lang_strings;
    $code = current_lang();
    if (!isset($vibe_lang_strings[$code])) {
        $path = ABSPATH . 'lang' . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '', $code) . '.php';
        $vibe_lang_strings[$code] = is_file($path) ? (include $path) : [];
        if (!is_array($vibe_lang_strings[$code])) {
            $vibe_lang_strings[$code] = [];
        }
    }
    return isset($vibe_lang_strings[$code][$key]) ? $vibe_lang_strings[$code][$key] : $key;
}

/**
 * Whether the current language is RTL (option rtl_languages = comma-separated codes, e.g. ar,he).
 * @return bool
 */
function is_rtl() {
    $rtl = get_option('rtl_languages', '');
    if ($rtl === '') {
        return false;
    }
    $codes = array_map('trim', explode(',', $rtl));
    return in_array(current_lang(), $codes, true);
}

/**
 * URL for switching to language $code (current path + ?lang= or &lang=).
 * @param string $code
 * @return string
 */
function lang_switch_url($code) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $q = strpos($uri, '?');
    if ($q !== false) {
        parse_str(substr($uri, $q + 1), $params);
        $params['lang'] = $code;
        return substr($uri, 0, $q) . '?' . http_build_query($params);
    }
    return $uri . (strpos($uri, '?') !== false ? '&' : '?') . 'lang=' . $code;
}

/**
 * Enabled language codes for switcher.
 * @return array [ ['code' => 'en', 'name' => 'English'], ... ]
 */
function enabled_languages() {
    $enabled = get_option('languages_enabled', 'en');
    $codes = array_map('trim', explode(',', $enabled));
    $codes = array_filter($codes);
    if (empty($codes)) {
        $codes = ['en'];
    }
    $names = [
        'en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
        'pt' => 'Portuguese', 'ar' => 'العربية', 'he' => 'עברית', 'it' => 'Italian',
    ];
    $out = [];
    foreach ($codes as $c) {
        $out[] = ['code' => $c, 'name' => $names[$c] ?? $c];
    }
    return $out;
}
