
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
