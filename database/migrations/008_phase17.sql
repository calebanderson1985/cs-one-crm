ALTER TABLE support_tickets
    ADD COLUMN requester_name VARCHAR(190) NULL AFTER detail_text,
    ADD COLUMN requester_email VARCHAR(190) NULL AFTER requester_name,
    ADD COLUMN source_channel VARCHAR(30) NOT NULL DEFAULT 'Web' AFTER requester_email,
    ADD COLUMN thread_ref VARCHAR(190) NULL AFTER source_channel,
    ADD COLUMN last_inbound_at DATETIME NULL AFTER thread_ref,
    ADD COLUMN last_outbound_at DATETIME NULL AFTER last_inbound_at;

ALTER TABLE support_ticket_comments
    ADD COLUMN parent_comment_id INT NULL AFTER user_id,
    ADD COLUMN message_direction VARCHAR(20) NOT NULL DEFAULT 'internal' AFTER visibility_scope,
    ADD COLUMN message_source VARCHAR(20) NOT NULL DEFAULT 'web' AFTER message_direction,
    ADD COLUMN source_message_id VARCHAR(190) NULL AFTER message_source,
    ADD COLUMN sender_name VARCHAR(190) NULL AFTER source_message_id,
    ADD COLUMN sender_email VARCHAR(190) NULL AFTER sender_name,
    ADD COLUMN thread_ref VARCHAR(190) NULL AFTER sender_email;

CREATE INDEX idx_support_requester ON support_tickets (company_id, requester_email);
CREATE INDEX idx_support_thread ON support_tickets (company_id, thread_ref);
CREATE INDEX idx_support_ticket_comments_parent ON support_ticket_comments (company_id, parent_comment_id);
CREATE INDEX idx_support_ticket_comments_message ON support_ticket_comments (company_id, source_message_id);

CREATE TABLE IF NOT EXISTS support_email_ingestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    ticket_id INT NULL,
    from_email VARCHAR(190) NOT NULL,
    subject_line VARCHAR(255) NULL,
    source_message_id VARCHAR(190) NULL,
    status_name VARCHAR(30) NOT NULL DEFAULT 'Processed',
    notes_text TEXT NULL,
    created_at DATETIME NOT NULL,
    KEY idx_support_email_ingestions_company (company_id, created_at),
    KEY idx_support_email_ingestions_message (company_id, source_message_id)
);
