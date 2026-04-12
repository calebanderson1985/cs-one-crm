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
