-- ============================================================
-- PDAM Anomaly Detection System — Database Schema v3 (PostgreSQL)
-- Import: psql -U postgres -d pdam_anomaly -f database_postgres.sql
-- Atau jalankan via phpMyAdmin/pgAdmin
-- ============================================================

-- Hapus tabel kalau sudah ada (urutan: child dulu, parent belakang)
DROP TABLE IF EXISTS analysis_results CASCADE;
DROP TABLE IF EXISTS analysis_history CASCADE;
DROP TABLE IF EXISTS raw_data CASCADE;
DROP TABLE IF EXISTS uploads CASCADE;
DROP TABLE IF EXISTS users CASCADE;
DROP TABLE IF EXISTS audit_log CASCADE;

-- Hapus tipe ENUM kalau sudah ada
DROP TYPE IF EXISTS user_role CASCADE;
DROP TYPE IF EXISTS anomaly_level CASCADE;
DROP TYPE IF EXISTS analysis_status CASCADE;

-- ============================================================
-- Tipe ENUM
-- ============================================================
CREATE TYPE user_role AS ENUM ('admin', 'user');
CREATE TYPE anomaly_level AS ENUM ('normal', 'low', 'medium', 'high');
CREATE TYPE analysis_status AS ENUM ('running', 'completed', 'failed');

-- ============================================================
-- Tabel: users
-- ============================================================
CREATE TABLE users (
  id          SERIAL PRIMARY KEY,
  username    VARCHAR(50)       NOT NULL,
  email       VARCHAR(150)      NOT NULL,
  password    VARCHAR(255)      NOT NULL,
  full_name   VARCHAR(100)      DEFAULT NULL,
  role        user_role         NOT NULL DEFAULT 'user',
  is_active   BOOLEAN           NOT NULL DEFAULT TRUE,
  created_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_username UNIQUE (username),
  CONSTRAINT uq_email UNIQUE (email)
);

CREATE INDEX idx_users_is_active ON users(is_active);

-- ============================================================
-- Tabel: uploads
-- ============================================================
CREATE TABLE uploads (
  id              SERIAL PRIMARY KEY,
  user_id         INT               DEFAULT NULL,
  uuid            VARCHAR(36)       NOT NULL,
  original_name   VARCHAR(255)      NOT NULL,
  stored_name     VARCHAR(255)      NOT NULL,
  file_size       BIGINT            DEFAULT 0,
  file_type       VARCHAR(50)       DEFAULT NULL,
  total_rows      INT               DEFAULT 0,
  tahun_min       SMALLINT          DEFAULT NULL,
  tahun_max       SMALLINT          DEFAULT NULL,
  golongan_list   TEXT              DEFAULT NULL,
  is_processed    BOOLEAN           NOT NULL DEFAULT FALSE,
  uploaded_at     TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_uuid UNIQUE (uuid),
  CONSTRAINT fk_upload_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_uploads_user_id ON uploads(user_id);
CREATE INDEX idx_uploads_uploaded ON uploads(uploaded_at);
CREATE INDEX idx_uploads_is_processed ON uploads(is_processed);

-- ============================================================
-- Tabel: raw_data
-- ============================================================
CREATE TABLE raw_data (
  id              BIGSERIAL PRIMARY KEY,
  upload_id       INT               NOT NULL,
  tahun           SMALLINT          NOT NULL,
  bulan           VARCHAR(20)       NOT NULL,
  bulan_num       SMALLINT          NOT NULL DEFAULT 0,
  golongan        VARCHAR(10)       NOT NULL,
  nama_golongan   VARCHAR(100)      DEFAULT NULL,
  rp              BIGINT            NOT NULL DEFAULT 0,
  m3              INT               NOT NULL DEFAULT 0,
  rp_per_m3       DECIMAL(15,4)     NOT NULL DEFAULT 0.0000,
  created_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_upload_data UNIQUE (upload_id, tahun, bulan_num, golongan),
  CONSTRAINT fk_raw_upload
    FOREIGN KEY (upload_id) REFERENCES uploads(id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE INDEX idx_raw_upload_id ON raw_data(upload_id);
CREATE INDEX idx_raw_golongan ON raw_data(golongan);
CREATE INDEX idx_raw_tahun ON raw_data(tahun);
CREATE INDEX idx_raw_bulan_num ON raw_data(bulan_num);
CREATE INDEX idx_raw_tahun_gol ON raw_data(tahun, golongan);
CREATE INDEX idx_raw_tahun_bulan ON raw_data(tahun, bulan_num);

-- ============================================================
-- Tabel: analysis_history
-- ============================================================
CREATE TABLE analysis_history (
  id              SERIAL PRIMARY KEY,
  user_id         INT               NOT NULL,
  upload_id       INT               DEFAULT NULL,
  filter_mode     VARCHAR(60)       NOT NULL DEFAULT 'near_tahun_per_golongan',
  contamination   VARCHAR(10)       NOT NULL DEFAULT '0.05',
  n_estimators    INT               DEFAULT 100,
  tahun_min       SMALLINT          DEFAULT NULL,
  tahun_max       SMALLINT          DEFAULT NULL,
  golongan_filter VARCHAR(20)       DEFAULT NULL,
  jumlah_data     INT               DEFAULT 0,
  jumlah_anomali  INT               DEFAULT 0,
  persentase      DECIMAL(5,2)      DEFAULT 0.00,
  status          analysis_status   DEFAULT 'completed',
  filename        VARCHAR(255)      DEFAULT NULL,
  created_at      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_history_upload
    FOREIGN KEY (upload_id) REFERENCES uploads(id)
    ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_history_user_id ON analysis_history(user_id);
CREATE INDEX idx_history_upload_id ON analysis_history(upload_id);
CREATE INDEX idx_history_created ON analysis_history(created_at);
CREATE INDEX idx_history_status ON analysis_history(status);

-- ============================================================
-- Tabel: analysis_results
-- ============================================================
CREATE TABLE analysis_results (
  id              BIGSERIAL PRIMARY KEY,
  raw_data_id     BIGINT            NOT NULL,
  upload_id       INT               NOT NULL,
  history_id      INT               DEFAULT NULL,
  is_anomaly      BOOLEAN           NOT NULL DEFAULT FALSE,
  anomaly_score   DECIMAL(10,6)     NOT NULL DEFAULT 0.000000,
  anomaly_level   anomaly_level     NOT NULL DEFAULT 'normal',
  z_score         DECIMAL(10,4)     DEFAULT NULL,
  causes_json     JSONB             DEFAULT NULL,
  created_at      TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_raw_upload UNIQUE (raw_data_id, upload_id),
  CONSTRAINT fk_result_raw
    FOREIGN KEY (raw_data_id) REFERENCES raw_data(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_result_upload
    FOREIGN KEY (upload_id) REFERENCES uploads(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_result_history
    FOREIGN KEY (history_id) REFERENCES analysis_history(id)
    ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE INDEX idx_results_raw_data ON analysis_results(raw_data_id);
CREATE INDEX idx_results_upload ON analysis_results(upload_id);
CREATE INDEX idx_results_history ON analysis_results(history_id);
CREATE INDEX idx_results_is_anomaly ON analysis_results(is_anomaly);
CREATE INDEX idx_results_level ON analysis_results(anomaly_level);
CREATE INDEX idx_results_score ON analysis_results(anomaly_score);

-- ============================================================
-- Tabel: audit_log (optional)
-- ============================================================
CREATE TABLE audit_log (
  id          BIGSERIAL PRIMARY KEY,
  user_id     INT               DEFAULT NULL,
  action      VARCHAR(50)       NOT NULL,
  entity_type VARCHAR(50)       DEFAULT NULL,
  entity_id   BIGINT            DEFAULT NULL,
  details     JSONB             DEFAULT NULL,
  ip_address  VARCHAR(45)       DEFAULT NULL,
  created_at  TIMESTAMP         DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE SET NULL
);

CREATE INDEX idx_audit_user_id ON audit_log(user_id);
CREATE INDEX idx_audit_action ON audit_log(action);
CREATE INDEX idx_audit_created ON audit_log(created_at);

-- ============================================================
-- Default Users (password: admin123)
-- ============================================================
INSERT INTO users (username, email, password, full_name, role, is_active) VALUES
  ('admin', 'admin@pdam.id', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMaBfNItzqYmMCdX0A3Q2mwzGm', 'Administrator', 'admin', TRUE),
  ('demo', 'demo@pdam.id', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMaBfNItzqYmMCdX0A3Q2mwzGm', 'Demo User', 'user', TRUE);

-- ============================================================
-- Views
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
GROUP BY u.id, u.original_name, u.total_rows, u.uploaded_at;

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
GROUP BY ah.id, ah.user_id, ah.upload_id, ah.filter_mode, ah.contamination,
         ah.jumlah_data, ah.jumlah_anomali, ah.persentase, ah.status, ah.created_at;
