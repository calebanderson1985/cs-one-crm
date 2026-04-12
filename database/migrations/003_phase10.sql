CREATE TABLE IF NOT EXISTS api_request_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    api_token_id INT NULL,
    resource_name VARCHAR(80) NULL,
    http_method VARCHAR(10) NULL,
    status_code INT NOT NULL DEFAULT 200,
    request_path VARCHAR(255) NULL,
    ip_address VARCHAR(64) NULL,
    scope_text TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_api_request_logs_company (company_id),
    KEY idx_api_request_logs_token (api_token_id),
    KEY idx_api_request_logs_created (created_at)
);

INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'admin', 'api_analytics', 1, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), updated_at = VALUES(updated_at);

INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'admin', 'company_switch', 1, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), updated_at = VALUES(updated_at);

INSERT INTO system_settings (company_id, setting_key, setting_value, updated_at)
SELECT c.id, 'login_rate_limit', '5', NOW() FROM companies c
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at);
