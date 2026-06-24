-- VA Auto Sales — Firewall & Security Events Tables

CREATE TABLE IF NOT EXISTS firewall_blocks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  reason VARCHAR(200) NOT NULL DEFAULT 'manual',
  hit_count INT UNSIGNED NOT NULL DEFAULT 1,
  blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_ip (ip),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS security_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  event_type VARCHAR(60) NOT NULL,
  detail VARCHAR(500) DEFAULT NULL,
  url VARCHAR(500) DEFAULT NULL,
  user_agent VARCHAR(400) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip),
  INDEX idx_event_type (event_type),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;
