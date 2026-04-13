ALTER TABLE clients
    ADD COLUMN IF NOT EXISTS lifecycle_stage VARCHAR(80) NULL AFTER status,
    ADD COLUMN IF NOT EXISTS industry_name VARCHAR(120) NULL AFTER lifecycle_stage,
    ADD COLUMN IF NOT EXISTS website_url VARCHAR(255) NULL AFTER industry_name,
    ADD COLUMN IF NOT EXISTS policy_type VARCHAR(120) NULL AFTER website_url,
    ADD COLUMN IF NOT EXISTS renewal_date DATE NULL AFTER policy_type,
    ADD COLUMN IF NOT EXISTS address_line1 VARCHAR(190) NULL AFTER renewal_date,
    ADD COLUMN IF NOT EXISTS address_line2 VARCHAR(190) NULL AFTER address_line1,
    ADD COLUMN IF NOT EXISTS city_name VARCHAR(120) NULL AFTER address_line2,
    ADD COLUMN IF NOT EXISTS state_name VARCHAR(80) NULL AFTER city_name,
    ADD COLUMN IF NOT EXISTS postal_code VARCHAR(30) NULL AFTER state_name,
    ADD COLUMN IF NOT EXISTS annual_revenue DECIMAL(14,2) NULL AFTER postal_code,
    ADD COLUMN IF NOT EXISTS employee_count INT NULL AFTER annual_revenue;

CREATE TABLE IF NOT EXISTS support_ticket_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    ticket_id INT NOT NULL,
    comment_id INT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    file_size BIGINT NOT NULL DEFAULT 0,
    uploaded_by INT NULL,
    source_name VARCHAR(40) NOT NULL DEFAULT 'portal',
    created_at DATETIME NOT NULL,
    INDEX idx_sta_company_ticket (company_id, ticket_id),
    INDEX idx_sta_comment (company_id, comment_id)
);

CREATE TABLE IF NOT EXISTS attachment_scan_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    attachment_type VARCHAR(40) NOT NULL DEFAULT 'support',
    attachment_id INT NOT NULL,
    scan_status VARCHAR(30) NOT NULL DEFAULT 'pending',
    engine_name VARCHAR(80) NULL,
    summary_text TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_asl_company_attachment (company_id, attachment_type, attachment_id)
);

CREATE TABLE IF NOT EXISTS mailbox_poll_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    config_name VARCHAR(190) NOT NULL,
    host_name VARCHAR(190) NOT NULL,
    port_number INT NOT NULL DEFAULT 993,
    encryption_type VARCHAR(20) NOT NULL DEFAULT 'ssl',
    username_text VARCHAR(190) NOT NULL,
    password_text TEXT NOT NULL,
    inbox_name VARCHAR(120) NOT NULL DEFAULT 'INBOX',
    sender_domain_filter VARCHAR(190) NULL,
    poll_mode VARCHAR(20) NOT NULL DEFAULT 'unseen',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_polled_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_mailbox_poll_company (company_id, is_active)
);
