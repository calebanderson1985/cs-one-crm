CREATE TABLE IF NOT EXISTS support_ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    ticket_id INT NOT NULL,
    user_id INT NULL,
    visibility_scope VARCHAR(20) NOT NULL DEFAULT 'internal',
    comment_text TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_support_ticket_comments_company_ticket (company_id, ticket_id),
    KEY idx_support_ticket_comments_user (user_id)
);

CREATE TABLE IF NOT EXISTS support_escalation_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    rule_name VARCHAR(190) NOT NULL,
    priority_name VARCHAR(50) NULL,
    category_name VARCHAR(120) NULL,
    hours_after_breach INT NOT NULL DEFAULT 0,
    escalate_to_user_id INT NULL,
    set_priority_name VARCHAR(50) NULL,
    set_status_name VARCHAR(50) NOT NULL DEFAULT 'Escalated',
    comment_template TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_support_escalation_rules_company (company_id, is_active, sort_order)
);
