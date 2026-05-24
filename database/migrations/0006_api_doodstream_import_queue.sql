-- 0006 API credentials, Doodstream sync, import system, queue, cron
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS api_credentials (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider ENUM('doodstream','smtp','analytics','other') NOT NULL,
  credential_name VARCHAR(120) NOT NULL,
  encrypted_value TEXT NOT NULL,
  masked_value VARCHAR(160) NULL,
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  last_test_status ENUM('unknown','success','failed') NOT NULL DEFAULT 'unknown',
  last_test_message TEXT NULL,
  last_tested_at DATETIME NULL,
  updated_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_api_provider_name (provider, credential_name),
  KEY idx_api_provider_status (provider, status),
  CONSTRAINT fk_api_admin FOREIGN KEY (updated_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doodstream_files (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_code VARCHAR(120) NOT NULL,
  folder_id VARCHAR(120) NULL,
  title VARCHAR(255) NULL,
  remote_status VARCHAR(80) NULL,
  remote_size_bytes BIGINT UNSIGNED NULL,
  remote_thumbnail_url VARCHAR(500) NULL,
  remote_embed_url VARCHAR(500) NULL,
  linked_content_id BIGINT UNSIGNED NULL,
  last_synced_at DATETIME NULL,
  raw_payload_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dood_file_code (file_code),
  KEY idx_dood_folder (folder_id),
  KEY idx_dood_linked_content (linked_content_id),
  CONSTRAINT fk_dood_content FOREIGN KEY (linked_content_id) REFERENCES content_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS queue_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  queue_name VARCHAR(80) NOT NULL DEFAULT 'default',
  job_type VARCHAR(120) NOT NULL,
  payload_json JSON NOT NULL,
  status ENUM('pending','running','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  max_attempts INT UNSIGNED NOT NULL DEFAULT 3,
  available_at DATETIME NOT NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  failed_at DATETIME NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_queue_ready (queue_name, status, available_at),
  KEY idx_queue_type_status (job_type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS doodstream_sync_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sync_type ENUM('manual','cron','single_file','folder','health_check','retry') NOT NULL DEFAULT 'manual',
  status ENUM('queued','running','success','partial','partial_failed','failed','cancelled') NOT NULL DEFAULT 'queued',
  folder_id VARCHAR(120) NULL,
  file_code VARCHAR(120) NULL,
  total_files INT UNSIGNED NOT NULL DEFAULT 0,
  created_files INT UNSIGNED NOT NULL DEFAULT 0,
  updated_files INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_files INT UNSIGNED NOT NULL DEFAULT 0,
  missing_files INT UNSIGNED NOT NULL DEFAULT 0,
  failed_files INT UNSIGNED NOT NULL DEFAULT 0,
  error_message TEXT NULL,
  payload_json JSON NULL,
  started_by_admin_id BIGINT UNSIGNED NULL,
  queue_job_id BIGINT UNSIGNED NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_dood_sync_status_time (status, created_at),
  KEY idx_dood_sync_type_time (sync_type, created_at),
  KEY idx_dood_sync_file_code (file_code),
  CONSTRAINT fk_dood_sync_admin FOREIGN KEY (started_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_dood_sync_queue FOREIGN KEY (queue_job_id) REFERENCES queue_jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_mapping_presets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  import_type ENUM('json_metadata','csv_metadata','doodstream_sync') NOT NULL,
  mapping_json JSON NOT NULL,
  duplicate_strategy ENUM('skip','insert_only','update_existing','insert_update') NOT NULL DEFAULT 'insert_update',
  default_lifecycle_status ENUM('draft','pending_review','published') NOT NULL DEFAULT 'pending_review',
  default_access_level ENUM('public','registered','premium','vip') NOT NULL DEFAULT 'public',
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_import_preset_type (import_type),
  CONSTRAINT fk_import_preset_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_code VARCHAR(80) NOT NULL,
  import_type ENUM('json_metadata','csv_metadata','doodstream_sync') NOT NULL,
  preset_id BIGINT UNSIGNED NULL,
  original_filename VARCHAR(255) NULL,
  status ENUM('uploaded','previewed','queued','processing','completed','failed','rolled_back') NOT NULL DEFAULT 'uploaded',
  total_rows INT UNSIGNED NOT NULL DEFAULT 0,
  success_rows INT UNSIGNED NOT NULL DEFAULT 0,
  failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
  duplicate_rows INT UNSIGNED NOT NULL DEFAULT 0,
  created_by_admin_id BIGINT UNSIGNED NULL,
  started_at DATETIME NULL,
  completed_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_import_batch_code (batch_code),
  KEY idx_import_batch_status (status, created_at),
  CONSTRAINT fk_import_batch_preset FOREIGN KEY (preset_id) REFERENCES import_mapping_presets(id) ON DELETE SET NULL,
  CONSTRAINT fk_import_batch_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS import_rows (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  row_number INT UNSIGNED NOT NULL,
  source_code VARCHAR(160) NULL,
  source_slug VARCHAR(220) NULL,
  raw_payload_json JSON NULL,
  mapped_payload_json JSON NULL,
  status ENUM('pending','validated','imported','skipped_duplicate','failed','rolled_back') NOT NULL DEFAULT 'pending',
  linked_content_id BIGINT UNSIGNED NULL,
  error_message TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_import_row (batch_id, row_number),
  KEY idx_import_rows_status (batch_id, status),
  KEY idx_import_source_code (source_code),
  CONSTRAINT fk_import_row_batch FOREIGN KEY (batch_id) REFERENCES import_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_import_row_content FOREIGN KEY (linked_content_id) REFERENCES content_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_schedules (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_key VARCHAR(120) NOT NULL,
  job_name VARCHAR(160) NOT NULL,
  cron_expression VARCHAR(80) NOT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  last_run_at DATETIME NULL,
  next_run_at DATETIME NULL,
  last_status ENUM('unknown','success','failed','running') NOT NULL DEFAULT 'unknown',
  config_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_cron_job_key (job_key),
  KEY idx_cron_enabled_next (is_enabled, next_run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
