-- Nia App – One-time updates for existing databases
-- Run this after you have already run schema.sql (for existing installs).
-- If your table prefix is not nia_, replace "nia_" with your prefix below.
-- Safe to run multiple times: adds columns/indexes only if missing (no duplicate errors).

DELIMITER $$

DROP PROCEDURE IF EXISTS nia_schema_updates$$
CREATE PROCEDURE nia_schema_updates()
BEGIN
  DECLARE tbl_prefix VARCHAR(32) DEFAULT 'nia_';

  -- nia_users: add premium_upto if missing
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'users') AND COLUMN_NAME = 'premium_upto') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'users ADD COLUMN premium_upto DATETIME DEFAULT NULL COMMENT ''Premium valid until'' AFTER last_login');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_posts: add status if missing
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'posts') AND COLUMN_NAME = 'status') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'posts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT ''publish'' AFTER excerpt');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_posts: add idx_status if missing
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'posts') AND INDEX_NAME = 'idx_status') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'posts ADD INDEX idx_status (status)');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_comments: add idx_user if missing
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'comments') AND INDEX_NAME = 'idx_user') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'comments ADD INDEX idx_user (user_id)');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_reports: add idx_user if missing
  IF (SELECT COUNT(*) FROM information_schema.STATISTICS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'reports') AND INDEX_NAME = 'idx_user') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'reports ADD INDEX idx_user (user_id)');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_videos: add YouTube metadata columns if missing
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'videos') AND COLUMN_NAME = 'yt_published_at') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'videos ADD COLUMN yt_published_at VARCHAR(32) DEFAULT NULL COMMENT ''YouTube publish date (ISO 8601)'' AFTER subtitle_url');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'videos') AND COLUMN_NAME = 'yt_channel_name') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'videos ADD COLUMN yt_channel_name VARCHAR(255) DEFAULT NULL COMMENT ''YouTube channel title'' AFTER yt_published_at');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_youtube_import_sources: add channel_name and total_imported if missing
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'youtube_import_sources') AND COLUMN_NAME = 'channel_name') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'youtube_import_sources ADD COLUMN channel_name VARCHAR(255) DEFAULT NULL COMMENT ''YouTube channel title'' AFTER value');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'youtube_import_sources') AND COLUMN_NAME = 'total_imported') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'youtube_import_sources ADD COLUMN total_imported INT UNSIGNED NOT NULL DEFAULT 0 AFTER channel_name');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;
  IF (SELECT COUNT(*) FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = CONCAT(tbl_prefix, 'youtube_import_sources') AND COLUMN_NAME = 'yt_video_count') = 0 THEN
    SET @sql = CONCAT('ALTER TABLE ', tbl_prefix, 'youtube_import_sources ADD COLUMN yt_video_count INT UNSIGNED DEFAULT NULL COMMENT ''Total videos on YouTube (from API)'' AFTER total_imported');
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  END IF;

  -- nia_youtube_import_log: create if not exists (dynamic table name in prepared stmt)
  SET @sql = CONCAT('CREATE TABLE IF NOT EXISTS ', tbl_prefix, 'youtube_import_log (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, source_id INT UNSIGNED NOT NULL, video_id INT UNSIGNED NOT NULL, video_title VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_source (source_id), INDEX idx_created (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

END$$

DELIMITER ;

CALL nia_schema_updates();
DROP PROCEDURE IF EXISTS nia_schema_updates;
