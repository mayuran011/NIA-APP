<?php
/**
 * Route parsing and dispatch.
 * Defined in index.php + nia_config.php; SEO overrides from options (video-seo-url, profile-seo-url, etc.).
 *
 * Route table (path → purpose):
 *   /                     Home – configurable homepage boxes
 *   /video/:id/:name      Video list or redirect to /watch or /listen
 *   /watch/:id            Video playback
 *   /listen/:id           Music playback
 *   /image/:id/:name      Single image page (SEO slug)
 *   /view/:id             Single image view
 *   /profile/:name/:id/   User profile (channel)
 *   /videos/:section      Video list (browse, most-viewed, top-rated, featured, etc.)
 *   /music/:section       Music list (same sections)
 *   /images/:section      Image list
 *   /category/            Channels/categories (video)
 *   /musicfilter/         Channels/categories (music)
 *   /imagefilter/         Channels/categories (image)
 *   /playlist/:name/:id/  Playlist page (video or image)
 *   /lists/:section       Playlists listing
 *   /albums/:section      Albums (image galleries)
 *   /album/:section       Album (image gallery)
 *   /show/:section        Search (videos/music)
 *   /imgsearch/           Image search
 *   /pplsearch/           People search
 *   /playlistsearch/      Playlist search
 *   /tag/:section         Tag results
 *   /add-video/, /add-music/, /add-image/  Upload/share
 *   /share/:section       Share (submit by URL)
 *   /me/:section          User manager (library, likes, history, watch later, playlists, edit media)
 *   /dashboard/:section   Dashboard (settings, activity, studio)
 *   /activity/:section    Buzz/activity feed
 *   /users/:section       Channels/members listing
 *   /login/, /register/   Auth (forgot password)
 *   /conversation/:id     Private conversation (messaging)
 *   /msg/:section         Messages (inbox)
 *   /payment/:section     Payments (premium)
 *   /premiumhub/:section  Premium hub
 *   /blog/, /read/:name/:id  Blog and static pages / articles (read page)
 *   /embed/:section       Embed player (iframe)
 *   /feed/:section        RSS feed
 *   /api/:section         API endpoint
 *   /forward/:section      Forward (playlists/redirects)
 */

if (!defined('in_nia_app')) {
    exit;
}

function nia_get_path() {
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base)) ?: '/';
    }
    $path = '/' . trim($path, '/');
    return $path === '' ? '/' : $path;
}

/**
 * Resolve first URL segment with optional SEO overrides from options.
 * Options: video-seo-url, image-seo-url, profile-seo-url, channel-seo-url, page-seo-url, article-seo-url.
 */
function nia_resolve_seo_segment($first) {
    $overrides = [
        get_option('video-seo-url')   => 'video',
        get_option('image-seo-url')   => 'image',
        get_option('profile-seo-url') => 'profile',
        get_option('channel-seo-url') => 'category',
        get_option('page-seo-url')    => 'read',
        get_option('article-seo-url') => 'read',
    ];
    foreach ($overrides as $slug => $route) {
        if ($slug !== '' && $slug !== null && $first === $slug) {
            return $route;
        }
    }
    return $first;
}

function nia_match_routes($path) {
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $first = $segments[0] ?? '';

    // SEO overrides: custom slug → canonical route name
    $first = nia_resolve_seo_segment($first);

    if (defined('ADMINCP') && $first === ADMINCP) {
        return ['admin', implode('/', array_slice($segments, 1))];
    }

    $rest = implode('/', array_slice($segments, 1));
    $section = $segments[1] ?? '';

    // Route table: first segment → [route_name, section]
    $routes = [
        ''                  => ['home', ''],
        'video'             => ['video', $rest],
        'watch'             => ['watch', $rest],
        'listen'            => ['listen', $rest],
        'image'             => ['image', $rest],
        'view'              => ['view', $rest],
        'profile'           => ['profile', $rest],
        'videos'            => ['videos', $section ?: 'browse'],
        'music'             => ['music', $section ?: 'browse'],
        'images'            => ['images', $section ?: 'browse'],
        'category'          => ['category', $rest],
        'musicfilter'       => ['musicfilter', $rest],
        'imagefilter'       => ['imagefilter', $rest],
        'playlist'          => ['playlist', $rest],
        'lists'             => ['lists', $section],
        'albums'            => ['albums', $section],
        'album'             => ['album', $rest],
        'show'              => ['show', $section],
        'imgsearch'         => ['imgsearch', $section],
        'pplsearch'         => ['pplsearch', $section],
        'playlistsearch'    => ['playlistsearch', $section],
        'tag'               => ['tag', $rest],
        'add-video'         => ['add-video', ''],
        'add-music'         => ['add-music', ''],
        'add-image'         => ['add-image', ''],
        'share'             => ['share', $section],
        'me'                => ['me', $rest],
        'dashboard'         => ['dashboard', $section],
        'activity'          => ['activity', $section],
        'users'             => ['users', $section],
        'login'             => ['login', ''],
        'register'          => ['register', ''],
        'forgot'            => ['forgot', ''],
        'conversation'      => ['conversation', $section],
        'msg'               => ['msg', $section],
        'payment'           => ['payment', $section],
        'premiumhub'        => ['premiumhub', $section],
        'blog'              => ['blog', $rest],
        'blogcat'           => ['blogcat', $rest],
        'read'              => ['read', $rest],
        'embed'             => ['embed', $rest],
        'download'          => ['download', $section],
        'feed'              => ['feed', $section],
        'api'               => ['api', $rest],
        'forward'           => ['forward', $rest],
        'seo-suite'         => ['seo-suite', $section],
    ];

    return $routes[$first] ?? ['show', $first];
}

function nia_dispatch($route_name, $section) {
    $views_dir = ABSPATH . 'views' . DIRECTORY_SEPARATOR;
    $theme_dir = ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR;

    $map = [
        'home'          => 'home.route.php',
        'video'         => 'video.route.php',
        'watch'         => 'watch.route.php',
        'listen'        => 'listen.route.php',
        'image'         => 'image.route.php',
        'view'          => 'view.route.php',
        'profile'       => 'profile.route.php',
        'videos'        => 'videos.route.php',
        'music'         => 'music.route.php',
        'images'        => 'images.route.php',
        'category'      => 'category.route.php',
        'musicfilter'   => 'musicfilter.route.php',
        'imagefilter'   => 'imagefilter.route.php',
        'playlist'      => 'playlist.route.php',
        'lists'         => 'lists.route.php',
        'albums'        => 'albums.route.php',
        'album'         => 'album.route.php',
        'show'          => 'show.route.php',
        'imgsearch'     => 'imgsearch.route.php',
        'pplsearch'     => 'pplsearch.route.php',
        'playlistsearch'=> 'playlistsearch.route.php',
        'tag'           => 'tag.route.php',
        'add-video'     => 'add-video.route.php',
        'add-music'     => 'add-music.route.php',
        'add-image'     => 'add-image.route.php',
        'share'         => 'share.route.php',
        'me'            => 'me.route.php',
        'dashboard'     => 'dashboard.route.php',
        'activity'      => 'activity.route.php',
        'users'         => 'users.route.php',
        'login'         => 'login.route.php',
        'register'      => 'register.route.php',
        'forgot'        => 'forgot.route.php',
        'conversation'  => 'conversation.route.php',
        'msg'           => 'msg.route.php',
        'payment'       => 'payment.route.php',
        'premiumhub'    => 'premiumhub.route.php',
        'blog'          => 'blog.route.php',
        'blogcat'       => 'blogcat.route.php',
        'read'          => 'read.route.php',
        'embed'         => 'embed.route.php',
        'download'      => 'download.route.php',
        'feed'          => 'feed.route.php',
        'api'           => 'api.route.php',
        'forward'       => 'forward.route.php',
        'admin'         => '../moderator/index.php',
        'seo-suite'     => 'seo-suite.route.php',
    ];

    $file = $map[$route_name] ?? null;
    if (!$file) {
        $file = 'show.route.php';
        $section = $route_name;
    }

    if ($route_name === 'admin') {
        $path = ABSPATH . 'moderator' . DIRECTORY_SEPARATOR . 'index.php';
        if (is_file($path)) {
            $_GET['section'] = $section;
            include $path;
            return;
        }
    }

    $path = $views_dir . $file;
    if (is_file($path)) {
        $GLOBALS['nia_route_section'] = $section;
        include $path;
        return;
    }

    // Fallback: home or 404
    if (is_file($views_dir . 'home.route.php')) {
        include $views_dir . 'home.route.php';
        return;
    }

    header('HTTP/1.0 404 Not Found');
    echo '<!DOCTYPE html><html><head><title>404</title></head><body><h1>Not Found</h1></body></html>';
}
