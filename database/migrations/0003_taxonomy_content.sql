-- 0003 Taxonomy, content, media, source, player
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  parent_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT NULL,
  status ENUM('active','hidden','disabled') NOT NULL DEFAULT 'active',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  content_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categories_slug (slug),
  KEY idx_categories_parent (parent_id),
  KEY idx_categories_status_sort (status, sort_order),
  CONSTRAINT fk_categories_parent FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tags (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  normalized_name VARCHAR(160) NOT NULL,
  description TEXT NULL,
  content_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  is_trending TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','merged','hidden','disabled') NOT NULL DEFAULT 'active',
  merged_into_tag_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tags_slug (slug),
  UNIQUE KEY uq_tags_normalized (normalized_name),
  KEY idx_tags_status_trending (status, is_trending),
  CONSTRAINT fk_tags_merged FOREIGN KEY (merged_into_tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS studios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  type ENUM('studio','maker','brand') NOT NULL DEFAULT 'studio',
  bio TEXT NULL,
  logo_url VARCHAR(500) NULL,
  website_url VARCHAR(500) NULL,
  content_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  follower_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','hidden','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_studios_slug (slug),
  KEY idx_studios_type_status (type, status),
  FULLTEXT KEY ft_studios_name_bio (name, bio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS performers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  bio TEXT NULL,
  avatar_url VARCHAR(500) NULL,
  cover_url VARCHAR(500) NULL,
  content_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  follower_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  compliance_status ENUM('unknown','verified','needs_review','blocked') NOT NULL DEFAULT 'unknown',
  status ENUM('active','hidden','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_performers_slug (slug),
  KEY idx_performers_status (status),
  FULLTEXT KEY ft_performers_name_bio (name, bio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS series_collections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  studio_id BIGINT UNSIGNED NULL,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(190) NOT NULL,
  description TEXT NULL,
  cover_url VARCHAR(500) NULL,
  content_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','hidden','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_series_slug (slug),
  KEY idx_series_studio (studio_id),
  CONSTRAINT fk_series_studio FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS storage_servers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  server_type ENUM('local','sftp','remote_http','cdn') NOT NULL DEFAULT 'local',
  base_public_url VARCHAR(500) NULL,
  private_root_path VARCHAR(500) NULL,
  credentials_encrypted TEXT NULL,
  capacity_bytes BIGINT UNSIGNED NULL,
  used_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','readonly','maintenance','disabled') NOT NULL DEFAULT 'active',
  last_health_check_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_storage_status_default (status, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS player_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  applies_to_source ENUM('any','doodstream','direct_mysql') NOT NULL DEFAULT 'any',
  autoplay TINYINT(1) NOT NULL DEFAULT 0,
  logo_enabled TINYINT(1) NOT NULL DEFAULT 0,
  logo_asset_url VARCHAR(500) NULL,
  logo_position VARCHAR(40) NULL,
  pause_ads_enabled TINYINT(1) NOT NULL DEFAULT 0,
  vast_vpaid_enabled TINYINT(1) NOT NULL DEFAULT 0,
  timeline_preview_enabled TINYINT(1) NOT NULL DEFAULT 0,
  hls_enabled TINYINT(1) NOT NULL DEFAULT 1,
  config_json JSON NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_player_source_status (applies_to_source, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  internal_code VARCHAR(120) NULL,
  source_type ENUM('doodstream','direct_mysql') NOT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  description TEXT NULL,
  release_date DATE NULL,
  runtime_seconds INT UNSIGNED NULL,
  studio_id BIGINT UNSIGNED NULL,
  series_id BIGINT UNSIGNED NULL,
  player_profile_id BIGINT UNSIGNED NULL,
  primary_thumbnail_asset_id BIGINT UNSIGNED NULL,
  thumbnail_url VARCHAR(500) NULL,
  lifecycle_status ENUM('draft','pending_review','published','scheduled','rejected','disabled','broken','archived') NOT NULL DEFAULT 'draft',
  access_level ENUM('public','registered','premium','vip') NOT NULL DEFAULT 'public',
  visibility_level ENUM('visible','hidden','unlisted') NOT NULL DEFAULT 'visible',
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  allow_comments TINYINT(1) NOT NULL DEFAULT 1,
  allow_public_embed TINYINT(1) NOT NULL DEFAULT 0,
  seo_indexable TINYINT(1) NOT NULL DEFAULT 1,
  view_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  like_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  dislike_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  favorite_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  comment_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  report_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  trending_score DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  scheduled_at DATETIME NULL,
  published_at DATETIME NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  updated_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_content_slug (slug),
  UNIQUE KEY uq_content_internal_code (internal_code),
  KEY idx_content_source_status (source_type, lifecycle_status),
  KEY idx_content_access_status (access_level, lifecycle_status),
  KEY idx_content_release (release_date),
  KEY idx_content_published (published_at),
  KEY idx_content_studio (studio_id),
  KEY idx_content_series (series_id),
  KEY idx_content_trending (trending_score),
  FULLTEXT KEY ft_content_search (title, description),
  CONSTRAINT fk_content_studio FOREIGN KEY (studio_id) REFERENCES studios(id) ON DELETE SET NULL,
  CONSTRAINT fk_content_series FOREIGN KEY (series_id) REFERENCES series_collections(id) ON DELETE SET NULL,
  CONSTRAINT fk_content_player FOREIGN KEY (player_profile_id) REFERENCES player_profiles(id) ON DELETE SET NULL,
  CONSTRAINT fk_content_created_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_content_updated_admin FOREIGN KEY (updated_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NULL,
  storage_server_id BIGINT UNSIGNED NULL,
  asset_type ENUM('video_mp4','video_hls','hls_segment','thumbnail','poster','sprite','subtitle','logo','other') NOT NULL,
  file_path VARCHAR(500) NULL,
  public_url VARCHAR(500) NULL,
  mime_type VARCHAR(120) NULL,
  file_size_bytes BIGINT UNSIGNED NULL,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  duration_seconds INT UNSIGNED NULL,
  checksum_sha256 CHAR(64) NULL,
  status ENUM('active','processing','missing','disabled') NOT NULL DEFAULT 'active',
  metadata_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_media_content_type (content_id, asset_type),
  KEY idx_media_storage (storage_server_id),
  KEY idx_media_status (status),
  CONSTRAINT fk_media_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_media_storage FOREIGN KEY (storage_server_id) REFERENCES storage_servers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_sources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  source_type ENUM('doodstream','direct_mysql') NOT NULL,
  doodstream_file_code VARCHAR(120) NULL,
  doodstream_folder_id VARCHAR(120) NULL,
  doodstream_embed_url VARCHAR(500) NULL,
  doodstream_download_url VARCHAR(500) NULL,
  direct_media_asset_id BIGINT UNSIGNED NULL,
  iframe_url VARCHAR(500) NULL,
  source_payload_json JSON NULL,
  remote_status VARCHAR(80) NULL,
  last_synced_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_source_content (content_id),
  UNIQUE KEY uq_doodstream_file_code (doodstream_file_code),
  KEY idx_content_sources_type (source_type),
  CONSTRAINT fk_cs_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_direct_asset FOREIGN KEY (direct_media_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_categories (
  content_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (content_id, category_id),
  KEY idx_cc_category (category_id),
  CONSTRAINT fk_cc_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_cc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_tags (
  content_id BIGINT UNSIGNED NOT NULL,
  tag_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (content_id, tag_id),
  KEY idx_ct_tag (tag_id),
  CONSTRAINT fk_ct_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_ct_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_performers (
  content_id BIGINT UNSIGNED NOT NULL,
  performer_id BIGINT UNSIGNED NOT NULL,
  performer_role VARCHAR(80) NULL,
  PRIMARY KEY (content_id, performer_id),
  KEY idx_cp_performer (performer_id),
  CONSTRAINT fk_cp_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_cp_performer FOREIGN KEY (performer_id) REFERENCES performers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_health_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  content_id BIGINT UNSIGNED NOT NULL,
  check_type ENUM('source_available','thumbnail_available','player_embed','hls_segments','metadata','seo','compliance') NOT NULL,
  status ENUM('ok','warning','failed','skipped') NOT NULL,
  message TEXT NULL,
  checked_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_health_content_type (content_id, check_type),
  KEY idx_health_status_checked (status, checked_at),
  CONSTRAINT fk_health_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
