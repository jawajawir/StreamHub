-- 0007 SEO meta, slug history, redirects, search, pages, homepage sections, sitemap runs
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS seo_meta (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('home','content','category','tag','performer','studio','series','page','search_landing') NOT NULL,
  entity_id BIGINT UNSIGNED NULL,
  language_code VARCHAR(12) NOT NULL DEFAULT 'default',
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  meta_keywords VARCHAR(500) NULL,
  canonical_url VARCHAR(500) NULL,
  og_title VARCHAR(255) NULL,
  og_description VARCHAR(500) NULL,
  og_image_url VARCHAR(500) NULL,
  robots_directive ENUM('index_follow','noindex_follow','noindex_nofollow') NOT NULL DEFAULT 'index_follow',
  schema_json JSON NULL,
  updated_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_seo_entity_lang (entity_type, entity_id, language_code),
  KEY idx_seo_entity (entity_type, entity_id),
  CONSTRAINT fk_seo_admin FOREIGN KEY (updated_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS slug_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity_type ENUM('content','category','tag','performer','studio','series','page') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  old_slug VARCHAR(220) NOT NULL,
  new_slug VARCHAR(220) NOT NULL,
  redirect_status SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_slug_history_old (entity_type, old_slug),
  KEY idx_slug_history_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS redirects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_path VARCHAR(500) NOT NULL,
  target_url VARCHAR(500) NOT NULL,
  status_code SMALLINT UNSIGNED NOT NULL DEFAULT 301,
  hit_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_redirect_source_path (source_path),
  KEY idx_redirect_active (is_active),
  CONSTRAINT fk_redirect_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  guest_session_id BIGINT UNSIGNED NULL,
  query_text VARCHAR(255) NOT NULL,
  normalized_query VARCHAR(255) NOT NULL,
  result_count INT UNSIGNED NOT NULL DEFAULT 0,
  clicked_content_id BIGINT UNSIGNED NULL,
  device ENUM('desktop','mobile','tablet','unknown') NOT NULL DEFAULT 'unknown',
  ip_hash CHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_search_normalized_time (normalized_query, created_at),
  KEY idx_search_zero_results (result_count, created_at),
  CONSTRAINT fk_search_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_search_guest FOREIGN KEY (guest_session_id) REFERENCES guest_sessions(id) ON DELETE SET NULL,
  CONSTRAINT fk_search_content FOREIGN KEY (clicked_content_id) REFERENCES content_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS search_suggestions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  suggestion_text VARCHAR(255) NOT NULL,
  normalized_text VARCHAR(255) NOT NULL,
  target_type ENUM('query','category','tag','performer','studio','content','redirect') NOT NULL DEFAULT 'query',
  target_id BIGINT UNSIGNED NULL,
  target_url VARCHAR(500) NULL,
  total_searches BIGINT UNSIGNED NOT NULL DEFAULT 0,
  priority INT UNSIGNED NOT NULL DEFAULT 100,
  status ENUM('active','hidden','disabled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_search_suggest_norm (normalized_text, target_type, target_id),
  KEY idx_search_suggest_status_priority (status, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(120) NOT NULL,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(220) NOT NULL,
  body LONGTEXT NULL,
  sanitized_body LONGTEXT NULL,
  page_type ENUM('static','legal','compliance','advertise','webmaster','faq','contact','landing') NOT NULL DEFAULT 'static',
  status ENUM('draft','published','hidden','archived') NOT NULL DEFAULT 'draft',
  published_at DATETIME NULL,
  updated_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_pages_key (page_key),
  UNIQUE KEY uq_pages_slug (slug),
  KEY idx_pages_type_status (page_type, status),
  FULLTEXT KEY ft_pages_search (title, sanitized_body),
  CONSTRAINT fk_pages_admin FOREIGN KEY (updated_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  body LONGTEXT NULL,
  sanitized_body LONGTEXT NULL,
  revision_note VARCHAR(255) NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_page_revisions_page (page_id, created_at),
  CONSTRAINT fk_page_rev_page FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
  CONSTRAINT fk_page_rev_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_sections (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_key VARCHAR(120) NOT NULL,
  title VARCHAR(160) NOT NULL,
  section_type ENUM('featured','latest','trending','most_viewed','most_liked','category','tag','performer','studio','manual','continue_watching','recommended','membership_promo') NOT NULL,
  source_config_json JSON NULL,
  device_visibility ENUM('all','desktop','mobile') NOT NULL DEFAULT 'all',
  status ENUM('active','hidden','disabled') NOT NULL DEFAULT 'active',
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_home_section_key (section_key),
  KEY idx_home_sections_status_sort (status, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS homepage_section_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  section_id BIGINT UNSIGNED NOT NULL,
  content_id BIGINT UNSIGNED NULL,
  entity_type ENUM('content','category','tag','performer','studio','series','custom') NOT NULL DEFAULT 'content',
  entity_id BIGINT UNSIGNED NULL,
  custom_title VARCHAR(255) NULL,
  custom_url VARCHAR(500) NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_home_items_section_sort (section_id, sort_order),
  CONSTRAINT fk_home_item_section FOREIGN KEY (section_id) REFERENCES homepage_sections(id) ON DELETE CASCADE,
  CONSTRAINT fk_home_item_content FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sitemap_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sitemap_type ENUM('main','video','category','tag','performer','studio','series','rss') NOT NULL,
  status ENUM('queued','running','completed','failed') NOT NULL DEFAULT 'queued',
  file_path VARCHAR(500) NULL,
  url_count INT UNSIGNED NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sitemap_type_status (sitemap_type, status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
