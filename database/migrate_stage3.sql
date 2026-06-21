-- VA Auto Sales - Stage 3 Migration
-- Adds Lead Scoring, Lead Nurturing, Visitor Tracking, and Email Campaign tables

USE va_aut_sales;

-- Visitors Table for Tracking
CREATE TABLE IF NOT EXISTS visitors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_token VARCHAR(100) NOT NULL UNIQUE,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent TEXT DEFAULT NULL,
  utm_source VARCHAR(100) DEFAULT NULL,
  session_duration INT DEFAULT 0,
  is_returning TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_token (visitor_token),
  INDEX idx_utm (utm_source),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Email Campaigns Table
CREATE TABLE IF NOT EXISTS email_campaigns (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body TEXT NOT NULL,
  template_type VARCHAR(50) NOT NULL,
  audience_segment VARCHAR(50) NOT NULL,
  status ENUM('draft', 'sent') DEFAULT 'draft',
  sent_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Email Logs Table
CREATE TABLE IF NOT EXISTS email_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  campaign_id INT UNSIGNED DEFAULT NULL,
  lead_id INT UNSIGNED DEFAULT NULL,
  email VARCHAR(120) NOT NULL,
  status ENUM('sent', 'failed') DEFAULT 'sent',
  sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_campaign (campaign_id),
  INDEX idx_lead (lead_id)
) ENGINE=InnoDB;
