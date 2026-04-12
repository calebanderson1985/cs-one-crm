CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    category_name VARCHAR(100) NOT NULL DEFAULT 'General',
    priority_name VARCHAR(30) NOT NULL DEFAULT 'Normal',
    status_name VARCHAR(30) NOT NULL DEFAULT 'Open',
    owner_user_id INT NULL,
    detail_text TEXT NULL,
    created_by INT NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_support_company (company_id),
    INDEX idx_support_status (status_name),
    INDEX idx_support_owner (owner_user_id)
);

ALTER TABLE companies
    ADD COLUMN suspension_reason TEXT NULL AFTER status,
    ADD COLUMN suspended_at DATETIME NULL AFTER suspension_reason;

INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT 1, 'admin', 'support', 1, 1, 1, 1, NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM role_permissions WHERE company_id = 1 AND role_name = 'admin' AND module_name = 'support');

INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT 1, 'manager', 'support', 1, 1, 1, 0, NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM role_permissions WHERE company_id = 1 AND role_name = 'manager' AND module_name = 'support');

INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT 1, 'agent', 'support', 1, 1, 0, 0, NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM role_permissions WHERE company_id = 1 AND role_name = 'agent' AND module_name = 'support');
