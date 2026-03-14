-- Nia App – Reset database and recreate all tables
-- WARNING: This deletes ALL data. Use only for a fresh start.
-- 1. Run this file in phpMyAdmin (Import) or: mysql -u root -p test < lib/reset-database.sql
-- 2. Default admin after reset: username "admin", password "admin123" (change after first login)

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS nia_youtube_import_sources;
DROP TABLE IF EXISTS nia_activity;
DROP TABLE IF EXISTS nia_reports;
DROP TABLE IF EXISTS nia_comment_likes;
DROP TABLE IF EXISTS nia_comments;
DROP TABLE IF EXISTS nia_likes;
DROP TABLE IF EXISTS nia_pages;
DROP TABLE IF EXISTS nia_posts;
DROP TABLE IF EXISTS nia_blogcat;
DROP TABLE IF EXISTS nia_conversation_data;
DROP TABLE IF EXISTS nia_conversation;
DROP TABLE IF EXISTS nia_users_friends;
DROP TABLE IF EXISTS nia_payments;
DROP TABLE IF EXISTS nia_users;
DROP TABLE IF EXISTS nia_playlist_data;
DROP TABLE IF EXISTS nia_playlists;
DROP TABLE IF EXISTS nia_channels;
DROP TABLE IF EXISTS nia_images;
DROP TABLE IF EXISTS nia_videos;
DROP TABLE IF EXISTS nia_options;
DROP TABLE IF EXISTS nia_users_groups;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- Recreate schema (same as schema.sql)
-- ---------------------------------------------------------------------------

CREATE TABLE nia_options (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  value LONGTEXT,
  autoload TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_name (name),
  INDEX idx_autoload (autoload)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_videos (
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
  subtitle_url VARCHAR(512) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_category (category_id),
  INDEX idx_type (type),
  INDEX idx_featured (featured),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_images (
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

CREATE TABLE nia_channels (
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

CREATE TABLE nia_playlists (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL DEFAULT 0,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'video',
  system_key VARCHAR(30) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  INDEX idx_system (system_key),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_playlist_data (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  playlist_id INT UNSIGNED NOT NULL,
  media_id INT UNSIGNED NOT NULL,
  media_type VARCHAR(20) NOT NULL DEFAULT 'video',
  sort_order INT NOT NULL DEFAULT 0,
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_playlist (playlist_id),
  INDEX idx_media (media_type, media_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_users_groups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  slug VARCHAR(32) NOT NULL,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO nia_users_groups (id, name, slug) VALUES (1, 'Admin', 'admin'), (2, 'Moderator', 'moderator'), (3, 'Premium', 'premium'), (4, 'Default', 'default');

CREATE TABLE nia_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  group_id INT UNSIGNED NOT NULL DEFAULT 4,
  username VARCHAR(64) NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(512) DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  premium_upto DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email (email),
  INDEX idx_group (group_id),
  INDEX idx_last_login (last_login),
  INDEX idx_premium_upto (premium_upto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_payments (
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

CREATE TABLE nia_users_friends (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  friend_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_friend (user_id, friend_id),
  INDEX idx_friend (friend_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_conversation (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_one_id INT UNSIGNED NOT NULL,
  user_two_id INT UNSIGNED NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_user_one (user_one_id),
  INDEX idx_user_two (user_two_id),
  UNIQUE KEY uq_pair (user_one_id, user_two_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_conversation_data (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  conversation_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_conversation (conversation_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_blogcat (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  description TEXT,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_posts (
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

CREATE TABLE nia_pages (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL,
  content LONGTEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_likes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  object_type VARCHAR(32) NOT NULL DEFAULT 'video',
  object_id INT UNSIGNED NOT NULL,
  value TINYINT NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_object (user_id, object_type, object_id),
  INDEX idx_object (object_type, object_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_comments (
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

CREATE TABLE nia_comment_likes (
  comment_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (comment_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nia_reports (
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

CREATE TABLE nia_activity (
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

CREATE TABLE nia_youtube_import_sources (
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

CREATE TABLE IF NOT EXISTS nia_youtube_import_log (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  source_id INT UNSIGNED NOT NULL,
  video_id INT UNSIGNED NOT NULL COMMENT 'nia_videos.id',
  video_title VARCHAR(255) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_source (source_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin user (group_id 1 = Admin). Password: admin123
-- Change password after first login.
INSERT INTO nia_users (id, group_id, username, name, email, password) VALUES
(1, 1, 'admin', 'Administrator', 'admin@localhost', '$2y$10$zl9M0bqzMxxXz4p9fEAZtO2WK/SfaTghmwWgJNoFbjGLfgXi9lsfG');
