-- 0005 Ads campaigns, groups, creatives, placements, rules, events
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS ad_campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  advertiser_name VARCHAR(160) NULL,
  status ENUM('draft','active','paused','expired','disabled') NOT NULL DEFAULT 'draft',
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  budget_note VARCHAR(255) NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ad_campaign_status_dates (status, starts_at, ends_at),
  CONSTRAINT fk_ad_campaign_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ad_groups (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  rotation_mode ENUM('fixed','random','weighted') NOT NULL DEFAULT 'random',
  device_target ENUM('all','desktop','mobile') NOT NULL DEFAULT 'all',
  membership_target JSON NULL,
  geo_target_json JSON NULL,
  status ENUM('active','paused','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ad_groups_campaign (campaign_id),
  KEY idx_ad_groups_status_device (status, device_target),
  CONSTRAINT fk_ad_group_campaign FOREIGN KEY (campaign_id) REFERENCES ad_campaigns(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ad_creatives (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad_group_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  creative_type ENUM('banner','html','popup','floating','pre_roll','mid_roll','pause','vast_vpaid') NOT NULL,
  html_code MEDIUMTEXT NULL,
  media_url VARCHAR(500) NULL,
  click_url VARCHAR(500) NULL,
  vast_tag_url VARCHAR(1000) NULL,
  width INT UNSIGNED NULL,
  height INT UNSIGNED NULL,
  weight INT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('active','paused','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ad_creative_group_status (ad_group_id, status),
  KEY idx_ad_creative_type (creative_type),
  CONSTRAINT fk_ad_creative_group FOREIGN KEY (ad_group_id) REFERENCES ad_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ad_placements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  placement_code VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  page_scope ENUM('global','home','watch','listing','search','account','embed','admin_preview') NOT NULL DEFAULT 'global',
  placement_area VARCHAR(120) NOT NULL,
  device ENUM('all','desktop','mobile') NOT NULL DEFAULT 'all',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ad_placement_code (placement_code),
  KEY idx_ad_placement_scope_device (page_scope, device, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ad_placement_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  placement_id BIGINT UNSIGNED NOT NULL,
  ad_group_id BIGINT UNSIGNED NULL,
  ad_creative_id BIGINT UNSIGNED NULL,
  membership_tier ENUM('guest','free','premium','vip','any') NOT NULL DEFAULT 'any',
  content_access_level ENUM('public','registered','premium','vip','any') NOT NULL DEFAULT 'any',
  category_id BIGINT UNSIGNED NULL,
  tag_id BIGINT UNSIGNED NULL,
  max_impressions_per_session INT UNSIGNED NULL,
  max_impressions_per_day INT UNSIGNED NULL,
  priority INT UNSIGNED NOT NULL DEFAULT 100,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ad_rule_placement_priority (placement_id, status, priority),
  KEY idx_ad_rule_membership (membership_tier),
  CONSTRAINT fk_ad_rule_placement FOREIGN KEY (placement_id) REFERENCES ad_placements(id) ON DELETE CASCADE,
  CONSTRAINT fk_ad_rule_group FOREIGN KEY (ad_group_id) REFERENCES ad_groups(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_rule_creative FOREIGN KEY (ad_creative_id) REFERENCES ad_creatives(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_rule_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_rule_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ad_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad_creative_id BIGINT UNSIGNED NULL,
  ad_placement_id BIGINT UNSIGNED NULL,
  content_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  guest_session_id BIGINT UNSIGNED NULL,
  event_type ENUM('impression','click','close','skip','error') NOT NULL,
  membership_tier ENUM('guest','free','premium','vip') NOT NULL DEFAULT 'guest',
  device ENUM('desktop','mobile','tablet','unknown') NOT NULL DEFAULT 'unknown',
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ad_events_creative_type_time (ad_creative_id, event_type, created_at),
  KEY idx_ad_events_placement_time (ad_placement_id, created_at),
  KEY idx_ad_events_user_time (user_id, created_at),
  CONSTRAINT fk_ad_event_creative FOREIGN KEY (ad_creative_id) REFERENCES ad_creatives(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_event_placement FOREIGN KEY (ad_placement_id) REFERENCES ad_placements(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_event_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_event_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_ad_event_guest FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
