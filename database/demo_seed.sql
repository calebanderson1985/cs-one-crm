SET @company_id := __COMPANY_ID__;
SET @admin_user_id := __ADMIN_USER_ID__;

INSERT INTO users (company_id, full_name, email, password_hash, role, manager_user_id, portal_client_id, is_active, created_at, updated_at)
SELECT @company_id, 'Manager User', 'manager@example.com', '$2y$10$7B3ZLrDW9CFAzDqP4TpqIejR8gFMvx6bMpiKFFitvolG/G5hgbf.e', 'manager', NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE company_id = @company_id AND email = 'manager@example.com');

SET @manager_user_id := (SELECT id FROM users WHERE company_id = @company_id AND email = 'manager@example.com' LIMIT 1);

INSERT INTO users (company_id, full_name, email, password_hash, role, manager_user_id, portal_client_id, is_active, created_at, updated_at)
SELECT @company_id, 'Agent User', 'agent@example.com', '$2y$10$7B3ZLrDW9CFAzDqP4TpqIejR8gFMvx6bMpiKFFitvolG/G5hgbf.e', 'agent', @manager_user_id, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE company_id = @company_id AND email = 'agent@example.com');

SET @agent_user_id := (SELECT id FROM users WHERE company_id = @company_id AND email = 'agent@example.com' LIMIT 1);

INSERT INTO clients (company_id, company_name, contact_name, email, phone, status, assigned_user_id, assigned_agent, notes, created_at, updated_at) VALUES
(@company_id,'Acme Agency','Jordan Blake','jordan@acme.test','555-1000','Active',@agent_user_id,'Agent User','Top renewal account',NOW(),NOW()),
(@company_id,'Blue Ridge Benefits','Taylor Moss','taylor@brb.test','555-1001','Prospect',@manager_user_id,'Manager User','Needs onboarding follow-up',NOW(),NOW());

SET @client_portal_id := (SELECT id FROM clients WHERE company_id = @company_id AND company_name = 'Acme Agency' ORDER BY id DESC LIMIT 1);

INSERT INTO users (company_id, full_name, email, password_hash, role, manager_user_id, portal_client_id, is_active, created_at, updated_at)
SELECT @company_id, 'Client User', 'client@example.com', '$2y$10$7B3ZLrDW9CFAzDqP4TpqIejR8gFMvx6bMpiKFFitvolG/G5hgbf.e', 'client', NULL, @client_portal_id, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE company_id = @company_id AND email = 'client@example.com');

SET @client_user_id := (SELECT id FROM users WHERE company_id = @company_id AND email = 'client@example.com' LIMIT 1);

INSERT INTO leads (company_id, lead_name, company_name, email, phone, source_name, stage, assigned_user_id, assigned_to, ai_score, last_scored_at, notes, created_at, updated_at) VALUES
(@company_id,'Morgan Lee','North Star Group','morgan@northstar.test','555-2100','Referral','Qualified',@agent_user_id,'Agent User',78,NOW(),'Warm referral from existing book',NOW(),NOW()),
(@company_id,'Riley Grant','Summit Coverage','riley@summit.test','555-2101','Website','New',@manager_user_id,'Manager User',48,NOW(),'Requested proposal review',NOW(),NOW());

INSERT INTO deals (company_id, deal_name, client_name, stage, amount, owner_user_id, owner_name, close_date, notes, created_at, updated_at) VALUES
(@company_id,'Acme Renewal','Acme Agency','Proposal',12500.00,@manager_user_id,'Manager User',CURDATE(),'Renewal package in review',NOW(),NOW()),
(@company_id,'Blue Ridge Expansion','Blue Ridge Benefits','Negotiation',18500.00,@agent_user_id,'Agent User',DATE_ADD(CURDATE(), INTERVAL 14 DAY),'Waiting on benefit census',NOW(),NOW());

INSERT INTO communication_templates (company_id, template_name, channel, subject_template, body_template, status, created_at, updated_at) VALUES
(@company_id,'Lead Welcome','Email','Welcome {{record.lead_name}}','Hello {{record.lead_name}},\n\nThanks for your interest in {{record.company_name}}. We will follow up shortly.\n\nBest regards,\nCS One CRM','Active',NOW(),NOW()),
(@company_id,'Task Reminder','SMS',NULL,'Reminder: {{record.task_name}} is due soon.','Active',NOW(),NOW()),
(@company_id,'Client Follow-up','Email','Next steps for {{record.company_name}}','Hello {{record.contact_name}},\n\nHere is a quick update on your account.','Active',NOW(),NOW());

SET @lead_welcome_template_id := (SELECT id FROM communication_templates WHERE company_id = @company_id AND template_name = 'Lead Welcome' LIMIT 1);

INSERT INTO communications (company_id, related_type, related_id, channel, direction, recipient, subject_line, body_text, status, provider_name, template_id, created_by, sent_at, created_at, updated_at) VALUES
(@company_id,'Client',@client_portal_id,'Email','Outbound','jordan@acme.test','Welcome to CS One CRM','Onboarding note','Sent','SMTP Placeholder',NULL,@admin_user_id,NOW(),NOW(),NOW()),
(@company_id,'Client',@client_portal_id,'SMS','Inbound','555-1001',NULL,'Interested in pricing','Received','Twilio Placeholder',NULL,@agent_user_id,NOW(),NOW(),NOW());

SET @welcome_comm_id := (SELECT id FROM communications WHERE company_id = @company_id AND recipient = 'jordan@acme.test' ORDER BY id DESC LIMIT 1);

INSERT INTO outbound_messages (company_id, communication_id, channel, recipient, subject_line, body_text, provider_name, send_status, attempt_count, provider_message_id, error_text, scheduled_at, sent_at, created_at, updated_at) VALUES
(@company_id,@welcome_comm_id,'Email','jordan@acme.test','Welcome to CS One CRM','Onboarding note','SMTP Placeholder','Sent',1,NULL,NULL,NOW(),NOW(),NOW(),NOW());

INSERT INTO documents (company_id, related_type, related_id, title, original_name, storage_path, mime_type, file_size, visibility_scope, uploaded_by, created_at) VALUES
(@company_id,'Client',@client_portal_id,'Acme Welcome Packet','welcome_packet.pdf','uploads/demo/welcome_packet.pdf','application/pdf',248120,'client',@admin_user_id,NOW()),
(@company_id,'Commission',1,'Commission Statement Sample','commission_statement_march.pdf','uploads/demo/commission_statement_march.pdf','application/pdf',165980,'company',@admin_user_id,NOW());

INSERT INTO tasks (company_id, task_name, related_type, related_name, assigned_user_id, assigned_to, priority_level, due_date, status, notes, created_at, updated_at) VALUES
(@company_id,'Call renewal contact','Client','Acme Agency',@agent_user_id,'Agent User','High',CURDATE(),'Open','Confirm renewal timeline',NOW(),NOW()),
(@company_id,'Review proposal package','Deal','Blue Ridge Expansion',@manager_user_id,'Manager User','Normal',DATE_ADD(CURDATE(), INTERVAL 3 DAY),'In Progress','Validate pricing strategy',NOW(),NOW());

INSERT INTO commissions (company_id, agent_user_id, agent_name, client_name, deal_name, amount, payout_status, statement_month, notes, created_at, updated_at) VALUES
(@company_id,@agent_user_id,'Agent User','Acme Agency','Acme Renewal',750.00,'Unpaid',DATE_FORMAT(CURDATE(), '%Y-%m'),'Awaiting month-end close',NOW(),NOW());

INSERT INTO reports (report_name, category_name, status, created_at) VALUES
('Executive KPI Snapshot','Reporting & Analytics','Active',NOW()),
('Commission Statement Summary','Commissions & Finance','Active',NOW()),
('Agent Activity Log','Operations','Active',NOW());

SET @payload_welcome := CONCAT('{"template_id":', @lead_welcome_template_id, ',"subject":"Welcome {{record.lead_name}}","body":"Hello {{record.lead_name}}, thanks for contacting us."}');
SET @payload_task := '{"task_name":"Schedule discovery call for {{record.lead_name}}","priority":"High","notes":"Created automatically after qualification."}';
SET @payload_notify := '{"title":"Deal entered negotiation","message":"{{record.deal_name}} is now in negotiation stage.","link_url":"index.php?page=deals"}';

INSERT INTO workflows (company_id, workflow_name, module_name, description_text, trigger_key, condition_field, condition_operator, condition_value, action_key, action_payload, run_mode, status, created_by, created_at, updated_at) VALUES
(@company_id,'New Lead Welcome','Communications','Sends a welcome email when a new lead enters the CRM.','lead.created','stage','equals','New','send_email',@payload_welcome,'queue','Active',@admin_user_id,NOW(),NOW()),
(@company_id,'Qualified Lead Task','Workflows','Creates a discovery task when a lead becomes qualified.','lead.updated','stage','equals','Qualified','create_task',@payload_task,'queue','Active',@admin_user_id,NOW(),NOW()),
(@company_id,'Negotiation Alert','Sales','Notifies the owner when a deal enters negotiation.','deal.updated','stage','equals','Negotiation','notify_user',@payload_notify,'queue','Active',@admin_user_id,NOW(),NOW()),
(@company_id,'Lead Score Refresh','AI','Refreshes lead score whenever a lead changes.','lead.updated',NULL,NULL,NULL,'score_lead','{}','queue','Active',@admin_user_id,NOW(),NOW());

SET @negotiation_workflow_id := (SELECT id FROM workflows WHERE company_id = @company_id AND workflow_name = 'Negotiation Alert' LIMIT 1);
SET @queue_payload := CONCAT('{"record":{"id":1,"type":"Deal","deal_name":"Blue Ridge Expansion","stage":"Negotiation","assigned_user_id":', @agent_user_id, '}}');

INSERT INTO workflow_queue (company_id, workflow_id, trigger_key, payload_json, queue_status, available_at, processed_at, error_text, created_at, updated_at) VALUES
(@company_id,@negotiation_workflow_id,'deal.updated',@queue_payload,'Queued',NOW(),NULL,NULL,NOW(),NOW());

INSERT INTO workflow_runs (company_id, workflow_id, workflow_name, trigger_key, action_key, run_status, details, created_at)
SELECT @company_id, id, workflow_name, trigger_key, action_key, 'Success', 'Seeded example run.', NOW() FROM workflows WHERE company_id = @company_id LIMIT 1;

INSERT INTO ai_logs (company_id, user_id, tool_name, input_text, output_text, created_at) VALUES
(@company_id,@admin_user_id,'lead_score','{"lead":"Morgan Lee"}','Lead score 78/100 based on source, contact data, and stage.',NOW());

INSERT INTO audit_logs (company_id, user_id, module_name, action_name, record_id, summary_text, ip_address, created_at) VALUES
(@company_id,@admin_user_id,'system','install',NULL,'Initial system seed completed.','127.0.0.1',NOW()),
(@company_id,@admin_user_id,'workflows','queue',NULL,'Workflow queue seeded for Phase 6.','127.0.0.1',NOW());

INSERT INTO notifications (company_id, user_id, title, message_text, level_name, link_url, is_read, created_at) VALUES
(@company_id,@admin_user_id,'Welcome to Phase 6','Communications, workflow engine, RBAC, API, and AI workspace are active.','success','index.php?page=dashboard',0,NOW()),
(@company_id,@agent_user_id,'Deal entered negotiation','Blue Ridge Expansion needs follow-up.','warning','index.php?page=deals',0,NOW()),
(@company_id,@client_user_id,'New portal document','A client-facing document is ready to download.','info','index.php?page=documents',0,NOW());


INSERT INTO onboarding_steps (company_id, step_key, title, description_text, is_complete, sort_order, completed_by, completed_at, created_at, updated_at) VALUES
(@company_id,'brand_setup','Brand setup','Confirm app name, branding, and support details.',1,10,@admin_user_id,NOW(),NOW(),NOW()),
(@company_id,'communications','Communications','Configure email and SMS providers.',1,20,@admin_user_id,NOW(),NOW(),NOW()),
(@company_id,'permissions','Permissions','Review role matrix and access scope.',0,30,NULL,NULL,NOW(),NOW()),
(@company_id,'team_setup','Team setup','Create managers, agents, and client portal users.',1,40,@admin_user_id,NOW(),NOW(),NOW()),
(@company_id,'api_tokens','API tokens','Generate scoped API credentials.',0,50,NULL,NULL,NOW(),NOW()),
(@company_id,'launch_review','Launch review','Validate billing, webhooks, and worker.',0,60,NULL,NULL,NOW(),NOW());

INSERT INTO api_tokens (company_id, token_name, token_prefix, token_hash, scope_text, expires_at, last_used_at, revoked_at, created_by, created_at, updated_at) VALUES
(@company_id,'Demo Integration','demo12345678',SHA2('phase9-demo-token', 256),'clients:read,leads:read,deals:read,tasks:read,communications:read',NULL,NULL,NULL,@admin_user_id,NOW(),NOW());
