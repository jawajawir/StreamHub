-- 0009 Analytics, quota, bandwidth, admin notifications
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS content_views (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  guest_session_id BIGINT UNSIGNED NULL,
  membership_tier ENUM('guest','free','premium','vip') NOT NULL DEFAULT 'guest',
  device ENUM('desktop','mobile','tablet','unknown') NOT NULL DEFAULT 'unknown',
  watch_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  ip_hash CHAR(64) NULL,
  referrer_domain VARCHAR(190) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_views_content_time (content_id, created_at),
  KEY idx_views_user_time (user_id, created_at),
  KEY idx_views_guest_time (guest_session_id, created_at),
  CONSTRAINT fk_view_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_view_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_view_guest FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_daily_stats (
  stat_date DATE NOT NULL,
  content_id BIGINT UNSIGNED NOT NULL,
  views BIGINT UNSIGNED NOT NULL DEFAULT 0,
  unique_views BIGINT UNSIGNED NOT NULL DEFAULT 0,
  likes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  dislikes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  favorites BIGINT UNSIGNED NOT NULL DEFAULT 0,
  comments BIGINT UNSIGNED NOT NULL DEFAULT 0,
  reports BIGINT UNSIGNED NOT NULL DEFAULT 0,
  watch_seconds BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (stat_date, content_id),
  KEY idx_daily_content (content_id, stat_date),
  CONSTRAINT fk_daily_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playback_quota_usage (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  guest_session_id BIGINT UNSIGNED NULL,
  ip_hash CHAR(64) NULL,
  usage_date DATE NOT NULL,
  play_count INT UNSIGNED NOT NULL DEFAULT 0,
  bandwidth_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_quota_user_date (user_id, usage_date),
  UNIQUE KEY uq_quota_guest_date (guest_session_id, usage_date),
  KEY idx_quota_ip_date (ip_hash, usage_date),
  CONSTRAINT fk_quota_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_quota_guest FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notification_type VARCHAR(120) NOT NULL,
  severity ENUM('info','warning','error','critical') NOT NULL DEFAULT 'info',
  title VARCHAR(255) NOT NULL,
  body TEXT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  action_url VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  read_at DATETIME NULL,
  KEY idx_admin_notif_read_severity (is_read, severity, created_at),
  KEY idx_admin_notif_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
