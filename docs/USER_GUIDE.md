# User Guide - Phase 9

## Main areas
- CRM Core: Clients, Leads, Deals, Tasks
- Communication: Email/SMS logs, Notifications, Documents
- Reporting & Automation: Reports, Workflows
- AI & Intelligence: AI Workspace
- Administration: Users, Permissions, Audit, Settings, API Tokens, Launch Wizard

## API Token Center
Create scoped tokens for integrations. Copy the plaintext token when shown; it is not displayed again. Rotate or revoke tokens as needed.

## Launch Wizard
Use the wizard to track go-live readiness across branding, communications, permissions, team setup, API setup, and final launch review.

## API
Supported resources: clients, leads, deals, tasks, communications.
- GET requires `resource:read` scope
- POST/PUT/PATCH/DELETE require `resource:write` scope

## Billing scaffold
Stripe fields are stored under System Settings and webhook requests land at `public/webhook.php`.


## Phase 12 Additions

### Webhook Events
Use **Administration > Webhook Events** to review billing/provider events and replay a stored event through the billing handler.

### Queue Operations
Use **Administration > Queue Operations** to retry failed workflow jobs and outbound communications.

### Diagnostics
Use **Administration > Diagnostics** to review production-readiness checks such as PHP support, writable storage, worker sizing, password policy, and API limits.
