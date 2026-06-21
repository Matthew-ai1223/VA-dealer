-- VA Auto Sales - Stage 2: Lead Management & CRM
-- Run via setup.php or import manually after Stage 1 schema

USE va_aut_sales;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  phone_number VARCHAR(30) NOT NULL,
  email VARCHAR(120) DEFAULT NULL,
  car_id INT UNSIGNED DEFAULT NULL,
  interested_vehicle VARCHAR(200) NOT NULL,
  budget DECIMAL(12, 2) DEFAULT NULL,
  inquiry_type ENUM('request_info', 'book_inspection', 'request_callback', 'whatsapp') NOT NULL DEFAULT 'request_info',
  source ENUM('website', 'nairaland', 'instagram', 'facebook', 'whatsapp') NOT NULL DEFAULT 'website',
  status ENUM('new', 'contacted', 'interested', 'negotiating', 'closed_won', 'closed_lost') NOT NULL DEFAULT 'new',
  message TEXT,
  assigned_to VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_phone (phone_number),
  INDEX idx_source (source),
  INDEX idx_status (status),
  INDEX idx_created (created_at),
  INDEX idx_car (car_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lead_notes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NOT NULL,
  admin_username VARCHAR(50) NOT NULL,
  note TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lead (lead_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS lead_activities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED DEFAULT NULL,
  car_id INT UNSIGNED DEFAULT NULL,
  activity_type ENUM(
    'vehicle_viewed',
    'vehicle_inquiry',
    'contact_request',
    'inspection_request',
    'callback_request',
    'whatsapp_click',
    'interest_click'
  ) NOT NULL,
  meta JSON DEFAULT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lead (lead_id),
  INDEX idx_car (car_id),
  INDEX idx_type (activity_type),
  INDEX idx_created (created_at)
) ENGINE=InnoDB;
