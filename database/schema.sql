CREATE DATABASE IF NOT EXISTS phoohotshu
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE phoohotshu;

CREATE TABLE IF NOT EXISTS rfqs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_no VARCHAR(32) NOT NULL,
  status ENUM(
    'new',
    'reviewing',
    'need_more_info',
    'site_visit',
    'quoted',
    'follow_up',
    'won',
    'lost'
  ) NOT NULL DEFAULT 'new',

  customer_name VARCHAR(120) NOT NULL,
  contact_value VARCHAR(190) NOT NULL,
  contact_type ENUM('phone','email','other') NOT NULL DEFAULT 'other',

  city VARCHAR(80) NULL,
  district VARCHAR(80) NULL,
  address_text VARCHAR(255) NULL,
  area_ping DECIMAL(8,2) NULL,
  space_type VARCHAR(80) NULL,
  request_type VARCHAR(120) NULL,
  product_interest VARCHAR(120) NULL,

  existing_floor VARCHAR(120) NULL,
  floor_issue VARCHAR(160) NULL,
  furniture_condition VARCHAR(120) NULL,
  preferred_timing VARCHAR(120) NULL,
  customer_note TEXT NULL,

  landing_page VARCHAR(255) NULL,
  source_page VARCHAR(255) NULL,
  referrer_url VARCHAR(500) NULL,
  utm_source VARCHAR(120) NULL,
  utm_medium VARCHAR(120) NULL,
  utm_campaign VARCHAR(160) NULL,
  utm_content VARCHAR(160) NULL,
  utm_term VARCHAR(160) NULL,

  consent_privacy TINYINT(1) NOT NULL DEFAULT 0,
  consent_case_publication TINYINT(1) NOT NULL DEFAULT 0,

  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(500) NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_rfqs_rfq_no (rfq_no),
  KEY idx_rfqs_status_created (status, created_at),
  KEY idx_rfqs_city_district (city, district),
  KEY idx_rfqs_utm_source (utm_source)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rfq_photos (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_id BIGINT UNSIGNED NOT NULL,
  stored_name VARCHAR(190) NOT NULL,
  original_name VARCHAR(255) NULL,
  mime_type VARCHAR(80) NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  width_px INT UNSIGNED NULL,
  height_px INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_rfq_photos_rfq_id (rfq_id),
  UNIQUE KEY uq_rfq_photos_sha256_per_rfq (rfq_id, sha256),
  CONSTRAINT fk_rfq_photos_rfq
    FOREIGN KEY (rfq_id) REFERENCES rfqs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rfq_status_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  rfq_id BIGINT UNSIGNED NOT NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  note VARCHAR(500) NULL,
  changed_by VARCHAR(120) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  KEY idx_status_history_rfq_id (rfq_id),
  CONSTRAINT fk_status_history_rfq
    FOREIGN KEY (rfq_id) REFERENCES rfqs(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS request_rate_limits (
  bucket_key CHAR(64) NOT NULL,
  window_started_at DATETIME NOT NULL,
  request_count INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (bucket_key)
) ENGINE=InnoDB;
