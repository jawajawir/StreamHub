-- 0004 Frontend engagement, comments, watch history
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS content_reactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  reaction ENUM('like','dislike') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reaction_user_content (user_id, content_id),
  KEY idx_reaction_content_type (content_id, reaction),
  CONSTRAINT fk_reaction_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  content_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, content_id),
  KEY idx_fav_content (content_id),
  CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlists (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NULL,
  visibility ENUM('private','unlisted','public') NOT NULL DEFAULT 'private',
  item_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_playlist_user (user_id),
  UNIQUE KEY uq_playlist_user_slug (user_id, slug),
  CONSTRAINT fk_playlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS playlist_items (
  playlist_id BIGINT UNSIGNED NOT NULL,
  content_id BIGINT UNSIGNED NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (playlist_id, content_id),
  KEY idx_playlist_items_content (content_id),
  CONSTRAINT fk_pi_playlist FOREIGN KEY (playlist_id) REFERENCES playlists(id) ON DELETE CASCADE,
  CONSTRAINT fk_pi_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS watch_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  content_id BIGINT UNSIGNED NOT NULL,
  watched_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  progress_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  completed TINYINT(1) NOT NULL DEFAULT 0,
  last_watched_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_watch_user_content (user_id, content_id),
  KEY idx_watch_user_last (user_id, last_watched_at),
  KEY idx_watch_content (content_id),
  CONSTRAINT fk_watch_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_watch_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  body TEXT NOT NULL,
  status ENUM('visible','pending','hidden','spam','deleted') NOT NULL DEFAULT 'pending',
  like_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  dislike_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  report_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  spam_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_comments_content_status (content_id, status, created_at),
  KEY idx_comments_user (user_id),
  KEY idx_comments_parent (parent_id),
  FULLTEXT KEY ft_comments_body (body),
  CONSTRAINT fk_comment_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_comment_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comment_votes (
  comment_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  vote ENUM('like','dislike') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (comment_id, user_id),
  KEY idx_comment_votes_user (user_id),
  CONSTRAINT fk_cv_comment FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
  CONSTRAINT fk_cv_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reporter_user_id BIGINT UNSIGNED NULL,
  guest_session_id BIGINT UNSIGNED NULL,
  report_type ENUM('broken_video','content_flag','comment_flag','feedback','abuse','other') NOT NULL,
  entity_type ENUM('content','comment','user','page','system') NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  reason VARCHAR(120) NULL,
  message TEXT NULL,
  status ENUM('new','reviewing','resolved','rejected','ignored') NOT NULL DEFAULT 'new',
  handled_by_admin_id BIGINT UNSIGNED NULL,
  handled_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_reports_status_type (status, report_type, created_at),
  KEY idx_reports_entity (entity_type, entity_id),
  CONSTRAINT fk_report_user FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_report_guest FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id) ON DELETE SET NULL,
  CONSTRAINT fk_report_admin FOREIGN KEY (handled_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS follows (
  user_id BIGINT UNSIGNED NOT NULL,
  entity_type ENUM('performer','studio') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, entity_type, entity_id),
  KEY idx_follows_entity (entity_type, entity_id),
  CONSTRAINT fk_follows_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
