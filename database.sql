-- ============================================================
-- PDAM Anomaly Detection System — Database Schema v3 (Complete)
-- Import: phpMyAdmin > Import > pilih file ini > Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS pdam_anomaly
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pdam_anomaly;

-- ============================================================
-- Tabel: users
-- Sistem autentikasi dengan role
-- ============================================================
DROP TABLE IF EXISTS analysis_results;
DROP TABLE IF EXISTS analysis_history;
DROP TABLE IF EXISTS raw_data;
DROP TABLE IF EXISTS uploads;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
  id          INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  username    VARCHAR(50)       NOT NULL,
  email       VARCHAR(150)      NOT NULL,
  password    VARCHAR(255)      NOT NULL,
  full_name   VARCHAR(100)      DEFAULT NULL,
  role        ENUM('admin','user') DEFAULT 'user',
  is_active   TINYINT(1)        NOT NULL DEFAULT 1,
  created_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_username (username),
  UNIQUE KEY uq_email    (email),
  KEY idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tabel: uploads
-- Setiap file Excel yang diupload dicatat di sini
-- ============================================================
CREATE TABLE IF NOT EXISTS uploads (
  id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED      DEFAULT NULL,
  uuid            VARCHAR(36)       NOT NULL,
  original_name   VARCHAR(255)      NOT NULL,
  stored_name     VARCHAR(255)      NOT NULL,
  file_size       BIGINT UNSIGNED   DEFAULT 0,
  file_type       VARCHAR(50)       DEFAULT NULL,
  total_rows      INT UNSIGNED      DEFAULT 0,
  tahun_min       SMALLINT          DEFAULT NULL,
  tahun_max       SMALLINT          DEFAULT NULL,
  golongan_list   TEXT              DEFAULT NULL,
  is_processed    TINYINT(1)        NOT NULL DEFAULT 0,
  uploaded_at     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_uuid (uuid),
  KEY idx_user_id  (user_id),
  KEY idx_uploaded (uploaded_at),
  KEY idx_is_processed (is_processed),
  CONSTRAINT fk_upload_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tabel: raw_data
-- Data mentah dari Excel (PDAM: tahun, bulan, golongan, rp, m3)
-- ============================================================
CREATE TABLE IF NOT EXISTS raw_data (
  id              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  upload_id       INT UNSIGNED      NOT NULL,
  tahun           SMALLINT          NOT NULL,
  bulan           VARCHAR(20)       NOT NULL,
  bulan_num       TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  golongan        VARCHAR(10)       NOT NULL,
  nama_golongan   VARCHAR(100)      DEFAULT NULL,
  rp              BIGINT            NOT NULL DEFAULT 0,
  m3              INT               NOT NULL DEFAULT 0,
  rp_per_m3       DECIMAL(15,4)     NOT NULL DEFAULT 0.0000,
  created_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- UNIQUE constraint untuk mencegah duplikasi data per upload + tahun + bulan + golongan
  UNIQUE KEY uq_upload_data (upload_id, tahun, bulan_num, golongan),
  KEY idx_upload_id (upload_id),
  KEY idx_golongan  (golongan),
  KEY idx_tahun     (tahun),
  KEY idx_bulan_num (bulan_num),
  KEY idx_tahun_gol (tahun, golongan),
  KEY idx_tahun_bulan (tahun, bulan_num),
  CONSTRAINT fk_raw_upload
    FOREIGN KEY (upload_id) REFERENCES uploads(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tabel: analysis_history
-- Setiap kali user run analysis (Isolation Forest)
-- ============================================================
CREATE TABLE IF NOT EXISTS analysis_history (
  id              INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED      NOT NULL,
  upload_id       INT UNSIGNED      DEFAULT NULL,
  filter_mode     VARCHAR(60)       NOT NULL DEFAULT 'near_tahun_per_golongan',
  contamination   VARCHAR(10)       NOT NULL DEFAULT '0.05',
  n_estimators    INT UNSIGNED      DEFAULT 100,
  tahun_min       SMALLINT          DEFAULT NULL,
  tahun_max       SMALLINT          DEFAULT NULL,
  golongan_filter VARCHAR(20)       DEFAULT NULL,
  jumlah_data     INT UNSIGNED      DEFAULT 0,
  jumlah_anomali  INT UNSIGNED      DEFAULT 0,
  persentase      DECIMAL(5,2)      DEFAULT 0.00,
  status          ENUM('running','completed','failed') DEFAULT 'completed',
  filename        VARCHAR(255)      DEFAULT NULL,
  created_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_id    (user_id),
  KEY idx_upload_id  (upload_id),
  KEY idx_created_at (created_at),
  KEY idx_status     (status),
  CONSTRAINT fk_history_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_history_upload
    FOREIGN KEY (upload_id) REFERENCES uploads(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tabel: analysis_results
-- Hasil Isolation Forest per record (satu row = satu raw_data)
-- ============================================================
CREATE TABLE IF NOT EXISTS analysis_results (
  id              BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  raw_data_id     BIGINT UNSIGNED   NOT NULL,
  upload_id       INT UNSIGNED      NOT NULL,
  history_id      INT UNSIGNED      DEFAULT NULL,
  is_anomaly      TINYINT(1)        NOT NULL DEFAULT 0,
  anomaly_score   DECIMAL(10,6)     NOT NULL DEFAULT 0.000000,
  anomaly_level   ENUM('normal','low','medium','high') NOT NULL DEFAULT 'normal',
  z_score         DECIMAL(10,4)     DEFAULT NULL,
  causes_json     JSON              DEFAULT NULL,
  created_at      DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME          DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- UNIQUE: satu raw_data hanya punya satu hasil analisis per upload
  UNIQUE KEY uq_raw_upload (raw_data_id, upload_id),
  KEY idx_raw_data   (raw_data_id),
  KEY idx_upload     (upload_id),
  KEY idx_history    (history_id),
  KEY idx_is_anomaly (is_anomaly),
  KEY idx_level      (anomaly_level),
  KEY idx_score      (anomaly_score),
  CONSTRAINT fk_result_raw
    FOREIGN KEY (raw_data_id) REFERENCES raw_data(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_result_upload
    FOREIGN KEY (upload_id) REFERENCES uploads(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_result_history
    FOREIGN KEY (history_id) REFERENCES analysis_history(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Tabel: audit_log (optional - untuk tracking aktivitas)
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_log (
  id          BIGINT UNSIGNED   NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED      DEFAULT NULL,
  action      VARCHAR(50)       NOT NULL,
  entity_type VARCHAR(50)       DEFAULT NULL,
  entity_id   BIGINT UNSIGNED   DEFAULT NULL,
  details     JSON              DEFAULT NULL,
  ip_address  VARCHAR(45)       DEFAULT NULL,
  created_at  TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_id (user_id),
  KEY idx_action  (action),
  KEY idx_created (created_at),
  CONSTRAINT fk_audit_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Default Users (password: admin123)
-- ============================================================
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
  ('admin', 'admin@pdam.id', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMaBfNItzqYmMCdX0A3Q2mwzGm', 'Administrator', 'admin', 1),
  ('demo', 'demo@pdam.id', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMaBfNItzqYmMCdX0A3Q2mwzGm', 'Demo User', 'user', 1);

-- ============================================================
-- Views untuk query yang sering digunakan
-- ============================================================

-- View: summary per upload
CREATE OR REPLACE VIEW v_upload_summary AS
SELECT 
    u.id as upload_id,
    u.original_name,
    u.total_rows,
    COUNT(DISTINCT rd.golongan) as jumlah_golongan,
    COUNT(DISTINCT rd.tahun) as jumlah_tahun,
    SUM(rd.rp) as total_rp,
    SUM(rd.m3) as total_m3,
    AVG(rd.rp_per_m3) as avg_rp_per_m3,
    u.uploaded_at
FROM uploads u
LEFT JOIN raw_data rd ON u.id = rd.upload_id
GROUP BY u.id;

-- View: anomaly summary per analysis
CREATE OR REPLACE VIEW v_anomaly_summary AS
SELECT 
    ah.id as history_id,
    ah.user_id,
    ah.upload_id,
    ah.filter_mode,
    ah.contamination,
    ah.jumlah_data,
    ah.jumlah_anomali,
    ah.persentase,
    ah.status,
    ah.created_at,
    COUNT(CASE WHEN ar.anomaly_level = 'high' THEN 1 END) as high_count,
    COUNT(CASE WHEN ar.anomaly_level = 'medium' THEN 1 END) as medium_count,
    COUNT(CASE WHEN ar.anomaly_level = 'low' THEN 1 END) as low_count
FROM analysis_history ah
LEFT JOIN analysis_results ar ON ah.id = ar.history_id
GROUP BY ah.id;
