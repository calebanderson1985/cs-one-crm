INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'manager', 'tokens', 0, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'manager', 'onboarding', 1, 0, 1, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_edit = VALUES(can_edit), updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'agent', 'tokens', 0, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'agent', 'onboarding', 0, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'client', 'tokens', 0, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'client', 'onboarding', 0, 0, 0, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'admin', 'tokens', 1, 1, 1, 1, NOW() FROM companies c
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_create = VALUES(can_create), can_edit = VALUES(can_edit), can_delete = VALUES(can_delete), updated_at = VALUES(updated_at);
INSERT INTO role_permissions (company_id, role_name, module_name, can_view, can_create, can_edit, can_delete, updated_at)
SELECT c.id, 'admin', 'onboarding', 1, 0, 1, 0, NOW() FROM companies c
ON DUPLICATE KEY UPDATE can_view = VALUES(can_view), can_edit = VALUES(can_edit), updated_at = VALUES(updated_at);
