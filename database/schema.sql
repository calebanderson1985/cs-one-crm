CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(190) NOT NULL,
    tenant_key VARCHAR(120) NOT NULL UNIQUE,
    status VARCHAR(50) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    full_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','agent','client') NOT NULL DEFAULT 'agent',
    manager_user_id INT NULL,
    portal_client_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_user_company_email (company_id, email),
    INDEX idx_users_company (company_id),
    INDEX idx_users_manager (manager_user_id),
    INDEX idx_users_portal_client (portal_client_id)
);

CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    company_name VARCHAR(190) NOT NULL,
    contact_name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Active',
    assigned_user_id INT NULL,
    assigned_agent VARCHAR(190) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_clients_company (company_id),
    INDEX idx_clients_assigned (assigned_user_id)
);

CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    lead_name VARCHAR(190) NOT NULL,
    company_name VARCHAR(190) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    source_name VARCHAR(100) NULL,
    stage VARCHAR(80) NOT NULL DEFAULT 'New',
    assigned_user_id INT NULL,
    assigned_to VARCHAR(190) NULL,
    ai_score INT NOT NULL DEFAULT 0,
    last_scored_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_leads_company (company_id),
    INDEX idx_leads_assigned (assigned_user_id),
    INDEX idx_leads_stage (stage)
);

CREATE TABLE IF NOT EXISTS deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    deal_name VARCHAR(190) NOT NULL,
    client_name VARCHAR(190) NOT NULL,
    stage VARCHAR(80) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    owner_user_id INT NULL,
    owner_name VARCHAR(190) NULL,
    close_date DATE NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_deals_company (company_id),
    INDEX idx_deals_owner (owner_user_id),
    INDEX idx_deals_stage (stage)
);

CREATE TABLE IF NOT EXISTS communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    related_type VARCHAR(50) NULL,
    related_id INT NULL,
    channel VARCHAR(30) NOT NULL,
    direction VARCHAR(30) NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    subject_line VARCHAR(190) NULL,
    body_text TEXT NULL,
    status VARCHAR(50) NOT NULL,
    provider_name VARCHAR(100) NULL,
    template_id INT NULL,
    created_by INT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_comms_company (company_id),
    INDEX idx_comms_related (related_type, related_id),
    INDEX idx_comms_created_by (created_by)
);

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    related_type VARCHAR(50) NULL,
    related_id INT NULL,
    title VARCHAR(190) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NULL,
    file_size BIGINT NOT NULL DEFAULT 0,
    visibility_scope VARCHAR(30) NOT NULL DEFAULT 'company',
    uploaded_by INT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_docs_company (company_id),
    INDEX idx_docs_related (related_type, related_id),
    INDEX idx_docs_uploaded_by (uploaded_by)
);

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    task_name VARCHAR(190) NOT NULL,
    related_type VARCHAR(50) NULL,
    related_name VARCHAR(190) NULL,
    assigned_user_id INT NULL,
    assigned_to VARCHAR(190) NULL,
    priority_level VARCHAR(30) NOT NULL DEFAULT 'Normal',
    due_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Open',
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_tasks_company (company_id),
    INDEX idx_tasks_assigned (assigned_user_id),
    INDEX idx_tasks_status (status)
);

CREATE TABLE IF NOT EXISTS commissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    agent_user_id INT NULL,
    agent_name VARCHAR(190) NOT NULL,
    client_name VARCHAR(190) NOT NULL,
    deal_name VARCHAR(190) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payout_status VARCHAR(50) NOT NULL DEFAULT 'Unpaid',
    statement_month VARCHAR(20) NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_commissions_company (company_id),
    INDEX idx_commissions_agent (agent_user_id)
);

CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(190) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    workflow_name VARCHAR(190) NOT NULL,
    module_name VARCHAR(80) NOT NULL DEFAULT 'System',
    description_text TEXT NULL,
    trigger_key VARCHAR(100) NOT NULL,
    condition_field VARCHAR(100) NULL,
    condition_operator VARCHAR(30) NULL,
    condition_value VARCHAR(190) NULL,
    action_key VARCHAR(100) NOT NULL,
    action_payload TEXT NULL,
    run_mode VARCHAR(20) NOT NULL DEFAULT 'queue',
    status VARCHAR(50) NOT NULL DEFAULT 'Active',
    created_by INT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_workflows_company (company_id),
    INDEX idx_workflows_trigger (trigger_key)
);

CREATE TABLE IF NOT EXISTS workflow_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    workflow_id INT NOT NULL,
    trigger_key VARCHAR(100) NOT NULL,
    payload_json LONGTEXT NULL,
    queue_status VARCHAR(30) NOT NULL DEFAULT 'Queued',
    available_at DATETIME NOT NULL,
    processed_at DATETIME NULL,
    error_text TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_workflow_queue_company (company_id),
    INDEX idx_workflow_queue_status (queue_status)
);

CREATE TABLE IF NOT EXISTS workflow_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    workflow_id INT NULL,
    workflow_name VARCHAR(190) NOT NULL,
    trigger_key VARCHAR(100) NOT NULL,
    action_key VARCHAR(100) NOT NULL,
    run_status VARCHAR(30) NOT NULL DEFAULT 'Queued',
    details TEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_workflow_runs_company (company_id),
    INDEX idx_workflow_runs_created (created_at)
);

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    setting_key VARCHAR(120) NOT NULL,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_settings_company_key (company_id, setting_key),
    INDEX idx_settings_company (company_id)
);

CREATE TABLE IF NOT EXISTS feature_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    feature_name VARCHAR(255) NOT NULL,
    source_module VARCHAR(255) NOT NULL,
    usage_summary TEXT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NULL,
    module_name VARCHAR(80) NOT NULL,
    action_name VARCHAR(80) NOT NULL,
    record_id INT NULL,
    summary_text TEXT NULL,
    ip_address VARCHAR(64) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_audit_company (company_id),
    INDEX idx_audit_module (module_name),
    INDEX idx_audit_created (created_at)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NULL,
    title VARCHAR(190) NOT NULL,
    message_text TEXT NULL,
    level_name VARCHAR(30) NOT NULL DEFAULT 'info',
    link_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_notifications_company (company_id),
    INDEX idx_notifications_user (user_id),
    INDEX idx_notifications_read (is_read)
);

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(190) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_password_resets_user (user_id),
    INDEX idx_password_resets_email (email),
    INDEX idx_password_resets_expires (expires_at)
);

CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    role_name VARCHAR(30) NOT NULL,
    module_name VARCHAR(80) NOT NULL,
    can_view TINYINT(1) NOT NULL DEFAULT 0,
    can_create TINYINT(1) NOT NULL DEFAULT 0,
    can_edit TINYINT(1) NOT NULL DEFAULT 0,
    can_delete TINYINT(1) NOT NULL DEFAULT 0,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_role_module_company (company_id, role_name, module_name),
    INDEX idx_role_permissions_company (company_id)
);

CREATE TABLE IF NOT EXISTS communication_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    template_name VARCHAR(190) NOT NULL,
    channel VARCHAR(30) NOT NULL,
    subject_template VARCHAR(190) NULL,
    body_template TEXT NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_templates_company (company_id)
);

CREATE TABLE IF NOT EXISTS outbound_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    communication_id INT NULL,
    channel VARCHAR(30) NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    subject_line VARCHAR(190) NULL,
    body_text TEXT NULL,
    provider_name VARCHAR(100) NULL,
    send_status VARCHAR(30) NOT NULL DEFAULT 'Queued',
    attempt_count INT NOT NULL DEFAULT 0,
    provider_message_id VARCHAR(190) NULL,
    error_text TEXT NULL,
    scheduled_at DATETIME NOT NULL,
    sent_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_outbound_company (company_id),
    INDEX idx_outbound_status (send_status)
);

CREATE TABLE IF NOT EXISTS ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    user_id INT NULL,
    tool_name VARCHAR(80) NOT NULL,
    input_text LONGTEXT NULL,
    output_text LONGTEXT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_ai_logs_company (company_id),
    INDEX idx_ai_logs_created (created_at)
);


CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    token_name VARCHAR(190) NOT NULL,
    token_prefix VARCHAR(32) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    scope_text TEXT NOT NULL,
    expires_at DATETIME NULL,
    last_used_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_by INT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_token_hash (token_hash),
    INDEX idx_api_tokens_company (company_id)
);

CREATE TABLE IF NOT EXISTS onboarding_steps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    step_key VARCHAR(120) NOT NULL,
    title VARCHAR(190) NOT NULL,
    description_text TEXT NULL,
    is_complete TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    completed_by INT NULL,
    completed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY uniq_onboarding_company_step (company_id, step_key),
    INDEX idx_onboarding_company (company_id)
);



CREATE TABLE IF NOT EXISTS webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    provider_name VARCHAR(50) NOT NULL,
    event_type VARCHAR(150) NOT NULL,
    payload_text LONGTEXT NULL,
    signature_header TEXT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    processing_status VARCHAR(50) NOT NULL DEFAULT 'received',
    replay_count INT NOT NULL DEFAULT 0,
    response_text LONGTEXT NULL,
    processed_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    INDEX idx_webhook_events_company (company_id),
    INDEX idx_webhook_events_status (processing_status)
);
