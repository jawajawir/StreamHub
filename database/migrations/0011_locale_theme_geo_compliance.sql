-- 0011 Localization, theme, device policy, geo, compliance, external scripts
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS languages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(12) NOT NULL,
  name VARCHAR(80) NOT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_languages_code (code),
  KEY idx_languages_active_default (is_active, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS translations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  language_code VARCHAR(12) NOT NULL,
  translation_group VARCHAR(80) NOT NULL,
  translation_key VARCHAR(190) NOT NULL,
  translation_value TEXT NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_translation (language_code, translation_group, translation_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_presets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  mode ENUM('dark','light','auto') NOT NULL DEFAULT 'dark',
  accent_color VARCHAR(40) NULL,
  logo_url VARCHAR(500) NULL,
  favicon_url VARCHAR(500) NULL,
  layout_config_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_theme_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS device_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  policy_key VARCHAR(120) NOT NULL,
  device ENUM('desktop','mobile','tablet','all') NOT NULL DEFAULT 'all',
  policy_json JSON NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_device_policy (policy_key, device),
  KEY idx_device_policy_enabled (device, is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS geo_rules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  country_code CHAR(2) NOT NULL,
  rule_type ENUM('allow','block','notice','ads_target','compliance') NOT NULL,
  rule_json JSON NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  priority INT UNSIGNED NOT NULL DEFAULT 100,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_geo_country_type (country_code, rule_type, status),
  KEY idx_geo_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compliance_records (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('content','performer','studio','page','site') NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  record_type ENUM('age_statement','records_keeper','creator_note','studio_note','publish_checklist','legal_note','other') NOT NULL,
  status ENUM('unknown','verified','needs_review','rejected','not_applicable') NOT NULL DEFAULT 'unknown',
  notes TEXT NULL,
  evidence_reference VARCHAR(500) NULL,
  reviewed_by_admin_id BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_compliance_entity (entity_type, entity_id),
  KEY idx_compliance_status (status, record_type),
  CONSTRAINT fk_compliance_admin FOREIGN KEY (reviewed_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS external_scripts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  script_key VARCHAR(120) NOT NULL,
  name VARCHAR(160) NOT NULL,
  placement ENUM('head','body_start','body_end') NOT NULL DEFAULT 'body_end',
  script_code MEDIUMTEXT NOT NULL,
  page_scope ENUM('global','frontend_only','admin_only','home','watch','listing','account') NOT NULL DEFAULT 'frontend_only',
  membership_scope ENUM('all','guest_free_only','premium_vip_only') NOT NULL DEFAULT 'all',
  status ENUM('active','disabled') NOT NULL DEFAULT 'disabled',
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ext_script_key (script_key),
  KEY idx_ext_script_scope_status (page_scope, status),
  CONSTRAINT fk_ext_script_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
