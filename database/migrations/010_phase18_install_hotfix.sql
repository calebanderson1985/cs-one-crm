CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NULL,
    user_id INT NULL,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NULL,
    success_flag TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    INDEX idx_login_attempts_email (email),
    INDEX idx_login_attempts_ip (ip_address),
    INDEX idx_login_attempts_created (created_at)
);

ALTER TABLE onboarding_steps
    ADD COLUMN IF NOT EXISTS action_url VARCHAR(255) NULL AFTER description_text;
