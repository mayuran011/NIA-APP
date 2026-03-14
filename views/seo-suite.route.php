<?php
/**
 * Site SEO Suite: Sitemap, Robots.txt, and Meta controls.
 */
if (!defined('in_nia_app')) exit;

$action = $_GET['action'] ?? '';

// --- 1. Sitemap Generator ---
if ($action === 'sitemap') {
    global $db;
    $xml = $db->fetchCached('xml_sitemap', 86400, function() use ($db) {
        $pre = $db->prefix();
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Static Pages
        $urls = ['', 'videos/browse', 'music/browse', 'blog', 'following', 'premiumhub'];
        foreach ($urls as $u) {
            $out .= '<url><loc>' . url($u) . '</loc><priority>1.0</priority></url>' . "\n";
        }

        // Dynamic: Videos / Music (watch for video, listen for music)
        $items = $db->fetchAll("SELECT id, title, type FROM {$pre}videos WHERE private = 0 ORDER BY created_at DESC LIMIT 2000");
        foreach ($items as $v) {
            $v = is_array($v) ? (object)$v : $v;
            $loc = function_exists('media_play_url') ? media_play_url($v->id, $v->type ?? 'video', $v->title ?? '') : watch_url($v->id, $v->title ?? '');
            $out .= '<url><loc>' . $loc . '</loc><priority>0.8</priority></url>' . "\n";
        }

        // Dynamic: Blog posts
        try {
            $posts = $db->fetchAll("SELECT id, title, slug FROM {$pre}posts WHERE status = 'publish' ORDER BY id DESC LIMIT 500");
            foreach ($posts as $p) {
                $p = is_array($p) ? (object)$p : $p;
                $out .= '<url><loc>' . article_url($p->slug, $p->id) . '</loc><priority>0.7</priority></url>' . "\n";
            }
        } catch (Exception $e) {}

        // Dynamic: Channels / Categories
        $chans = $db->fetchAll("SELECT id, name, slug FROM {$pre}channels ORDER BY name ASC");
        foreach ($chans as $c) {
            $c = is_array($c) ? (object)$c : $c;
            $slug = $c->slug ?: 'channel';
            $out .= '<url><loc>' . url('category/' . $slug) . '</loc><priority>0.6</priority></url>' . "\n";
        }

        // Dynamic: Users / Profiles
        $users = $db->fetchAll("SELECT id, username FROM {$pre}users ORDER BY id DESC LIMIT 500");
        foreach ($users as $u) {
            $u = is_array($u) ? (object)$u : $u;
            $out .= '<url><loc>' . profile_url($u->username, $u->id) . '</loc><priority>0.5</priority></url>' . "\n";
        }

        $out .= '</urlset>';
        return $out;
    });

    header('Content-Type: application/xml; charset=utf-8');
    echo $xml;
    exit;
}

// --- 2. Robots.txt ---
if ($action === 'robots') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\n";
    echo "Disallow: /admin/\n"; // Standard admin protection
    echo "Disallow: /" . ADMINCP . "/\n";
    echo "Disallow: /app/ajax/\n";
    echo "Allow: /\n\n";
    echo "Sitemap: " . url('seo-suite?action=sitemap') . "\n";
    exit;
}

// --- 3. Social Meta Tags Builder (Helper) ---
function get_social_meta($title = '', $desc = '', $thumb = '', $type = 'website') {
    $sitename = get_option('sitename', 'Nia App');
    $title = $title ?: $sitename;
    $desc = $desc ?: get_option('meta_description', '');
    $thumb = $thumb ?: get_option('logo_url', '');
    
    $html = '<!-- SEO Suite: Social Meta -->'."\n";
    $html .= '<meta property="og:site_name" content="'._e($sitename).'">'."\n";
    $html .= '<meta property="og:type" content="'._e($type).'">'."\n";
    $html .= '<meta property="og:title" content="'._e($title).'">'."\n";
    $html .= '<meta property="og:description" content="'._e($desc).'">'."\n";
    $html .= '<meta property="og:image" content="'._e($thumb).'">'."\n";
    $html .= '<meta name="twitter:card" content="summary_large_image">'."\n";
    $html .= '<meta name="twitter:title" content="'._e($title).'">'."\n";
    $html .= '<meta name="twitter:description" content="'._e($desc).'">'."\n";
    $html .= '<meta name="twitter:image" content="'._e($thumb).'">'."\n";
    return $html;
}
?>
