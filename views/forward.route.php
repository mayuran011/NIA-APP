<?php
/**
 * /forward/:section — redirects (e.g. playlists, legacy URLs).
 */
if (!defined('in_nia_app')) exit;
$rest = trim($GLOBALS['nia_route_section'] ?? '');
$path = $rest !== '' ? $rest : '';
if ($path === 'playlists' || strpos($path, 'playlist') === 0) {
    redirect(url('lists'));
}
redirect(url());
