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
