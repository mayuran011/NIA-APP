-- Nia App – Database schema (prefix: nia_)
-- Run this after creating database. Options (autoload=1) are cached at bootstrap for get_option().
-- For existing installs, run lib/schema-updates.sql to add missing columns and indexes.
--
-- Tables: options, users, users_groups, users_friends, videos, images, channels,
-- playlists, playlist_data, likes, activity, comments, comment_likes, reports,
-- conversation, conversation_data, blogcat, posts, pages, payments.
-- (Homepage layout is stored as option homepage_boxes, not a table.)
--
-- Options: Stored in nia_options; autoload=1 rows loaded into memory at bootstrap.
-- Control: theme (theme_color, background_color, dark_mode), players (choosen-player,
-- jwkey, player-logo, remote-player, youtube-player), upload (mediafolder, tmp-folder,
-- ffmpeg-cmd, binpath), SEO (video-seo-url, image-seo-url, profile-seo-url,
-- channel-seo-url, page-seo-url, article-seo-url, meta_description, meta_keywords),
-- social login (fb_app_id, fb_app_secret, google_client_id, google_client_secret),
-- premium (allowpremium, price, currency, paypal_client_id, paypal_secret, paypal_sandbox),
-- homepage (homepage_boxes), site (sitename, site_description, logo_url, favicon_url),
-- plugins (plugins_enabled), cache (cache_enabled, cache_ttl), ads, languages, etc.
-- Optional: bpp (items per page), thumb_width, thumb_height for listing/thumb generation.

-- Options (site settings; autoload=1 loaded at bootstrap)
CREATE TABLE IF NOT EXISTS nia_options (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  value LONGTEXT,
  autoload TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_name (name),
  INDEX idx_autoload (autoload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Videos & music (same table; type = 'video' | 'music')
CREATE TABLE IF NOT EXISTS nia_videos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  thumb VARCHAR(512) DEFAULT NULL,
  duration INT UNSIGNED NOT NULL DEFAULT 0,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  likes INT UNSIGNED NOT NULL DEFAULT 0,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  nsfw TINYINT(1) NOT NULL DEFAULT 0,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  private TINYINT(1) NOT NULL DEFAULT 0,
  premium TINYINT(1) NOT NULL DEFAULT 0,
  type VARCHAR(20) NOT NULL DEFAULT 'video',
  source VARCHAR(30) NOT NULL DEFAULT 'local',
  remote_url VARCHAR(1024) DEFAULT NULL,
  embed_code TEXT DEFAULT NULL,
  file_path VARCHAR(512) DEFAULT NULL,
  subtitle_url VARCHAR(512) DEFAULT NULL COMMENT 'SRT/VTT URL for player track',
  yt_published_at VARCHAR(32) DEFAULT NULL COMMENT 'YouTube publish date (ISO 8601)',
  yt_channel_name VARCHAR(255) DEFAULT NULL COMMENT 'YouTube channel title',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_category (category_id),
  INDEX idx_type (type),
  INDEX idx_featured (featured),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Images (upload or remote; galleries/albums, thumbnails, tags; views for tracking)
CREATE TABLE IF NOT EXISTS nia_images (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  album_id INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  thumb VARCHAR(512) DEFAULT NULL,
  path VARCHAR(512) DEFAULT NULL,
  remote_url VARCHAR(1024) DEFAULT NULL,
  tags VARCHAR(512) DEFAULT NULL,
  views INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_album (album_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Channels/categories (hierarchical; video/music/image)
CREATE TABLE IF NOT EXISTS nia_channels (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'video',
  description TEXT,
  thumb VARCHAR(512) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  INDEX idx_parent (parent_id),
  INDEX idx_type (type),
  UNIQUE KEY uq_slug_type (slug, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User playlists (video or image); system playlists use system_key
CREATE TABLE IF NOT EXISTS nia_playlists (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'video',
  system_key VARCHAR(30) DEFAULT NULL COMMENT '[likes], [history], [later]',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_system (system_key),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Playlist items (video or image)
CREATE TABLE IF NOT EXISTS nia_playlist_data (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NOT NULL,
  media_type VARCHAR(20) NOT NULL DEFAULT 'video',
  sort_order INT NOT NULL DEFAULT 0,
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_playlist (playlist_id),
  INDEX idx_media (media_type, media_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User groups: admin (1), moderator (2), premium, default
CREATE TABLE IF NOT EXISTS nia_users_groups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  slug VARCHAR(32) NOT NULL,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO nia_users_groups (id, name, slug) VALUES (1, 'Admin', 'admin'), (2, 'Moderator', 'moderator'), (3, 'Premium', 'premium'), (4, 'Default', 'default');

-- Users: name, username, email, avatar, group_id, last login, etc.
CREATE TABLE IF NOT EXISTS nia_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL DEFAULT 4,
  username VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(512) DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  premium_upto DATETIME DEFAULT NULL COMMENT 'Premium valid until (NULL = use group)',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email),
  INDEX idx_group (group_id),
  INDEX idx_last_login (last_login),
  INDEX idx_premium_upto (premium_upto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If nia_users already exists, add premium column: ALTER TABLE nia_users ADD COLUMN premium_upto DATETIME DEFAULT NULL AFTER last_login;

-- Payment log (optional; for history)
CREATE TABLE IF NOT EXISTS nia_payments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  provider VARCHAR(32) NOT NULL DEFAULT 'paypal',
  external_id VARCHAR(255) DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  currency VARCHAR(6) NOT NULL DEFAULT 'USD',
  status VARCHAR(32) NOT NULL DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Subscriptions (subscribe/unsubscribe); subscriber count on profile
CREATE TABLE IF NOT EXISTS nia_users_friends (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  friend_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_friend (user_id, friend_id),
  INDEX idx_friend (friend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conversations (two-way private messages)
CREATE TABLE IF NOT EXISTS nia_conversation (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_one_id INT UNSIGNED NOT NULL,
  user_two_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_one (user_one_id),
  INDEX idx_user_two (user_two_id),
  UNIQUE KEY uq_pair (user_one_id, user_two_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS nia_conversation_data (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conversation (conversation_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blog categories (blogcat)
CREATE TABLE IF NOT EXISTS nia_blogcat (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Blog posts (articles); status = 'publish' | 'draft'
CREATE TABLE IF NOT EXISTS nia_posts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  category_id INT UNSIGNED NOT NULL DEFAULT 0,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  content LONGTEXT,
  excerpt TEXT,
  status VARCHAR(20) NOT NULL DEFAULT 'publish',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_category (category_id),
  INDEX idx_status (status),
  INDEX idx_created (created_at),
  INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Static pages (custom pages)
CREATE TABLE IF NOT EXISTS nia_pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  content LONGTEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Likes / dislikes (per user per object)
CREATE TABLE IF NOT EXISTS nia_likes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  object_type VARCHAR(32) NOT NULL DEFAULT 'video',
  object_id INT UNSIGNED NOT NULL,
  value TINYINT NOT NULL DEFAULT 1 COMMENT '1=like, -1=dislike',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_object (user_id, object_type, object_id),
  INDEX idx_object (object_type, object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments (threaded; object_type e.g. video, image)
CREATE TABLE IF NOT EXISTS nia_comments (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  parent_id INT UNSIGNED NOT NULL DEFAULT 0,
  object_type VARCHAR(32) NOT NULL DEFAULT 'video',
  object_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  likes_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_object (object_type, object_id),
  INDEX idx_parent (parent_id),
  INDEX idx_user (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comment likes
CREATE TABLE IF NOT EXISTS nia_comment_likes (
  comment_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (comment_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reports (spam, copyright, etc.)
CREATE TABLE IF NOT EXISTS nia_reports (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  object_type VARCHAR(32) NOT NULL DEFAULT 'video',
  object_id INT UNSIGNED NOT NULL,
  reason VARCHAR(64) NOT NULL,
  details TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_object (object_type, object_id),
  INDEX idx_user (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity (buzz): liked, disliked, added to playlist, watched, shared, subscribed
CREATE TABLE IF NOT EXISTS nia_activity (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  action VARCHAR(32) NOT NULL,
  object_type VARCHAR(32) NOT NULL DEFAULT 'video',
  object_id INT UNSIGNED NOT NULL DEFAULT 0,
  extra VARCHAR(512) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_action (action),
  INDEX idx_created (created_at),
  INDEX idx_object (object_type, object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- YouTube Importer: channels/playlists/keywords for auto or manual import
CREATE TABLE IF NOT EXISTS nia_youtube_import_sources (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'channel' COMMENT 'channel, playlist, keyword',
  value VARCHAR(512) NOT NULL COMMENT 'channel_id, playlist_id, or keyword text',
  channel_name VARCHAR(255) DEFAULT NULL COMMENT 'YouTube channel title (for display)',
  total_imported INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Total videos imported from this source',
  auto_import TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = auto import new videos (channels only)',
  last_imported_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_auto (auto_import),
  UNIQUE KEY uq_user_type_value (user_id, type, value(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log of each video imported from a source (for "added log")
CREATE TABLE IF NOT EXISTS nia_youtube_import_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_id INT UNSIGNED NOT NULL,
  video_id INT UNSIGNED NOT NULL COMMENT 'nia_videos.id',
  video_title VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_source (source_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
