<?php
/**
 * Video/music source providers: local file, remote URL, embed code.
 * Supports: local, YouTube, Vimeo, Dailymotion, Twitch, Facebook, SoundCloud, Vine.
 */

if (!defined('in_nia_app')) {
    exit;
}

class NiaProviders {

    protected static $patterns = [
        'youtube'     => [
            'url'   => '#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([a-zA-Z0-9_-]{11})#',
            'embed' => 'https://www.youtube.com/embed/%s',
            'name'  => 'YouTube',
        ],
        'vimeo'       => [
            'url'   => '#vimeo\.com/(?:video/)?(\d+)#',
            'embed' => 'https://player.vimeo.com/video/%s',
            'name'  => 'Vimeo',
        ],
        'dailymotion' => [
            'url'   => '#dailymotion\.com/(?:video|embed/video)/([a-zA-Z0-9]+)#',
            'embed' => 'https://www.dailymotion.com/embed/video/%s',
            'name'  => 'Dailymotion',
        ],
        'twitch'      => [
            'url'   => '#twitch\.tv/(?:videos/)?(\d+)|twitch\.tv/([a-zA-Z0-9_]+)#',
            'embed' => 'https://player.twitch.tv/?video=%s',
            'name'  => 'Twitch',
        ],
        'facebook'    => [
            'url'   => '#facebook\.com/(?:[^/]+/)?videos/(\d+)#',
            'embed' => 'https://www.facebook.com/plugins/video.php?href=https://www.facebook.com/video.php?v=%s',
            'name'  => 'Facebook',
        ],
        'soundcloud'  => [
            'url'   => '#soundcloud\.com/[^/]+/[^/]+#',
            'embed' => null,
            'name'  => 'SoundCloud',
        ],
        'vine'        => [
            'url'   => '#vine\.co/v/([a-zA-Z0-9]+)#',
            'embed' => 'https://vine.co/v/%s/embed/simple',
            'name'  => 'Vine',
        ],
        'gdrive'      => [
            'url'   => '#drive\.google\.com/file/d/([a-zA-Z0-9_-]+)#',
            'embed' => 'https://drive.google.com/file/d/%s/preview',
            'name'  => 'Google Drive',
        ],
        'direct_video' => [
            'url'   => '#^https?://[^\s]+\.(mp4|webm|m4v|mov|ogg)(?:\?[^#]*)?$#i',
            'embed' => null,
            'name'  => 'Direct video',
        ],
        'direct_audio' => [
            'url'   => '#^https?://[^\s]+\.(mp3|m4a|ogg|wav|aac)(?:\?[^#]*)?$#i',
            'embed' => null,
            'name'  => 'Direct audio',
        ],
    ];

    /**
     * Detect provider and return slug (youtube, vimeo, local, etc.) and external id if any.
     * @param string $input URL or embed code
     * @return array { 'source' => string, 'id' => string|null, 'url' => string|null, 'embed_url' => string|null }
     */
    public static function detect($input) {
        $input = trim($input);
        if ($input === '') {
            return ['source' => 'local', 'id' => null, 'url' => null, 'embed_url' => null];
        }

        // Already embed or URL
        if (preg_match('#^<iframe#i', $input)) {
            foreach (self::$patterns as $slug => $info) {
                if (preg_match($info['url'], $input, $m)) {
                    $id = $m[1] ?? ($m[2] ?? '');
                    $embed = $info['embed'] ? sprintf($info['embed'], $id) : null;
                    return [
                        'source'    => $slug,
                        'id'       => $id,
                        'url'      => $input,
                        'embed_url' => $embed,
                    ];
                }
            }
            return ['source' => 'embed', 'id' => null, 'url' => $input, 'embed_url' => null];
        }

        if (preg_match('#^https?://#i', $input)) {
            foreach (self::$patterns as $slug => $info) {
                if (preg_match($info['url'], $input, $m)) {
                    $id = $m[1] ?? ($m[2] ?? '');
                    $embed = $info['embed'] ? sprintf($info['embed'], $id) : $input;
                    return [
                        'source'    => $slug,
                        'id'       => $id,
                        'url'      => $input,
                        'embed_url' => $embed,
                    ];
                }
            }
            return ['source' => 'remote', 'id' => null, 'url' => $input, 'embed_url' => $input];
        }

        return ['source' => 'local', 'id' => null, 'url' => $input, 'embed_url' => null];
    }

    /**
     * @return string[]
     */
    public static function getSupportedSources() {
        return array_keys(self::$patterns);
    }

    /**
     * @param string $slug
     * @return string
     */
    public static function getProviderName($slug) {
        return self::$patterns[$slug]['name'] ?? $slug;
    }

    /**
     * Fetch title/thumbnail via oEmbed where available.
     * @param string $url Public URL (YouTube, Vimeo, etc.)
     * @return array { title => string, thumbnail_url => string|null }
     */
    public static function fetchMetadata($url) {
        $url = trim($url);
        $out = ['title' => '', 'thumbnail_url' => null];
        if ($url === '') return $out;
        $enc = rawurlencode($url);
        if (preg_match('#youtube\.com|youtu\.be#i', $url)) {
            $api = 'https://www.youtube.com/oembed?url=' . $enc . '&format=json';
            $json = @file_get_contents($api);
            if ($json) {
                $d = json_decode($json, true);
                if ($d) {
                    $out['title'] = $d['title'] ?? '';
                    $out['thumbnail_url'] = $d['thumbnail_url'] ?? null;
                }
            }
        } elseif (preg_match('#vimeo\.com#i', $url)) {
            $api = 'https://vimeo.com/api/oembed.json?url=' . $enc;
            $json = @file_get_contents($api);
            if ($json) {
                $d = json_decode($json, true);
                if ($d) {
                    $out['title'] = $d['title'] ?? '';
                    $out['thumbnail_url'] = $d['thumbnail_url'] ?? null;
                }
            }
        }
        if ($out['title'] === '' && preg_match('#^https?://[^/]+/[^?#]*/([^/?#]+)(?:\?|$)#', $url, $m)) {
            $out['title'] = rawurldecode($m[1]);
        }
        return $out;
    }
}
