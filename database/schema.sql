-- VA Auto Sales - Stage 1 Database Schema
-- Run via setup.php or import manually in phpMyAdmin

CREATE DATABASE IF NOT EXISTS va_aut_sales
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE va_aut_sales;

-- Admin users (Stage 2: extend for roles/permissions)
CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Car listings (Stage 2: add lead_id, whatsapp_thread_id for automation)
CREATE TABLE IF NOT EXISTS cars (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  brand VARCHAR(80) NOT NULL,
  model VARCHAR(80) NOT NULL,
  year YEAR NOT NULL,
  price DECIMAL(12, 2) NOT NULL,
  description TEXT,
  specs JSON,
  images JSON,
  status ENUM('available', 'sold') NOT NULL DEFAULT 'available',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_brand (brand),
  INDEX idx_price (price),
  INDEX idx_year (year),
  INDEX idx_featured (featured)
) ENGINE=InnoDB;

-- Default admin: vaautosales / vaautosales123
INSERT INTO admins (username, password_hash) VALUES
('vaautosales', '$2y$10$l2z1u5LiOStYvWnL/W8.vemE1xmHFS.8e6UaENXw3Kd8MLNuwDeOm')
ON DUPLICATE KEY UPDATE username = username;

-- Sample listings for demo
INSERT INTO cars (title, brand, model, year, price, description, specs, images, status, featured) VALUES
(
  '2022 Toyota Camry XSE',
  'Toyota',
  'Camry',
  2022,
  18500000.00,
  'Immaculate Toyota Camry XSE with full service history. Single owner, accident-free, and ready to drive.',
  '{"mileage":"45,000 km","transmission":"Automatic","fuel":"Petrol","color":"Pearl White","engine":"2.5L"}',
  '["sample-camry.jpg"]',
  'available',
  1
),
(
  '2021 Honda Accord Sport',
  'Honda',
  'Accord',
  2021,
  16200000.00,
  'Sport trim with premium interior. Excellent fuel economy and smooth ride quality.',
  '{"mileage":"38,000 km","transmission":"Automatic","fuel":"Petrol","color":"Black","engine":"1.5L Turbo"}',
  '["sample-accord.jpg"]',
  'available',
  1
),
(
  '2020 Mercedes-Benz C300',
  'Mercedes-Benz',
  'C300',
  2020,
  24500000.00,
  'Luxury sedan with panoramic roof, leather seats, and advanced safety features.',
  '{"mileage":"52,000 km","transmission":"Automatic","fuel":"Petrol","color":"Silver","engine":"2.0L Turbo"}',
  '["sample-mercedes.jpg"]',
  'available',
  0
),
(
  '2019 Lexus RX 350',
  'Lexus',
  'RX 350',
  2019,
  22800000.00,
  'Premium SUV with AWD, navigation, and heated seats. Well maintained.',
  '{"mileage":"60,000 km","transmission":"Automatic","fuel":"Petrol","color":"Graphite","engine":"3.5L V6"}',
  '["sample-lexus.jpg"]',
  'sold',
  0
);
