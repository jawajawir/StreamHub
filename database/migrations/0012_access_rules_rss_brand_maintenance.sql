-- 0012 Access rule engine, RSS, robots, brand assets, cache, maintenance, system health, partner, security events
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS access_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rule_key VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  rule_scope ENUM('playback','page','ads','download_block','embed','registration','comment','search') NOT NULL,
  source_type ENUM('any','doodstream','direct_mysql') NOT NULL DEFAULT 'any',
  membership_tier ENUM('guest','free','premium','vip','any') NOT NULL DEFAULT 'any',
  access_level ENUM('public','registered','premium','vip','any') NOT NULL DEFAULT 'any',
  device ENUM('all','desktop','mobile','tablet') NOT NULL DEFAULT 'all',
  country_code CHAR(2) NULL,
  category_id BIGINT UNSIGNED NULL,
  tag_id BIGINT UNSIGNED NULL,
  action ENUM('allow','block','limit','show_notice','require_login','require_membership') NOT NULL DEFAULT 'allow',
  config_json JSON NULL,
  priority INT UNSIGNED NOT NULL DEFAULT 100,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_access_rule_key (rule_key),
  KEY idx_access_rules_scope_priority (rule_scope, status, priority),
  KEY idx_access_rules_membership (membership_tier, access_level),
  CONSTRAINT fk_access_rule_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_access_rule_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rss_feeds (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  feed_key VARCHAR(120) NOT NULL,
  title VARCHAR(180) NOT NULL,
  feed_type ENUM('latest','category','tag','performer','studio','series','custom') NOT NULL DEFAULT 'latest',
  target_entity_id BIGINT UNSIGNED NULL,
  item_limit INT UNSIGNED NOT NULL DEFAULT 50,
  cache_minutes INT UNSIGNED NOT NULL DEFAULT 60,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_generated_at DATETIME NULL,
  output_path VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rss_feed_key (feed_key),
  KEY idx_rss_type_status (feed_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS robots_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_agent VARCHAR(120) NOT NULL DEFAULT '*',
  directive ENUM('allow','disallow','sitemap','crawl_delay') NOT NULL,
  rule_value VARCHAR(500) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_robots_status_sort (status, sort_order),
  KEY idx_robots_user_agent (user_agent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS brand_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_key VARCHAR(120) NOT NULL,
  asset_type ENUM('logo','favicon','watermark','player_logo','og_default','banner','icon','other') NOT NULL,
  title VARCHAR(160) NOT NULL,
  file_url VARCHAR(500) NOT NULL,
  file_path VARCHAR(500) NULL,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_brand_asset_key (asset_key),
  KEY idx_brand_asset_type_status (asset_type, status),
  CONSTRAINT fk_brand_asset_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cache_purge_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  purge_scope ENUM('all','home','content','category','tag','performer','studio','sitemap','css_js','template','custom') NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  status ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
  requested_by_admin_id BIGINT UNSIGNED NULL,
  error_message TEXT NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cache_purge_status (status, created_at),
  CONSTRAINT fk_cache_purge_admin FOREIGN KEY (requested_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_windows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  message TEXT NULL,
  mode ENUM('frontend_only','admin_only','full_site') NOT NULL DEFAULT 'frontend_only',
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  allow_admin_access TINYINT(1) NOT NULL DEFAULT 1,
  status ENUM('scheduled','active','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_maintenance_status_dates (status, starts_at, ends_at),
  CONSTRAINT fk_maintenance_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_health_checks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  check_key VARCHAR(120) NOT NULL,
  check_group ENUM('database','storage','doodstream','cron','queue','mail','security','performance','seo','media') NOT NULL,
  status ENUM('ok','warning','failed','unknown') NOT NULL DEFAULT 'unknown',
  message TEXT NULL,
  details_json JSON NULL,
  checked_at DATETIME NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_health_group_status (check_group, status, checked_at),
  KEY idx_health_key_checked (check_key, checked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS partner_embed_keys (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  partner_name VARCHAR(160) NOT NULL,
  api_key_hash CHAR(64) NOT NULL,
  allowed_domains_json JSON NULL,
  allowed_content_json JSON NULL,
  rate_limit_per_day INT UNSIGNED NULL,
  status ENUM('active','paused','disabled') NOT NULL DEFAULT 'active',
  created_by_admin_id BIGINT UNSIGNED NULL,
  last_used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_partner_embed_key_hash (api_key_hash),
  KEY idx_partner_embed_status (status),
  CONSTRAINT fk_partner_embed_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
