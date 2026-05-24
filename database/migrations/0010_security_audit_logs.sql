-- 0010 Security: bans, login attempts, rate limits, audit logs, error logs, security events
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS bans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ban_type ENUM('ip_hash','user','email','domain','user_agent_hash','country') NOT NULL,
  ban_value VARCHAR(255) NOT NULL,
  reason TEXT NULL,
  status ENUM('active','expired','disabled') NOT NULL DEFAULT 'active',
  expires_at DATETIME NULL,
  created_by_admin_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ban_type_value (ban_type, ban_value),
  KEY idx_bans_status_expiry (status, expires_at),
  CONSTRAINT fk_ban_admin FOREIGN KEY (created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(190) NULL,
  user_type ENUM('frontend_user','superadmin') NOT NULL,
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  failure_reason VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_attempts_identifier_time (identifier, created_at),
  KEY idx_login_attempts_ip_time (ip_hash, created_at),
  KEY idx_login_attempts_type_success (user_type, success, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limit_hits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  limiter_key VARCHAR(160) NOT NULL,
  subject_hash CHAR(64) NOT NULL,
  hit_count INT UNSIGNED NOT NULL DEFAULT 1,
  window_starts_at DATETIME NOT NULL,
  window_ends_at DATETIME NOT NULL,
  blocked_until DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rate_limit_window (limiter_key, subject_hash, window_starts_at),
  KEY idx_rate_limit_blocked (blocked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NULL,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(160) NOT NULL,
  entity_type VARCHAR(80) NULL,
  entity_id BIGINT UNSIGNED NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_admin_time (admin_id, created_at),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_action_time (action, created_at),
  CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS error_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  level ENUM('debug','info','notice','warning','error','critical') NOT NULL DEFAULT 'error',
  channel VARCHAR(80) NOT NULL DEFAULT 'app',
  message TEXT NOT NULL,
  context_json JSON NULL,
  file_path VARCHAR(500) NULL,
  line_number INT UNSIGNED NULL,
  request_id VARCHAR(80) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_error_level_channel_time (level, channel, created_at),
  KEY idx_error_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS security_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_type ENUM('csrf_failed','xss_blocked','rate_limited','login_blocked','ban_triggered','hotlink_blocked','invalid_embed_domain','api_auth_failed','suspicious_import','other') NOT NULL,
  severity ENUM('info','warning','error','critical') NOT NULL DEFAULT 'warning',
  user_id BIGINT UNSIGNED NULL,
  admin_id BIGINT UNSIGNED NULL,
  ip_hash CHAR(64) NULL,
  user_agent_hash CHAR(64) NULL,
  request_path VARCHAR(500) NULL,
  context_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_security_events_type_time (event_type, created_at),
  KEY idx_security_events_severity_time (severity, created_at),
  KEY idx_security_events_ip_time (ip_hash, created_at),
  CONSTRAINT fk_security_event_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_security_event_admin FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
