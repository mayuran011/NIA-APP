<?php
/**
 * RSS feeds for all media, video, music, images.
 * Routes: /feed (all), /feed/video, /feed/music, /feed/images.
 * Uses site name, URLs, thumbnails.
 */
if (!defined('in_nia_app')) exit;

$nia_section = $GLOBALS['nia_route_section'] ?? '';
$section = $nia_section !== '' ? $nia_section : 'all';
if (!in_array($section, ['all', 'video', 'music', 'images'], true)) {
    $section = 'all';
}

$site_name = get_option('sitename', 'Nia App');
$site_url = rtrim(SITE_URL, '/');
$site_desc = get_option('site_description', 'Video sharing and multimedia.');
$limit = 50;

$items = [];
if ($section === 'all' || $section === 'video' || $section === 'music') {
    $type = $section === 'all' ? null : $section;
    $args = ['limit' => $limit, 'section' => 'browse'];
    if ($type) $args['type'] = $type;
    $list = get_videos($args);
    foreach ($list as $v) {
        $items[] = [
            'type' => $v->type ?? 'video',
            'id' => (int) $v->id,
            'title' => $v->title ?? '',
            'link' => video_url($v->id, $v->title ?? ''),
            'description' => $v->description ?? '',
            'thumb' => $v->thumb ?? null,
            'created_at' => $v->created_at ?? null,
        ];
    }
}
if ($section === 'all' || $section === 'images') {
    $list = get_images(['limit' => $section === 'all' ? 30 : $limit]);
    foreach ($list as $img) {
        $items[] = [
            'type' => 'image',
            'id' => (int) $img->id,
            'title' => $img->title ?? '',
            'link' => function_exists('view_url') ? view_url($img->id, $img->title ?? '') : image_url($img->id, $img->title ?? ''),
            'description' => $img->description ?? '',
            'thumb' => $img->thumb ?? $img->path ?? null,
            'created_at' => $img->created_at ?? null,
        ];
    }
}

if ($section === 'all') {
    usort($items, function ($a, $b) {
        $ta = strtotime($a['created_at'] ?? '0');
        $tb = strtotime($b['created_at'] ?? '0');
        return $tb - $ta;
    });
    $items = array_slice($items, 0, $limit);
}

$channel_title = $section === 'all' ? $site_name : $site_name . ' – ' . ucfirst($section);
$feed_link = url($section === 'all' ? 'feed' : 'feed/' . $section);

header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=300');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title><?php echo _e($channel_title); ?></title>
    <link><?php echo _e($site_url); ?></link>
    <description><?php echo _e($site_desc); ?></description>
    <atom:link href="<?php echo _e($feed_link); ?>" rel="self" type="application/rss+xml"/>
    <language>en</language>
    <?php foreach ($items as $item) {
        $link = $item['link'];
        $thumb = $item['thumb'];
        if ($thumb !== null && $thumb !== '' && strpos($thumb, 'http') !== 0) {
            $thumb = $site_url . '/' . ltrim($thumb, '/');
        }
        $pubDate = !empty($item['created_at']) ? date(DATE_RSS, strtotime($item['created_at'])) : date(DATE_RSS);
    ?>
    <item>
        <title><?php echo _e($item['title']); ?></title>
        <link><?php echo _e($link); ?></link>
        <description><?php echo _e($item['description'] !== '' ? $item['description'] : $item['title']); ?></description>
        <pubDate><?php echo $pubDate; ?></pubDate>
        <guid isPermaLink="true"><?php echo _e($link); ?></guid>
        <?php if ($thumb) { ?><media:thumbnail url="<?php echo _e($thumb); ?>"/><?php } ?>
    </item>
    <?php } ?>
</channel>
</rss>
