CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  body_text TEXT NULL,
  audience_scope VARCHAR(40) NOT NULL DEFAULT 'company',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY idx_announcements_company (company_id, is_active)
);

CREATE TABLE IF NOT EXISTS maintenance_runs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  run_type VARCHAR(60) NOT NULL,
  result_json LONGTEXT NULL,
  created_by INT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_maintenance_runs_company (company_id, created_at)
);

CREATE TABLE IF NOT EXISTS worker_heartbeats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  worker_name VARCHAR(100) NOT NULL,
  heartbeat_at DATETIME NOT NULL,
  status_text VARCHAR(60) NOT NULL DEFAULT 'ok',
  payload_json LONGTEXT NULL,
  UNIQUE KEY uq_worker_company_name (company_id, worker_name),
  KEY idx_worker_heartbeats_company (company_id, heartbeat_at)
);
