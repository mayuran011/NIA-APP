<?php
/**
 * Share: Share video (YouTube, Google Drive, MP4 URL, playlist), Share music, Share picture, Share article.
 */
if (!defined('in_nia_app')) exit;
if (!is_logged()) {
    redirect(url('login'));
}
$nia_section = $GLOBALS['nia_route_section'] ?? '';
$page_title = 'Share';
$preview = null;
$error = '';
$success = '';
$music_video_skipped = 0;
$article_view_url = '';
$share_page = isset($_GET['page']) ? trim($_GET['page']) : 'share-video';
$allowed_pages = ['share-video', 'upload-video', 'share-music', 'upload-music', 'share-picture', 'upload-picture', 'share-article'];
if (!in_array($share_page, $allowed_pages, true)) {
    $share_page = 'share-video';
}
$importer_section = isset($_POST['importer_section']) ? trim($_POST['importer_section']) : 'video';
$api_key = function_exists('nia_youtube_api_key') ? nia_youtube_api_key() : '';

// --- LOAD CHANNELS ---
global $db;
$pre = $db->prefix();
$channels = $db->fetchAll("SELECT id, name, type FROM {$pre}channels ORDER BY name ASC");
$video_channels = array_filter($channels, function($c) { return $c->type === 'video'; });
$music_channels = array_filter($channels, function($c) { return $c->type === 'music'; });
$image_channels = array_filter($channels, function($c) { return $c->type === 'image'; });
$blog_cats = function_exists('get_blogcats') ? get_blogcats() : [];

// Add single video by URL (existing flow)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    $url = isset($_POST['url']) ? trim($_POST['url']) : '';

    // When adding music, only audio URLs are allowed (no video-only sources)
    $music_video_sources = ['youtube', 'vimeo', 'dailymotion', 'twitch', 'facebook', 'vine'];

    if ($action === 'add' && $url !== '') {
        $detect = NiaProviders::detect($url);
        if ($detect['source'] !== 'local') {
            global $db;
            $type = isset($_POST['type']) && $_POST['type'] === 'music' ? 'music' : 'video';
            if ($type === 'music' && in_array($detect['source'], $music_video_sources, true)) {
                $error = 'Video URLs are not allowed for music. Use audio links only (e.g. direct MP3, SoundCloud, Google Drive audio).';
            } else {
            $embed_url = $detect['embed_url'] ?? null;
            $remote_url = $detect['url'] ?? $url;
            $store_url = ($embed_url && in_array($detect['source'], ['gdrive', 'direct_video', 'direct_audio'], true)) ? $embed_url : $remote_url;
            $title = trim($_POST['title'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $cat = (int)($_POST['category_id'] ?? 0);
            $priv = (int)($_POST['private'] ?? 0);
            $nsfw = (int)($_POST['nsfw'] ?? 0);
            
            $thumb = null;
            $yt_published = null;
            $yt_channel = null;
            $duration = 0;
            if (in_array($detect['source'], ['youtube', 'vimeo', 'gdrive'], true) && preg_match('#^https?://#', $remote_url)) {
                if ($detect['source'] === 'youtube' && !empty($detect['id']) && function_exists('nia_youtube_video_details')) {
                    $yt_v = nia_youtube_video_details($detect['id']);
                    if ($yt_v) {
                        if ($title === '') $title = $yt_v['title'] ?? '';
                        if ($desc === '') $desc = $yt_v['description'] ?? '';
                        $thumb = $yt_v['thumb'] ?? $thumb;
                        $yt_published = $yt_v['published_at'] ?? null;
                        $yt_channel = $yt_v['channel_title'] ?? null;
                        $duration = (int) ($yt_v['duration'] ?? 0);
                    }
                }
                if ($title === '') {
                    $meta = NiaProviders::fetchMetadata($remote_url);
                    $title = $meta['title'] ?? '';
                    $desc = $meta['description'] ?? $desc;
                    $thumb = $meta['thumbnail_url'] ?? $thumb;
                }
            }
            if ($title === '') $title = NiaProviders::getProviderName($detect['source']) . ($type === 'music' ? ' audio' : ' video');
            $pre = $db->prefix();
            $slug = preg_replace('/[^a-z0-9\-]/i', '-', $title) ?: 'v-' . time();
            try {
                $db->query(
                    "INSERT INTO {$pre}videos (user_id, title, description, category_id, private, nsfw, type, source, remote_url, embed_code, thumb, duration, yt_published_at, yt_channel_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [current_user_id(), $title, $desc, $cat, $priv, $nsfw, $type, $detect['source'], $store_url, $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null, $thumb, $duration, $yt_published, $yt_channel]
                );
            } catch (Throwable $e) {
                $db->query(
                    "INSERT INTO {$pre}videos (user_id, title, description, category_id, private, nsfw, type, source, remote_url, embed_code, thumb, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [current_user_id(), $title, $desc, $cat, $priv, $nsfw, $type, $detect['source'], $store_url, $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null, $thumb, $duration]
                );
            }
            $id = (int) $db->pdo()->lastInsertId();
            if (function_exists('add_activity')) add_activity(current_user_id(), 'shared', $type, $id);
            redirect($type === 'music' ? listen_url($id) : watch_url($id));
            }
        }
    }

    // Bulk add: multiple URLs (YouTube keyword/playlist results or pasted URLs)
    if ($action === 'add_bulk') {
        $urls = [];
        if (!empty($_POST['urls']) && is_array($_POST['urls'])) {
            $urls = array_filter(array_map('trim', $_POST['urls']));
        } else {
            $single = !empty($_POST['url']) ? trim($_POST['url']) : '';
            $lines = !empty($_POST['url_lines']) ? preg_split('/\r\n|\r|\n/', trim($_POST['url_lines']), -1, PREG_SPLIT_NO_EMPTY) : [];
            $fromLines = array_filter(array_map('trim', $lines));
            if ($single !== '') {
                $urls = array_merge([$single], $fromLines);
            } else {
                $urls = $fromLines;
            }
            $urls = array_values(array_unique(array_filter($urls)));
        }
        $type = isset($_POST['type']) && $_POST['type'] === 'music' ? 'music' : 'video';
        $cat = (int)($_POST['category_id'] ?? 0);
        $priv = (int)($_POST['private'] ?? 0);
        $nsfw = (int)($_POST['nsfw'] ?? 0);
        
        $added = 0;
        $skipped_video = 0;
        global $db;
        $pre = $db->prefix();
        foreach ($urls as $url) {
            if ($url === '') continue;
            $detect = NiaProviders::detect($url);
            if ($detect['source'] === 'local') continue;
            if ($type === 'music' && in_array($detect['source'], $music_video_sources, true)) {
                $skipped_video++;
                continue;
            }
            $embed_url = $detect['embed_url'] ?? null;
            $remote_url = $detect['url'] ?? $url;
            $store_url = ($embed_url && in_array($detect['source'], ['gdrive', 'direct_video', 'direct_audio'], true)) ? $embed_url : $remote_url;
            $title = '';
            $desc = '';
            $thumb = null;
            $yt_published = null;
            $yt_channel = null;
            $duration = 0;
            if (in_array($detect['source'], ['youtube', 'vimeo', 'gdrive'], true) && preg_match('#^https?://#', $remote_url)) {
                if ($detect['source'] === 'youtube' && !empty($detect['id']) && function_exists('nia_youtube_video_details')) {
                    $yt_v = nia_youtube_video_details($detect['id']);
                    if ($yt_v) {
                        $title = $yt_v['title'] ?? '';
                        $desc = $yt_v['description'] ?? '';
                        $thumb = $yt_v['thumb'] ?? null;
                        $yt_published = $yt_v['published_at'] ?? null;
                        $yt_channel = $yt_v['channel_title'] ?? null;
                        $duration = (int) ($yt_v['duration'] ?? 0);
                    }
                }
                if ($title === '') {
                    $meta = NiaProviders::fetchMetadata($remote_url);
                    $title = $meta['title'] ?? '';
                    $desc = $meta['description'] ?? '';
                    $thumb = $meta['thumbnail_url'] ?? null;
                }
            }
            if ($title === '') $title = NiaProviders::getProviderName($detect['source']) . ($type === 'music' ? ' audio' : ' video');
            try {
                $db->query(
                    "INSERT INTO {$pre}videos (user_id, title, description, category_id, private, nsfw, type, source, remote_url, embed_code, thumb, duration, yt_published_at, yt_channel_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [current_user_id(), $title, $desc, $cat, $priv, $nsfw, $type, $detect['source'], $store_url, $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null, $thumb, $duration, $yt_published, $yt_channel]
                );
            } catch (Throwable $e) {
                $db->query(
                    "INSERT INTO {$pre}videos (user_id, title, description, category_id, private, nsfw, type, source, remote_url, embed_code, thumb, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [current_user_id(), $title, $desc, $cat, $priv, $nsfw, $type, $detect['source'], $store_url, $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null, $thumb, $duration]
                );
            }
            $id = (int) $db->pdo()->lastInsertId();
            if ($id) { $added++; if (function_exists('add_activity')) add_activity(current_user_id(), 'shared', $type, $id); }
        }
        if ($type === 'music' && $skipped_video > 0) {
            $music_video_skipped = $skipped_video;
            if ($added === 0) {
                $error = 'No audio links were added. ' . $skipped_video . ' video URL(s) were skipped. Only audio URLs are allowed for music (e.g. SoundCloud, direct MP3, Google Drive audio).';
            } else {
                $success = "Added {$added} item(s) to your content.";
            }
        } else {
            $success = $added > 0 ? "Added {$added} item(s) to your content." : 'No valid URLs to add.';
        }
        $importer_section = isset($_POST['importer_section']) ? $_POST['importer_section'] : 'video';
    }

    if ($action === 'preview' && $url !== '') {
        $detect = NiaProviders::detect($url);
        $preview = $detect;
        $preview['meta'] = [];
        if (in_array($detect['source'], ['youtube', 'vimeo', 'gdrive'], true) && preg_match('#^https?://#', $url)) {
            $preview['meta'] = NiaProviders::fetchMetadata($url);
        }
        if (empty($preview['meta']['title']) && in_array($detect['source'], ['direct_video', 'direct_audio'], true)) {
            $preview['meta'] = NiaProviders::fetchMetadata($url);
        }
        $importer_section = isset($_POST['importer_section']) ? $_POST['importer_section'] : 'video';
    }

    // YouTube Importer: Keywords search
    if ($action === 'youtube_keywords' && $api_key !== '' && function_exists('nia_youtube_search')) {
        $keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
        if ($keyword !== '') {
            $keyword_results = nia_youtube_search($keyword, 15);
            $importer_section = 'keywords';
        } else {
            $error = 'Enter a keyword.';
        }
    }

    // YouTube Importer: Playlist
    if ($action === 'youtube_playlist' && $api_key !== '' && function_exists('nia_youtube_parse_playlist_id') && function_exists('nia_youtube_playlist_items')) {
        $playlist_url = isset($_POST['playlist_url']) ? trim($_POST['playlist_url']) : '';
        $playlist_id = nia_youtube_parse_playlist_id($playlist_url);
        $playlist_import_mode = isset($_POST['playlist_import_mode']) && $_POST['playlist_import_mode'] === 'add_all' ? 'add_all' : 'select';
        if ($playlist_id) {
            $playlist_res = nia_youtube_playlist_items($playlist_id, 50, null);
            $playlist_results = isset($playlist_res['items']) ? $playlist_res['items'] : [];
            $importer_section = 'playlist';
            if ($playlist_import_mode === 'add_all' && !empty($playlist_results)) {
                $type = isset($_POST['type']) && $_POST['type'] === 'music' ? 'music' : 'video';
                $cat = (int)($_POST['category_id'] ?? 0);
                $priv = (int)($_POST['private'] ?? 0);
                $nsfw = (int)($_POST['nsfw'] ?? 0);
                $added = 0;
                global $db;
                $pre = $db->prefix();
                foreach ($playlist_results as $v) {
                    $url = $v['url'] ?? '';
                    if ($url === '') continue;
                    $detect = NiaProviders::detect($url);
                    if ($detect['source'] === 'local') continue;
                    if ($type === 'music' && in_array($detect['source'], $music_video_sources, true)) continue;
                    $embed_url = $detect['embed_url'] ?? null;
                    $remote_url = $detect['url'] ?? $url;
                    $store_url = ($embed_url && in_array($detect['source'], ['gdrive', 'direct_video', 'direct_audio'], true)) ? $embed_url : $remote_url;
                    $title = $v['title'] ?? '';
                    $thumb = $v['thumb'] ?? null;
                    $yt_published = $v['published_at'] ?? null;
                    $yt_channel = $v['channel_title'] ?? null;
                    $duration = 0;
                    if ($detect['source'] === 'youtube' && !empty($detect['id']) && function_exists('nia_youtube_video_details')) {
                        $yt_v = nia_youtube_video_details($detect['id']);
                        if ($yt_v) {
                            if ($title === '') $title = $yt_v['title'] ?? '';
                            $thumb = $yt_v['thumb'] ?? $thumb;
                            $yt_published = $yt_v['published_at'] ?? $yt_published;
                            $yt_channel = $yt_v['channel_title'] ?? $yt_channel;
                            $duration = (int) ($yt_v['duration'] ?? 0);
                        }
                    }
                    if ($title === '') {
                        $meta = NiaProviders::fetchMetadata($remote_url);
                        $title = $meta['title'] ?? '';
                        $thumb = $meta['thumbnail_url'] ?? $thumb;
                    }
                    if ($title === '') $title = NiaProviders::getProviderName($detect['source']) . ' video';
                    $desc = '';
                    try {
                        $db->query(
                            "INSERT INTO {$pre}videos (user_id, title, description, category_id, private, nsfw, type, source, remote_url, embed_code, thumb, duration, yt_published_at, yt_channel_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [current_user_id(), $title, $desc, $cat, $priv, $nsfw, $type, $detect['source'], $store_url, $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null, $thumb, $duration, $yt_published, $yt_channel]
                        );
                    } catch (Throwable $e) {
                        $db->query(
                            "INSERT INTO {$pre}videos (user_id, title, description, category_id, private, nsfw, type, source, remote_url, embed_code, thumb, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                            [current_user_id(), $title, $desc, $cat, $priv, $nsfw, $type, $detect['source'], $store_url, $embed_url ? '<iframe src="' . $embed_url . '" frameborder="0" allowfullscreen></iframe>' : null, $thumb, $duration]
                        );
                    }
                    $id = (int) $db->pdo()->lastInsertId();
                    if ($id) { $added++; if (function_exists('add_activity')) add_activity(current_user_id(), 'shared', $type, $id); }
                }
                $success = $added > 0 ? "Added {$added} video(s) from the playlist." : 'No new videos were added from the playlist.';
                $playlist_results = [];
            }
        } else {
            $error = 'Enter a valid YouTube playlist URL (e.g. https://www.youtube.com/playlist?list=...).';
        }
    }

    // YouTube Importer: Channel (add to auto-import + initial import of all existing videos)
    if ($action === 'youtube_channel' && function_exists('nia_youtube_parse_channel_id') && function_exists('nia_youtube_add_import_source')) {
        $channel_url = isset($_POST['channel_url']) ? trim($_POST['channel_url']) : '';
        $auto_import = isset($_POST['auto_import']) ? 1 : 0;
        $channel_id = nia_youtube_parse_channel_id($channel_url);
        if ($channel_id) {
            $source_db_id = nia_youtube_add_import_source(current_user_id(), 'channel', $channel_id, $auto_import);
            if ($source_db_id > 0 && function_exists('nia_youtube_refresh_source_display')) {
                nia_youtube_refresh_source_display($source_db_id);
            }
            $initial_count = 0;
            if ($source_db_id > 0 && function_exists('nia_youtube_process_source')) {
                $initial_max = defined('NIA_YOUTUBE_SYNC_MAX_PER_RUN') ? NIA_YOUTUBE_SYNC_MAX_PER_RUN : 1000;
                $initial_count = nia_youtube_process_source($source_db_id, $initial_max);
            }
            $channel_added = true;
            $total_from_channel = $initial_count;
            if ($source_db_id > 0 && function_exists('nia_youtube_get_source')) {
                $src = nia_youtube_get_source($source_db_id);
                if ($src && isset($src->total_imported)) $total_from_channel = (int) $src->total_imported;
            }
            if ($initial_count > 0) {
                $success = $initial_count . ' video(s) added now. Total from this channel: ' . $total_from_channel . '.';
                if ($auto_import) $success .= ' New videos will be auto-imported (run cron or Admin → YouTube).';
            } else {
                $success = 'Channel added. No new videos to import (all already added). Total from this channel: ' . $total_from_channel . '.';
                if ($auto_import) $success .= ' New videos will be auto-imported when added on YouTube (run cron or Admin → YouTube).';
            }
            $importer_section = 'channel';
        } else {
            $api_err = function_exists('nia_youtube_last_api_error') ? nia_youtube_last_api_error() : null;
            if ($api_err) {
                $error = 'YouTube API: ' . $api_err;
            } else {
                $error = 'Enter a valid YouTube channel URL (e.g. https://www.youtube.com/channel/UC... or https://www.youtube.com/@username).';
            }
        }
    }

    if ($action === 'remove_import_source' && function_exists('nia_youtube_remove_import_source')) {
        $id = isset($_POST['source_id']) ? (int) $_POST['source_id'] : 0;
        if ($id) nia_youtube_remove_import_source(current_user_id(), $id);
        $success = 'Import source removed.';
    }

    // Add pictures by URL (Google Drive or any image URL) – insert into images table
    if ($action === 'add_bulk_images') {
        $urls = [];
        if (!empty($_POST['url']) && trim($_POST['url']) !== '') $urls[] = trim($_POST['url']);
        if (!empty($_POST['url_lines'])) {
            $lines = preg_split('/\r\n|\r|\n/', trim($_POST['url_lines']), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($lines as $line) if (($u = trim($line)) !== '') $urls[] = $u;
        }
        $urls = array_unique($urls);
        $added = 0;
        $album = (int)($_POST['album_id'] ?? 0);
        global $db;
        $pre = $db->prefix();
        foreach ($urls as $url) {
            $title = 'Image';
            if (preg_match('#/([^/?#]+)(?:\?|$)#', $url, $m)) $title = rawurldecode($m[1]);
            $db->query(
                "INSERT INTO {$pre}images (user_id, album_id, title, path, thumb) VALUES (?, ?, ?, ?, ?)",
                [current_user_id(), $album, $title, $url, $url]
            );
            if ((int) $db->pdo()->lastInsertId()) $added++;
        }
        $success = $added > 0 ? "Added {$added} picture(s)." : 'No valid URLs.';
    }

    // Share article: save to nia_posts (blog)
    if ($action === 'share_article') {
        $article_title = isset($_POST['article_title']) ? trim($_POST['article_title']) : '';
        $article_slug = isset($_POST['article_slug']) ? trim($_POST['article_slug']) : '';
        $article_content = isset($_POST['article_content']) ? trim($_POST['article_content']) : '';
        $article_excerpt = isset($_POST['article_excerpt']) ? trim($_POST['article_excerpt']) : '';
        $article_category_id = isset($_POST['article_category_id']) ? (int) $_POST['article_category_id'] : 0;
        $article_status = (isset($_POST['article_status']) && $_POST['article_status'] === 'draft') ? 'draft' : 'publish';
        $article_id_edit = isset($_POST['article_id']) ? (int) $_POST['article_id'] : 0;

        if ($article_title === '') {
            $error = 'Blog title is required.';
        } else {
            if ($article_slug === '') {
                $article_slug = preg_replace('/[^a-z0-9\-]/i', '-', strtolower($article_title));
                $article_slug = trim(preg_replace('/\-+/', '-', $article_slug), '-') ?: 'post-' . time();
            } else {
                $article_slug = preg_replace('/[^a-z0-9\-]/i', '-', strtolower($article_slug));
                $article_slug = trim(preg_replace('/\-+/', '-', $article_slug), '-') ?: 'post-' . time();
            }
            $uid = current_user_id();
            $pre = $db->prefix();
            if ($article_id_edit > 0) {
                $db->query(
                    "UPDATE {$pre}posts SET title = ?, slug = ?, content = ?, excerpt = ?, status = ?, category_id = ?, updated_at = NOW() WHERE id = ? AND user_id = ?",
                    [$article_title, $article_slug, $article_content, $article_excerpt, $article_status, $article_category_id, $article_id_edit, $uid]
                );
                $new_post_id = $article_id_edit;
            } else {
                $db->query(
                    "INSERT INTO {$pre}posts (user_id, title, slug, content, excerpt, status, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$uid, $article_title, $article_slug, $article_content, $article_excerpt, $article_status, $article_category_id]
                );
                $new_post_id = (int) $db->pdo()->lastInsertId();
            }
            if ($new_post_id > 0 && $article_status === 'publish' && function_exists('article_url')) {
                redirect(article_url($article_slug, $new_post_id));
            }
            $success = 'Blog post saved. ' . ($article_status === 'draft' ? 'Saved as draft.' : 'Published.');
            $article_view_url = ($new_post_id > 0 && function_exists('article_url')) ? article_url($article_slug, $new_post_id) : '';
        }
    }
}

$import_sources = function_exists('nia_youtube_get_import_sources') ? nia_youtube_get_import_sources(current_user_id()) : [];

$upload_video_url = rtrim(SITE_URL, '/') . '/app/uploading/upload-video.php';
$upload_music_url = rtrim(SITE_URL, '/') . '/app/uploading/upload-mp3.php';
$upload_image_url = rtrim(SITE_URL, '/') . '/app/uploading/upload-image.php';

require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.header.php';
?>
<style>
.nia-share-nav { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.nia-share-nav a { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1rem; border-radius: 0.75rem; text-decoration: none; color: rgba(255,255,255,0.75); background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); font-size: 0.9rem; transition: all 0.2s ease; }
.nia-share-nav a:hover { color: #fff; background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.12); }
.nia-share-nav a.active { color: #fff; background: rgba(var(--bs-primary-rgb), 0.25); border-color: rgba(var(--bs-primary-rgb), 0.5); }
.nia-share-nav a .material-icons { font-size: 1.15rem; opacity: 0.9; }
.nia-share-panel-card { background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.08); border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem; }
.nia-share-tabs { display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
.nia-share-tabs .nav-link { padding: 0.5rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; color: rgba(255,255,255,0.7); background: transparent; border: none; text-decoration: none; transition: all 0.2s; }
.nia-share-tabs .nav-link:hover { color: #fff; background: rgba(255,255,255,0.06); }
.nia-share-tabs .nav-link.active { color: var(--bs-primary); background: rgba(var(--bs-primary-rgb), 0.15); }
.nia-upload-zone { transition: all 0.2s ease; border: 2px dashed rgba(255,255,255,0.12); background: rgba(0,0,0,0.15); }
.nia-upload-zone.nia-upload-dragover { border-color: rgba(var(--bs-primary-rgb), 0.5) !important; background: rgba(var(--bs-primary-rgb), 0.08) !important; }
.alert-share { display: flex; align-items: flex-start; gap: 0.75rem; padding: 1rem 1.25rem; border-radius: 0.75rem; margin-bottom: 1.25rem; }
.alert-share .material-icons { font-size: 1.35rem; flex-shrink: 0; margin-top: 0.1rem; }
/* Share article: Quill editor dark theme + responsive */
.nia-article-panel .card-body { min-width: 0; }
.nia-quill-wrapper { border-color: rgba(255,255,255,0.12) !important; }
.nia-article-editor-wrap { min-height: 220px; background: rgba(0,0,0,0.35); display: flex; flex-direction: column; }
.nia-article-editor-wrap .ql-container { border: none; font-size: 1rem; flex: 1; min-height: 0; }
.nia-article-editor-wrap .ql-editor { min-height: 200px; color: #e8e8e8; padding: 1rem 1.25rem; }
.nia-article-editor-wrap .ql-editor.ql-blank::before { color: rgba(255,255,255,0.4); font-style: normal; }
#nia-article-editor { min-height: 220px; border: none; background: transparent; }
#nia-article-editor.ql-container { font-family: inherit; }
/* Toolbar: above editor, dark and visible */
.nia-editor-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.nia-editor-toolbar .ql-formats { margin-right: 0.5rem; margin-bottom: 0; }
.nia-editor-toolbar .ql-toolbar.ql-snow { border: none; background: transparent; padding: 0; }
.nia-editor-toolbar .ql-snow .ql-stroke { stroke: rgba(255,255,255,0.35); }
.nia-editor-toolbar .ql-snow .ql-fill { fill: rgba(255,255,255,0.5); }
.nia-editor-toolbar .ql-snow .ql-picker { color: rgba(255,255,255,0.85); }
.nia-editor-toolbar .ql-snow button:hover .ql-stroke { stroke: rgba(255,255,255,0.8); }
.nia-editor-toolbar .ql-snow button:hover .ql-fill { fill: rgba(255,255,255,0.9); }
.nia-editor-toolbar .ql-snow .ql-picker-label { color: inherit; }
.nia-editor-toolbar .nia-editor-img-upload,
.nia-editor-toolbar .nia-editor-img-url { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 0.375rem; padding: 0.35rem 0.5rem; cursor: pointer; vertical-align: middle; }
.nia-editor-toolbar .nia-editor-img-upload:hover,
.nia-editor-toolbar .nia-editor-img-url:hover { background: rgba(255,255,255,0.15); }
@media (min-width: 576px) {
    .nia-article-editor-wrap { min-height: 280px; }
    .nia-article-editor-wrap .ql-editor { min-height: 240px; }
    #nia-article-editor { min-height: 280px; }
}
@media (min-width: 992px) {
    .nia-article-editor-wrap { min-height: 320px; }
    .nia-article-editor-wrap .ql-editor { min-height: 280px; }
    #nia-article-editor { min-height: 320px; }
}
@media (max-width: 575.98px) {
    .nia-editor-toolbar .ql-formats button { min-width: 38px; min-height: 38px; }
    .nia-editor-toolbar .nia-editor-img-upload,
    .nia-editor-toolbar .nia-editor-img-url { padding: 0.4rem; }
}
</style>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<main class="nia-main container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
    <?php if ($error) { ?><div class="alert alert-danger alert-share d-flex align-items-start gap-2"><span class="material-icons">error_outline</span><span><?php echo _e($error); ?></span></div><?php } ?>
    <?php if ($success) { ?><div class="alert alert-success alert-share d-flex align-items-start gap-2"><span class="material-icons">check_circle_outline</span><span><?php echo _e($success); ?><?php if (!empty($article_view_url)) { ?> <a href="<?php echo _e($article_view_url); ?>" class="alert-link">View article</a><?php } ?></span></div><?php } ?>
    <?php if (!empty($music_video_skipped) && $music_video_skipped > 0 && $share_page === 'share-music' && $success !== '') { ?><div class="alert alert-warning alert-share d-flex align-items-start gap-2"><span class="material-icons">info</span><span><strong><?php echo (int) $music_video_skipped; ?> video URL(s)</strong> were not added. Only audio links can be added for music (SoundCloud, direct MP3, Google Drive audio).</span></div><?php } ?>

    <div class="nia-share-nav mb-4">
        <a class="<?php echo $share_page === 'share-video' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=share-video')); ?>"><span class="material-icons">link</span> Share video</a>
        <a class="<?php echo $share_page === 'upload-video' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=upload-video')); ?>"><span class="material-icons">upload_file</span> Upload video</a>
        <a class="<?php echo $share_page === 'share-music' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=share-music')); ?>"><span class="material-icons">music_note</span> Share music</a>
        <a class="<?php echo $share_page === 'upload-music' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=upload-music')); ?>"><span class="material-icons">upload_file</span> Upload music</a>
        <a class="<?php echo $share_page === 'share-picture' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=share-picture')); ?>"><span class="material-icons">image</span> Share picture</a>
        <a class="<?php echo $share_page === 'upload-picture' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=upload-picture')); ?>"><span class="material-icons">upload_file</span> Upload picture</a>
        <a class="<?php echo $share_page === 'share-article' ? 'active' : ''; ?>" href="<?php echo _e(url('share?page=share-article')); ?>"><span class="material-icons">article</span> Share article</a>
    </div>

    <!-- Share video (YouTube importer) -->
    <div class="nia-share-panel <?php echo $share_page !== 'share-video' ? 'd-none' : ''; ?>" data-page="share-video">
    <?php if ($api_key === '') { ?>
    <div class="alert alert-warning alert-share mb-4"><span class="material-icons">info</span><span>For <strong>Keywords</strong>, <strong>Playlist</strong>, and <strong>Channel</strong> import, set a YouTube Data API key in <a href="<?php echo url(defined('ADMINCP') ? ADMINCP : 'moderator'); ?>/youtube" class="alert-link">Admin → YouTube</a>. Video link import works without it.</span></div>
    <?php } ?>
    <div class="nia-share-tabs">
        <a class="nav-link <?php echo $importer_section === 'video' ? 'active' : ''; ?>" href="#" data-section="video">Video link</a>
        <a class="nav-link <?php echo $importer_section === 'gdrive' ? 'active' : ''; ?>" href="#" data-section="gdrive">Google Drive</a>
        <a class="nav-link <?php echo $importer_section === 'mp4url' ? 'active' : ''; ?>" href="#" data-section="mp4url">MP4 URL</a>
        <a class="nav-link <?php echo $importer_section === 'keywords' ? 'active' : ''; ?>" href="#" data-section="keywords">Keywords</a>
        <a class="nav-link <?php echo $importer_section === 'playlist' ? 'active' : ''; ?>" href="#" data-section="playlist">Playlist</a>
        <a class="nav-link <?php echo $importer_section === 'channel' ? 'active' : ''; ?>" href="#" data-section="channel">Channel</a>
    </div>

    <!-- Video link -->
    <div class="nia-importer-panel <?php echo $importer_section === 'video' ? '' : 'd-none'; ?>" data-panel="video">
        <div class="nia-share-panel-card">
            <p class="text-muted small mb-4">Paste a video URL from YouTube, Vimeo, Dailymotion, or social media.</p>
            <form method="post" action="">
                <input type="hidden" name="importer_section" value="video">
                <input type="hidden" name="action" value="preview">
                <div class="mb-3">
                    <label for="share-url" class="form-label fw-bold">Video URL</label>
                    <div class="input-group">
                        <span class="input-group-text bg-black border-secondary"><span class="material-icons text-primary" style="font-size:1.2rem;">link</span></span>
                        <input type="url" class="form-control bg-dark border-secondary text-light" id="share-url" name="url" placeholder="https://www.youtube.com/watch?v=..." value="<?php echo _e(isset($_POST['url']) ? $_POST['url'] : ''); ?>">
                        <button type="submit" class="btn btn-primary px-4">Fetch</button>
                    </div>
                </div>
            </form>
            <?php if ($preview && $importer_section === 'video') {
                $meta = $preview['meta'] ?? [];
                $v_title = $meta['title'] ?? (NiaProviders::getProviderName($preview['source']) . ' video');
                $v_desc = $meta['description'] ?? '';
                $thumb = $meta['thumbnail_url'] ?? null;
            ?>
            <div class="card border-secondary bg-black bg-opacity-25 mt-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <?php if ($thumb) { ?><img src="<?php echo _e($thumb); ?>" alt="" class="img-fluid rounded shadow-sm"><?php } ?>
                        </div>
                        <div class="col-md-8">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="url" value="<?php echo _e($_POST['url'] ?? ''); ?>">
                                <div class="mb-3">
                                    <label class="form-label small">Edit Title</label>
                                    <input type="text" name="title" class="form-control form-control-sm bg-dark border-secondary text-light" value="<?php echo _e($v_title); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small">Description</label>
                                    <textarea name="description" class="form-control form-control-sm bg-dark border-secondary text-light" rows="2"><?php echo _e($v_desc); ?></textarea>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small">Channel</label>
                                        <select name="category_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                                            <option value="0">- No Channel -</option>
                                            <?php foreach ($video_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Type</label>
                                        <select name="type" class="form-select form-select-sm bg-dark border-secondary text-light">
                                            <option value="video">Video</option>
                                            <option value="music">Music</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="private" value="1" id="pv-v">
                                        <label class="form-check-label small" for="pv-v">Private</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="nsfw" value="1" id="ns-v">
                                        <label class="form-check-label small" for="ns-v">NSFW</label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm w-100">Confirm & Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- Google Drive video -->
    <div class="nia-importer-panel <?php echo $importer_section === 'gdrive' ? '' : 'd-none'; ?>" data-panel="gdrive">
        <div class="nia-share-panel-card">
            <p class="text-muted small mb-4">Paste Google Drive video links. Supports single files or mass importing as a playlist.</p>
            <form method="post" class="mb-0">
                <input type="hidden" name="importer_section" value="gdrive">
                <div class="mb-3">
                    <label class="form-label fw-bold">Drive Link / List</label>
                    <textarea class="form-control bg-dark border-secondary text-light" name="url_lines" rows="4" placeholder="Paste links here, one per line..."><?php echo _e(isset($_POST['url_lines']) ? $_POST['url_lines'] : ($_POST['url'] ?? '')); ?></textarea>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Assign to Channel</label>
                        <select name="category_id" class="form-select bg-dark border-secondary text-light">
                            <option value="0">- No Channel -</option>
                            <?php foreach ($video_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Privacy</label>
                        <div class="d-flex gap-3 pt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="private" value="1" id="pv-g">
                                <label class="form-check-label small" for="pv-g">Private</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="nsfw" value="1" id="ns-g">
                                <label class="form-check-label small" for="ns-g">NSFW</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="add_bulk" class="btn btn-primary px-4 d-flex align-items-center gap-2">
                        <span class="material-icons">playlist_add</span> Import Selected
                    </button>
                    <button type="submit" name="action" value="preview" class="btn btn-outline-secondary">Preview First</button>
                </div>
            </form>
            
            <?php if ($preview && $importer_section === 'gdrive') {
                $meta = $preview['meta'] ?? [];
                $v_title = $meta['title'] ?? (NiaProviders::getProviderName($preview['source']) . ' video');
                $thumb = $meta['thumbnail_url'] ?? null;
            ?>
            <div class="card border-secondary bg-black bg-opacity-25 mt-4">
                <div class="card-body d-flex align-items-center gap-3">
                    <?php if ($thumb) { ?><img src="<?php echo _e($thumb); ?>" alt="" class="rounded" width="100"><?php } ?>
                    <div>
                        <h6 class="mb-1"><?php echo _e($v_title); ?></h6>
                        <span class="badge bg-primary">Google Drive</span>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <!-- MP4 video URL and playlist -->
    <div class="nia-importer-panel <?php echo $importer_section === 'mp4url' ? '' : 'd-none'; ?>" data-panel="mp4url">
        <div class="nia-share-panel-card">
        <p class="text-muted small mb-4">Paste a direct MP4 (or WebM/M4V) video URL, or multiple URLs (one per line) to add as a playlist.</p>
        <form method="post" class="mb-4">
            <input type="hidden" name="importer_section" value="mp4url">
            <div class="mb-2">
                <label class="form-label">MP4 / video URL</label>
                <input type="url" class="form-control bg-dark border-secondary text-light" name="url" placeholder="https://example.com/video.mp4" value="<?php echo _e(isset($_POST['url']) ? $_POST['url'] : ''); ?>">
            </div>
            <div class="mb-2">
                <label class="form-label">Or paste multiple video URLs (playlist), one per line</label>
                <textarea class="form-control bg-dark border-secondary text-light" name="url_lines" rows="4" placeholder="https://.../a.mp4&#10;https://.../b.mp4"><?php echo _e(isset($_POST['url_lines']) ? $_POST['url_lines'] : ''); ?></textarea>
            </div>
            <input type="hidden" name="type" value="video">
            <button type="submit" name="action" value="preview" class="btn btn-outline-secondary me-2">Preview one</button>
            <button type="submit" name="action" value="add_bulk" class="btn btn-primary">Add all (playlist)</button>
        </form>
        </div>
    </div>

    <!-- Keywords: only visible when Keywords tab is active -->
    <div class="nia-importer-panel <?php echo $importer_section === 'keywords' ? '' : 'd-none'; ?>" data-panel="keywords">
        <div class="nia-share-panel-card">
            <p class="text-muted small mb-4">Search YouTube and import videos directly. Select multiple items to mass-import.</p>
            <form method="post" class="mb-4">
                <input type="hidden" name="importer_section" value="keywords">
                <input type="hidden" name="action" value="youtube_keywords">
                <div class="input-group">
                    <span class="input-group-text bg-black border-secondary"><span class="material-icons text-primary" style="font-size:1.2rem;">search</span></span>
                    <input type="text" class="form-control bg-dark border-secondary text-light" name="keyword" placeholder="e.g. tutorial php" value="<?php echo _e(isset($_POST['keyword']) ? $_POST['keyword'] : ''); ?>">
                    <button type="submit" class="btn btn-primary px-4" <?php echo $api_key === '' ? 'disabled' : ''; ?>>Search</button>
                </div>
            </form>
            
            <?php if (!empty($keyword_results)) { ?>
            <form method="post" class="mb-0">
                <input type="hidden" name="importer_section" value="keywords">
                <input type="hidden" name="action" value="add_bulk">
                <input type="hidden" name="type" value="video">
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Assign Channel</label>
                        <select name="category_id" class="form-select bg-dark border-secondary text-light">
                            <option value="0">- No Channel -</option>
                            <?php foreach ($video_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                        </select>
                    </div>
                     <div class="col-md-6 pt-md-4">
                        <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                           <span class="material-icons">add_box</span> Import Selected
                        </button>
                    </div>
                </div>

                <div class="row g-2">
                    <?php foreach ($keyword_results as $i => $v) { ?>
                    <div class="col-6 col-md-4">
                        <div class="card bg-black bg-opacity-25 border-secondary h-100 overflow-hidden">
                            <label class="m-0 cursor-pointer h-100 d-flex flex-column">
                                <div class="position-relative">
                                    <?php if ($v['thumb']) { ?><img src="<?php echo _e($v['thumb']); ?>" alt="" class="card-img-top"><?php } ?>
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <input type="checkbox" name="urls[]" value="<?php echo _e($v['url']); ?>" class="form-check-input">
                                    </div>
                                </div>
                                <div class="p-2 flex-grow-1">
                                    <div class="small fw-bold text-truncate-2" style="font-size: 0.75rem; line-height: 1.2;"><?php echo _e($v['title']); ?></div>
                                </div>
                            </label>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </form>
            <?php } ?>
        </div>
    </div>

    <!-- Playlist -->
    <div class="nia-importer-panel <?php echo $importer_section === 'playlist' ? '' : 'd-none'; ?>" data-panel="playlist">
        <div class="nia-share-panel-card">
        <p class="text-muted small mb-4">Paste a YouTube playlist URL. Choose to <strong>select videos</strong> (pick which to add) or <strong>add all</strong> videos in the playlist.</p>
        <form method="post" class="mb-4">
            <input type="hidden" name="importer_section" value="playlist">
            <input type="hidden" name="action" value="youtube_playlist">
            <div class="mb-3">
                <label class="form-label">Playlist URL</label>
                <input type="url" class="form-control bg-dark border-secondary text-light" name="playlist_url" placeholder="https://www.youtube.com/playlist?list=PL..." value="<?php echo _e(isset($_POST['playlist_url']) ? $_POST['playlist_url'] : ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">When loading playlist</label>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="playlist_import_mode" id="playlist_mode_select" value="select" <?php echo (isset($_POST['playlist_import_mode']) ? $_POST['playlist_import_mode'] : 'select') === 'select' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="playlist_mode_select">Select videos (choose which to add)</label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" name="playlist_import_mode" id="playlist_mode_add_all" value="add_all" <?php echo (isset($_POST['playlist_import_mode']) && $_POST['playlist_import_mode'] === 'add_all') ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="playlist_mode_add_all">Add all videos in this playlist</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" <?php echo $api_key === '' ? 'disabled' : ''; ?>>Load playlist</button>
        </form>
        <?php if (!empty($playlist_results)) { ?>
        <form method="post" class="mb-3">
            <input type="hidden" name="importer_section" value="playlist">
            <input type="hidden" name="action" value="add_bulk">
            <input type="hidden" name="type" value="video">
            <button type="submit" class="btn btn-success btn-sm mb-2">Add selected</button>
        <ul class="list-unstyled">
            <?php foreach ($playlist_results as $v) { ?>
            <li class="d-flex gap-2 align-items-center mb-3 p-2 rounded bg-dark">
                <label class="d-flex align-items-center gap-2 flex-grow-1 min-width-0 mb-0">
                    <input type="checkbox" name="urls[]" value="<?php echo _e($v['url']); ?>" class="form-check-input flex-shrink-0">
                    <?php if ($v['thumb']) { ?><img src="<?php echo _e($v['thumb']); ?>" alt="" width="120" height="68" class="rounded flex-shrink-0"><?php } ?>
                    <div class="text-truncate"><?php echo _e($v['title']); ?></div>
                </label>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="url" value="<?php echo _e($v['url']); ?>">
                    <input type="hidden" name="type" value="video">
                    <button type="submit" class="btn btn-sm btn-outline-primary">Add</button>
                </form>
            </li>
            <?php } ?>
        </ul>
        </form>
        <?php } ?>
        </div>
    </div>

    <!-- Channel -->
    <div class="nia-importer-panel <?php echo $importer_section === 'channel' ? '' : 'd-none'; ?>" data-panel="channel">
        <div class="nia-share-panel-card">
        <p class="text-muted small mb-4">Add a YouTube channel. Enable <strong>Auto-import</strong> to automatically import new videos when the channel uploads (run via cron or Admin).</p>
        <form method="post" class="mb-4">
            <input type="hidden" name="importer_section" value="channel">
            <input type="hidden" name="action" value="youtube_channel">
            <div class="mb-2">
                <label class="form-label">Channel URL</label>
                <input type="url" class="form-control form-control-sm bg-dark border-secondary text-light" name="channel_url" placeholder="https://www.youtube.com/channel/UC... or https://www.youtube.com/@username" style="max-height: 2.25rem;">
            </div>
            <div class="mb-2 form-check">
                <input type="checkbox" class="form-check-input" name="auto_import" id="auto_import" value="1" checked>
                <label class="form-check-label" for="auto_import">Auto-import new videos when channel adds them on YouTube</label>
            </div>
            <button type="submit" class="btn btn-primary">Add channel</button>
        </form>
        </div>
    </div>

    <p class="small text-muted mt-4 mb-0">Manage YouTube channels and import logs in <a href="<?php echo url('me/auto-import'); ?>">Library → Auto-import</a>.</p>
    </div><!-- .nia-share-panel share-video -->

    <!-- Upload video -->
    <div class="nia-share-panel nia-upload-panel <?php echo $share_page !== 'upload-video' ? 'd-none' : ''; ?>" data-page="upload-video">
        <div class="nia-share-panel-card">
            <div class="nia-upload-zone rounded-3 p-5 text-center mb-0" id="upload-video-zone" data-upload-url="<?php echo _e($upload_video_url); ?>" data-accept="video/mp4,video/webm,video/quicktime,video/x-msvideo">
                <div class="mb-3">
                    <span class="material-icons text-primary" style="font-size: 5rem; opacity: 0.5;">cloud_upload</span>
                </div>
                <h4 class="fw-bold mb-2">Upload Video Files</h4>
                <p class="text-muted small mb-4">MP4, WebM, or MOV formats supported. Max size depends on server limits.</p>
                <input type="file" id="upload-video-input" class="d-none" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.avi">
                <button type="button" class="btn btn-primary btn-lg px-5 rounded-pill" id="upload-video-btn">
                   <span class="material-icons align-middle me-1">file_open</span> Select File
                </button>
                <p class="mt-3 text-muted small">or drag and drop here</p>
            </div>
            <div id="upload-video-result" class="mt-3 d-none"></div>
        </div>
    </div>

    <!-- Upload music -->
    <div class="nia-share-panel nia-upload-panel <?php echo $share_page !== 'upload-music' ? 'd-none' : ''; ?>" data-page="upload-music">
        <div class="nia-share-panel-card">
        <div class="nia-upload-zone rounded-3 p-5 text-center" id="upload-music-zone" data-upload-url="<?php echo _e($upload_music_url); ?>" data-accept="audio/mpeg,audio/mp3,audio/mp4,audio/x-m4a,audio/ogg,audio/wav">
            <div class="nia-upload-icon mb-3 text-secondary" style="font-size: 4rem;">☁ ↑</div>
            <p class="text-muted mb-2">Drag and drop a music file here or</p>
            <input type="file" id="upload-music-input" class="d-none" accept=".mp3,.m4a,.ogg,.wav,audio/*">
            <button type="button" class="btn btn-primary" id="upload-music-btn">Choose music</button>
        </div>
        <div id="upload-music-result" class="mt-3 d-none"></div>
        </div>
    </div>

    <!-- Upload picture -->
    <div class="nia-share-panel nia-upload-panel <?php echo $share_page !== 'upload-picture' ? 'd-none' : ''; ?>" data-page="upload-picture">
        <div class="nia-share-panel-card">
            <div class="nia-upload-zone rounded-3 p-5 text-center mb-0" id="upload-picture-zone" data-upload-url="<?php echo _e($upload_image_url); ?>" data-accept="image/jpeg,image/png,image/gif,image/webp">
                <div class="mb-3">
                    <span class="material-icons text-success" style="font-size: 5rem; opacity: 0.5;">add_photo_alternate</span>
                </div>
                <h4 class="fw-bold mb-2">Upload Pictures</h4>
                <p class="text-muted small mb-4">JPG, PNG, GIF, or WebP formats supported.</p>
                <input type="file" id="upload-picture-input" class="d-none" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp">
                <button type="button" class="btn btn-success btn-lg px-5 rounded-pill" id="upload-picture-btn">
                   <span class="material-icons align-middle me-1">image_search</span> Select Image
                </button>
            </div>
            <div id="upload-picture-result" class="mt-3 d-none"></div>
        </div>
    </div>

    <!-- Share music -->
    <div class="nia-share-panel <?php echo $share_page !== 'share-music' ? 'd-none' : ''; ?>" data-page="share-music">
        <div class="nia-share-panel-card">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="material-icons text-primary">music_note</span>
                <h2 class="h5 mb-0 fw-bold">Share music</h2>
            </div>
            <p class="text-muted small mb-4">Add music from audio links only: SoundCloud, Google Drive audio, or direct MP3/audio URLs. Video URLs are not accepted.</p>
            <div class="nia-share-tabs mb-4">
                <a class="nav-link active" href="#" data-music-section="url"><span class="material-icons align-middle me-1" style="font-size:1rem;">link</span> URL / Social</a>
                <a class="nav-link" href="#" data-music-section="gdrive"><span class="material-icons align-middle me-1" style="font-size:1rem;">folder</span> Google Drive</a>
                <a class="nav-link" href="#" data-music-section="mp3url"><span class="material-icons align-middle me-1" style="font-size:1rem;">audiotrack</span> Direct MP3</a>
            </div>

            <!-- URL / Social (YouTube, SoundCloud, etc.) -->
            <div class="nia-music-panel" data-music-panel="url">
                <form method="post" action="<?php echo _e(url('share?page=share-music')); ?>">
                    <input type="hidden" name="type" value="music">
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">link</span> Music links
                        </label>
                        <textarea class="form-control bg-dark border-secondary text-light" name="url_lines" rows="4" placeholder="https://soundcloud.com/...&#10;https://drive.google.com/... (audio file)&#10;https://example.com/track.mp3"></textarea>
                        <div class="form-text small">Only audio links: SoundCloud, Google Drive audio, or direct MP3/audio URLs. Video URLs (e.g. YouTube) are not accepted for music.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Music channel</label>
                            <select name="category_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                                <option value="0">— No channel —</option>
                                <?php foreach ($music_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" name="action" value="add_bulk" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <span class="material-icons" style="font-size:1.2rem;">add_circle</span> Add music
                            </button>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="private" value="1" id="music-private-url">
                            <label class="form-check-label small" for="music-private-url">Private</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="nsfw" value="1" id="music-nsfw-url">
                            <label class="form-check-label small" for="music-nsfw-url">NSFW</label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Google Drive -->
            <div class="nia-music-panel d-none" data-music-panel="gdrive">
                <form method="post" action="<?php echo _e(url('share?page=share-music')); ?>">
                    <input type="hidden" name="type" value="music">
                    <input type="hidden" name="action" value="add_bulk">
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">folder</span> Google Drive links
                        </label>
                        <textarea class="form-control bg-dark border-secondary text-light" name="url_lines" rows="4" placeholder="Paste Google Drive audio file links here, one per line..."></textarea>
                        <div class="form-text small">Shareable links to MP3 or other audio files on Google Drive.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Music channel</label>
                            <select name="category_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                                <option value="0">— No channel —</option>
                                <?php foreach ($music_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <span class="material-icons" style="font-size:1.2rem;">playlist_add</span> Import
                            </button>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="private" value="1" id="music-private-gdrive">
                            <label class="form-check-label small" for="music-private-gdrive">Private</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="nsfw" value="1" id="music-nsfw-gdrive">
                            <label class="form-check-label small" for="music-nsfw-gdrive">NSFW</label>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Direct MP3 -->
            <div class="nia-music-panel d-none" data-music-panel="mp3url">
                <form method="post" action="<?php echo _e(url('share?page=share-music')); ?>">
                    <input type="hidden" name="type" value="music">
                    <input type="hidden" name="action" value="add_bulk">
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            <span class="material-icons" style="font-size:1.1rem;">audiotrack</span> Direct MP3 / audio URL
                        </label>
                        <input type="url" class="form-control bg-dark border-secondary text-light mb-2" name="url" placeholder="https://example.com/track.mp3" value="">
                        <label class="form-label small text-muted">Or paste multiple URLs (one per line)</label>
                        <textarea class="form-control bg-dark border-secondary text-light" name="url_lines" rows="4" placeholder="https://.../a.mp3&#10;https://.../b.mp3"></textarea>
                        <div class="form-text small">Direct links to MP3, M4A, OGG, or other streamable audio files.</div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Music channel</label>
                            <select name="category_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                                <option value="0">— No channel —</option>
                                <?php foreach ($music_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2">
                                <span class="material-icons" style="font-size:1.2rem;">add_circle</span> Add music
                            </button>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="private" value="1" id="music-private-mp3">
                            <label class="form-check-label small" for="music-private-mp3">Private</label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="nsfw" value="1" id="music-nsfw-mp3">
                            <label class="form-check-label small" for="music-nsfw-mp3">NSFW</label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Share picture -->
    <div class="nia-share-panel <?php echo $share_page !== 'share-picture' ? 'd-none' : ''; ?>" data-page="share-picture">
        <div class="nia-share-panel-card">
            <p class="text-muted small mb-4">Paste image URLs from Google Drive, Imgur, or direct image links.</p>
            <form method="post" action="<?php echo _e(url('share?page=share-picture')); ?>">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Picture Links</label>
                    <textarea class="form-control bg-dark border-secondary text-light" name="url_lines" rows="4" placeholder="https://imgur.com/gallery/...&#10;https://example.com/image.jpg"></textarea>
                </div>
                <div class="row g-3 mb-0">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Image Album / Channel</label>
                        <select name="album_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                            <option value="0">- No Album -</option>
                            <?php foreach ($image_channels as $c) { echo '<option value="'.$c->id.'">'._e($c->name).'</option>'; } ?>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" name="action" value="add_bulk_images" class="btn btn-success w-100">Import Pictures</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Share blog -->
    <div class="nia-share-panel <?php echo $share_page !== 'share-article' ? 'd-none' : ''; ?>" data-page="share-article">
        <div class="nia-share-panel-card nia-article-panel">
            <div class="d-flex align-items-center gap-2 mb-4">
                <span class="material-icons text-primary">article</span>
                <h2 class="h5 mb-0 fw-bold">Write a blog post</h2>
            </div>
            <form method="post" id="nia-share-article-form" class="nia-article-form">
                <input type="hidden" name="action" value="share_article">
                <input type="hidden" name="article_content" id="nia-article-content-input">
                <input type="hidden" name="article_id" id="nia-article-id" value="">

                <div class="row g-3 g-lg-4">
                    <!-- Main content: title, slug, editor (stacks first on mobile) -->
                    <div class="col-12 col-lg-8 order-1">
                        <div class="card border-secondary bg-black bg-opacity-25 mb-3 mb-lg-0">
                            <div class="card-body p-3 p-md-4">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1.1rem;">title</span> Title
                                    </label>
                                    <input type="text" class="form-control form-control-lg bg-dark border-secondary text-light" name="article_title" id="nia-article-title" placeholder="Enter blog title..." required value="<?php echo _e(isset($_POST['article_title']) ? $_POST['article_title'] : ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1.1rem;">link</span> URL slug
                                    </label>
                                    <input type="text" class="form-control bg-dark border-secondary text-light" name="article_slug" id="nia-article-slug" placeholder="auto-generated-from-title" value="<?php echo _e(isset($_POST['article_slug']) ? $_POST['article_slug'] : ''); ?>">
                                    <div class="form-text small">Leave blank to generate from title. Use lowercase letters, numbers, and hyphens.</div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-semibold d-flex align-items-center gap-1 mb-2">
                                        <span class="material-icons" style="font-size:1.1rem;">edit_note</span> Content
                                    </label>
                                    <div class="nia-quill-wrapper border border-secondary rounded overflow-hidden">
                                    <div id="nia-article-toolbar" class="nia-editor-toolbar border-secondary border-bottom bg-black bg-opacity-50 px-2 py-2">
                                        <span class="ql-formats">
                                            <select class="ql-header"><option selected></option><option value="2"></option><option value="3"></option></select>
                                            <button class="ql-bold"></button><button class="ql-italic"></button><button class="ql-underline"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button class="ql-list" value="ordered"></button><button class="ql-list" value="bullet"></button>
                                            <button class="ql-blockquote"></button><button class="ql-link"></button>
                                        </span>
                                        <span class="ql-formats">
                                            <button type="button" class="nia-editor-img-upload" title="Upload image"><span class="material-icons" style="font-size:1.1rem;">image</span></button>
                                            <button type="button" class="nia-editor-img-url" title="Image URL"><span class="material-icons" style="font-size:1.1rem;">link</span></button>
                                        </span>
                                    </div>
                                    <div class="nia-article-editor-wrap border-0 overflow-hidden" data-upload-image-url="<?php echo _e($upload_image_url); ?>">
                                        <div id="nia-article-editor"></div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sidebar: excerpt, category, meta, featured image, status, actions (stacks second on mobile) -->
                    <div class="col-12 col-lg-4 order-2">
                        <div class="card border-secondary bg-black bg-opacity-25 mb-3">
                            <div class="card-header py-2 px-3 d-flex align-items-center gap-1">
                                <span class="material-icons" style="font-size:1.1rem;">settings</span>
                                <span class="small fw-semibold text-uppercase opacity-75">Publish</span>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">short_text</span> Excerpt
                                    </label>
                                    <textarea class="form-control form-control-sm bg-dark border-secondary text-light" name="article_excerpt" id="nia-article-excerpt" rows="3" placeholder="Brief summary for listings..."><?php echo _e(isset($_POST['article_excerpt']) ? $_POST['article_excerpt'] : ''); ?></textarea>
                                    <div class="form-text small"><span id="nia-excerpt-count">0</span> characters</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">folder</span> Category
                                    </label>
                                    <select name="article_category_id" class="form-select form-select-sm bg-dark border-secondary text-light">
                                        <option value="0">— No category —</option>
                                        <?php foreach ($blog_cats as $bc) { ?>
                                        <option value="<?php echo (int) $bc->id; ?>" <?php echo (isset($_POST['article_category_id']) && (int)$_POST['article_category_id'] === (int)$bc->id) ? 'selected' : ''; ?>><?php echo _e($bc->name); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">description</span> Meta description
                                    </label>
                                    <textarea class="form-control form-control-sm bg-dark border-secondary text-light" name="article_meta_description" id="nia-article-meta-desc" rows="2" placeholder="Optional SEO description"><?php echo _e(isset($_POST['article_meta_description']) ? $_POST['article_meta_description'] : ''); ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">label</span> Tags
                                    </label>
                                    <input type="text" class="form-control form-control-sm bg-dark border-secondary text-light" name="article_tags" placeholder="tag1, tag2, tag3" value="<?php echo _e(isset($_POST['article_tags']) ? $_POST['article_tags'] : ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">image</span> Featured image URL
                                    </label>
                                    <div class="input-group input-group-sm">
                                        <input type="url" class="form-control bg-dark border-secondary text-light" name="article_featured_image" id="nia-article-featured-url" placeholder="https://...">
                                        <button class="btn btn-outline-secondary nia-article-featured-upload-btn" type="button" title="Upload"><span class="material-icons" style="font-size:1rem;">upload</span></button>
                                    </div>
                                    <div id="nia-article-featured-preview" class="mt-2 text-center"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold d-flex align-items-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">visibility</span> Status
                                    </label>
                                    <select name="article_status" class="form-select form-select-sm bg-dark border-secondary text-light">
                                        <option value="publish" <?php echo (isset($_POST['article_status']) && $_POST['article_status'] === 'publish') ? 'selected' : ''; ?>>Publish now</option>
                                        <option value="draft" <?php echo (isset($_POST['article_status']) && $_POST['article_status'] === 'draft') ? 'selected' : ''; ?>>Save as draft</option>
                                    </select>
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                                        <span class="material-icons" style="font-size:1.2rem;">publish</span> Publish / Save
                                    </button>
                                    <a href="<?php echo _e(url('blog')); ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-1">
                                        <span class="material-icons" style="font-size:1rem;">list</span> View all articles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="file" id="nia-article-img-upload" class="d-none" accept="image/*">
                <input type="file" id="nia-article-featured-upload" class="d-none" accept="image/*">
            </form>
        </div>
    </div>
        </div><!-- col -->
    </div><!-- row -->
</main>

<script>window.niaArticleContent = <?php echo json_encode(isset($_POST['article_content']) ? $_POST['article_content'] : ''); ?>;</script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function(){
    var mainTabs = document.querySelectorAll('.nia-share-nav a');
    var panels = document.querySelectorAll('.nia-share-panel');
    function setPage(page) {
        mainTabs.forEach(function(t){ t.classList.toggle('active', (t.getAttribute('href') || '').indexOf('page=' + page) !== -1); });
        panels.forEach(function(p){ p.classList.toggle('d-none', p.getAttribute('data-page') !== page); });
    }
    mainTabs.forEach(function(tab){
        tab.addEventListener('click', function(e){
            var href = this.getAttribute('href');
            if (href && href.indexOf('page=') !== -1) {
                var m = href.match(/page=([^&]+)/);
                if (m) { e.preventDefault(); setPage(m[1]); history.replaceState(null, '', href); }
            }
        });
    });

    document.querySelectorAll('.nia-share-panel[data-page="share-video"] .nia-share-tabs [data-section]').forEach(function(tab){
        tab.addEventListener('click', function(e){
            e.preventDefault();
            var section = this.getAttribute('data-section');
            document.querySelectorAll('.nia-share-panel[data-page="share-video"] .nia-share-tabs .nav-link').forEach(function(l){ l.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.nia-importer-panel').forEach(function(p){
                p.classList.toggle('d-none', p.getAttribute('data-panel') !== section);
            });
        });
    });
    document.querySelectorAll('[data-music-section]').forEach(function(tab){
        tab.addEventListener('click', function(e){
            e.preventDefault();
            var section = this.getAttribute('data-music-section');
            document.querySelectorAll('[data-music-section]').forEach(function(l){ l.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.nia-music-panel').forEach(function(p){
                p.classList.toggle('d-none', p.getAttribute('data-music-panel') !== section);
            });
        });
    });
    document.querySelectorAll('[data-picture-section]').forEach(function(tab){
        tab.addEventListener('click', function(e){
            e.preventDefault();
            var section = this.getAttribute('data-picture-section');
            document.querySelectorAll('[data-picture-section]').forEach(function(l){ l.classList.remove('active'); });
            this.classList.add('active');
            document.querySelectorAll('.nia-picture-panel').forEach(function(p){
                p.classList.toggle('d-none', p.getAttribute('data-picture-panel') !== section);
            });
        });
    });

    function setupUpload(zoneId, inputId, btnId, resultId) {
        var zone = document.getElementById(zoneId);
        if (!zone) return;
        var input = document.getElementById(inputId);
        var btn = document.getElementById(btnId);
        var resultEl = document.getElementById(resultId);
        var url = zone.getAttribute('data-upload-url');
        if (!url) return;
        btn.addEventListener('click', function(){ input.click(); });
        input.addEventListener('change', function(){ if (this.files.length) doUpload(this.files[0], url, resultEl); });
        zone.addEventListener('dragover', function(e){ e.preventDefault(); zone.classList.add('nia-upload-dragover'); });
        zone.addEventListener('dragleave', function(){ zone.classList.remove('nia-upload-dragover'); });
        zone.addEventListener('drop', function(e){
            e.preventDefault();
            zone.classList.remove('nia-upload-dragover');
            if (e.dataTransfer.files.length) doUpload(e.dataTransfer.files[0], url, resultEl);
        });
    }
    function doUpload(file, url, resultEl) {
        resultEl.classList.remove('d-none');
        resultEl.innerHTML = '<span class="text-muted small">Uploading…</span>';
        var fd = new FormData();
        fd.append('file', file);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.onload = function() {
            var data = {};
            try { data = JSON.parse(xhr.responseText); } catch (e) {}
            if (data.ok && data.url) {
                resultEl.innerHTML = '<div class="alert alert-success mt-2 py-2 small">Uploaded. <a href="' + data.url + '" class="fw-bold">View</a></div>';
            } else {
                resultEl.innerHTML = '<div class="alert alert-danger mt-2 py-2 small">' + (data.error || 'Upload failed') + '</div>';
            }
        };
        xhr.onerror = function() { resultEl.innerHTML = '<div class="alert alert-danger mt-2 py-2 small">Upload failed</div>'; };
        xhr.send(fd);
    }
    setupUpload('upload-video-zone', 'upload-video-input', 'upload-video-btn', 'upload-video-result');
    setupUpload('upload-music-zone', 'upload-music-input', 'upload-music-btn', 'upload-music-result');
    setupUpload('upload-picture-zone', 'upload-picture-input', 'upload-picture-btn', 'upload-picture-result');

    // Rich text editor (Share article)
    var editorEl = document.getElementById('nia-article-editor');
    var articleForm = document.getElementById('nia-share-article-form');
    if (editorEl && typeof Quill !== 'undefined') {
        var quill = new Quill(editorEl, {
            theme: 'snow',
            modules: {
                toolbar: '#nia-article-toolbar',
                clipboard: { matchVisual: false }
            }
        });
        if (window.niaArticleContent) quill.root.innerHTML = window.niaArticleContent;
        if (articleForm) {
            articleForm.addEventListener('submit', function() {
                var contentInput = document.getElementById('nia-article-content-input');
                if (contentInput) contentInput.value = quill.root.innerHTML;
            });
        }
        var wrap = document.querySelector('.nia-article-editor-wrap');
        var uploadUrl = wrap ? wrap.getAttribute('data-upload-image-url') : '';
        var imgInput = document.getElementById('nia-article-img-upload');
        document.querySelectorAll('.nia-editor-img-upload').forEach(function(btn) {
            btn.addEventListener('click', function() {
                if (imgInput) imgInput.click();
            });
        });
        if (imgInput && uploadUrl) {
            imgInput.addEventListener('change', function() {
                if (!this.files || !this.files[0]) return;
                var fd = new FormData();
                fd.append('file', this.files[0]);
                var xhr = new XMLHttpRequest();
                xhr.open('POST', uploadUrl);
                xhr.onload = function() {
                    var data = {};
                    try { data = JSON.parse(xhr.responseText); } catch (e) {}
                    var imgUrl = data.image_src || data.url;
                    if (data.ok && imgUrl) {
                        var range = quill.getSelection(true) || { index: quill.getLength() };
                        quill.insertEmbed(range.index, 'image', imgUrl, 'user');
                        quill.setSelection(range.index + 1);
                    }
                };
                xhr.send(fd);
                this.value = '';
            });
        }
        document.querySelectorAll('.nia-editor-img-url').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var url = prompt('Enter image URL:');
                if (url && url.trim()) {
                    var range = quill.getSelection(true) || { index: quill.getLength() };
                    quill.insertEmbed(range.index, 'image', url.trim(), 'user');
                    quill.setSelection(range.index + 1);
                }
            });
        });
    }

    // Blog form: excerpt character count + slug from title
    var excerptEl = document.getElementById('nia-article-excerpt');
    var excerptCountEl = document.getElementById('nia-excerpt-count');
    if (excerptEl && excerptCountEl) {
        function updateExcerptCount() { excerptCountEl.textContent = (excerptEl.value || '').length; }
        excerptEl.addEventListener('input', updateExcerptCount);
        excerptEl.addEventListener('change', updateExcerptCount);
        updateExcerptCount();
    }
    var titleEl = document.getElementById('nia-article-title');
    var slugEl = document.getElementById('nia-article-slug');
    if (titleEl && slugEl) {
        var slugTouched = false;
        slugEl.addEventListener('input', function() { slugTouched = slugEl.value.length > 0; });
        titleEl.addEventListener('input', function() {
            if (!slugTouched) {
                var s = titleEl.value.replace(/[^a-zA-Z0-9\s\-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-').toLowerCase().replace(/^-|-$/g, '');
                slugEl.placeholder = s || 'auto-generated-from-title';
                slugEl.setAttribute('data-suggest', s);
            }
        });
        titleEl.addEventListener('blur', function() {
            if (!slugTouched && slugEl.value === '' && titleEl.value) {
                var s = (slugEl.getAttribute('data-suggest') || '').trim();
                if (s) slugEl.value = s;
            }
        });
    }

    // Featured image: upload and URL preview
    var featuredUploadBtn = document.querySelector('.nia-article-featured-upload-btn');
    var featuredUploadInput = document.getElementById('nia-article-featured-upload');
    var featuredUrlInput = document.getElementById('nia-article-featured-url');
    var featuredPreview = document.getElementById('nia-article-featured-preview');
    var uploadImageUrl = document.querySelector('.nia-article-editor-wrap') ? document.querySelector('.nia-article-editor-wrap').getAttribute('data-upload-image-url') : '';
    function showFeaturedPreview(src) {
        if (!featuredPreview) return;
        featuredPreview.innerHTML = '';
        if (src) {
            featuredPreview.innerHTML = '<img src="'+src+'" class="rounded shadow-sm mt-2" style="max-height:100px; max-width:100%; border: 1px solid rgba(255,255,255,0.1);">';
        }
    }
    if (featuredUploadBtn && featuredUploadInput && uploadImageUrl) {
        featuredUploadBtn.addEventListener('click', function() { featuredUploadInput.click(); });
        featuredUploadInput.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            var fd = new FormData();
            fd.append('file', this.files[0]);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', uploadImageUrl);
            xhr.onload = function() {
                var data = {};
                try { data = JSON.parse(xhr.responseText); } catch (e) {}
                var imgUrl = data.image_src || data.url;
                if (data.ok && imgUrl) {
                    if (featuredUrlInput) featuredUrlInput.value = imgUrl;
                    showFeaturedPreview(imgUrl);
                }
            };
            xhr.send(fd);
            this.value = '';
        });
    }
    if (featuredUrlInput && featuredPreview && featuredUrlInput.value.trim()) showFeaturedPreview(featuredUrlInput.value.trim());
})();
</script>
<?php
require ABSPATH . 'themes' . DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . 'tpl.footer.php';