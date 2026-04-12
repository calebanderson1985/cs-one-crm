CREATE TABLE IF NOT EXISTS sla_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    policy_name VARCHAR(190) NOT NULL,
    target_scope VARCHAR(100) DEFAULT 'General',
    response_minutes INT NOT NULL DEFAULT 60,
    resolution_minutes INT NOT NULL DEFAULT 480,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_sla_company (company_id)
);

CREATE TABLE IF NOT EXISTS knowledge_base_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(190) NOT NULL,
    category_name VARCHAR(120) DEFAULT 'General',
    visibility_scope VARCHAR(40) NOT NULL DEFAULT 'internal',
    body_text MEDIUMTEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_kb_company (company_id),
    INDEX idx_kb_visibility (company_id, visibility_scope, is_published)
);

ALTER TABLE support_tickets
    ADD COLUMN sla_policy_id INT NULL AFTER created_by,
    ADD COLUMN response_due_at DATETIME NULL AFTER sla_policy_id,
    ADD COLUMN resolution_due_at DATETIME NULL AFTER response_due_at;
