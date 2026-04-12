# CS One CRM Phase 6 User Guide

## Overview

Phase 6 turns the earlier CRM scaffold into a more production-oriented SaaS CRM foundation. The system is organized by business category instead of separate plugin-style menus.

### Product categories

- **CRM Core**: Clients, Leads, Deals, Tasks
- **Communication**: Email / SMS, Templates, Notifications, Documents
- **Commissions & Finance**: Commission tracking and payout monitoring
- **Reporting & Analytics**: Filtered reports, exports, KPI summaries
- **Workflows & Automation**: Trigger-based workflows, queue, worker processing
- **AI & Intelligence**: Lead scoring, client summaries, email drafting
- **Portals**: Admin, Manager, Agent, Client experiences
- **Administration**: Users, Permissions, Audit, Settings, API, Feature Registry

## Installation

1. Create a new MySQL database.
2. Upload the Phase 6 package to your PHP hosting environment.
3. Open `install.php`.
4. Enter database credentials, tenant/company details, admin credentials, and optionally seed demo data.
5. Sign in at `public/index.php?page=login`.

## Roles and scope

### Admin
- Full visibility across the tenant/company
- Settings, permissions, API, users, audit, feature registry
- All CRUD and automation controls

### Manager
- Team-focused visibility
- Can work clients, leads, deals, tasks, communications, commissions, reports, workflows
- Sees team-owned records and queue activity

### Agent
- Works assigned leads, clients, deals, tasks, documents, and communications
- Uses AI workspace tools
- Receives assignment notifications

### Client
- Uses portal-oriented views only
- Can access client-facing communications, notifications, and client-scoped documents

## CRM Core

### Clients
Use **Clients** to create and manage customer accounts.

Fields:
- Company name
- Contact name
- Email and phone
- Status
- Assigned owner
- Notes

What happens when you save:
- Ownership is stored for role-aware visibility
- Assignment can notify the owner
- `client.created` or `client.updated` workflow events are fired

### Leads
Use **Leads** for prospect intake and qualification.

Fields:
- Lead name
- Company
- Contact info
- Source
- Stage
- Assigned owner
- Notes

Phase 6 lead intelligence:
- Leads are automatically scored with the AI workspace logic on create/update
- Scores are written to `ai_score`
- Workflow triggers fire on create/update

Lead conversion:
- Click **Convert** from the lead grid
- A client record is created from the lead
- The lead stage is updated to `Converted`
- `lead.converted` workflows can be attached

### Deals
Use **Deals** for revenue pipeline tracking.

Fields:
- Deal name
- Client name
- Stage
- Amount
- Owner
- Close date
- Notes

Workflow examples:
- Notify owner when stage becomes `Negotiation`
- Create follow-up tasks when a deal changes stage

### Tasks
Use **Tasks** for operational follow-ups.

Fields:
- Task name
- Related type and related record name
- Assigned owner
- Priority
- Due date
- Status
- Notes

Tasks support:
- Assignment notifications
- Workflow-triggered task creation
- Due-date and status reporting

## Communication Center

Open **Email / SMS** to manage outbound messaging, inbound logs, templates, and queue activity.

### Send / Queue Message
This creates:
- a communication log record
- an outbound queue record

You can:
- choose Email or SMS
- link to a related module and record id
- select a template
- queue for the worker or attempt immediate send

### Inbound Log
Use this to log inbound:
- emails
- SMS
- calls
- portal messages

### Templates
Templates support token replacement using fields like:
- `{{record.lead_name}}`
- `{{record.company_name}}`
- `{{record.contact_name}}`

### Queue processing
Phase 6 adds a real queue concept:
- messages can stay queued until a worker runs
- status can move to Sent or Failed
- provider and error text are stored in queue history

### Provider support included in code
- Email: PHP `mail()` fallback, SendGrid-ready, Mailgun-ready
- SMS: Twilio-ready

Provider credentials are configured in **System Settings**.

## Documents

Use **Documents** for centralized storage.

Fields:
- title
- related type
- related id
- visibility scope
- file upload

Visibility scopes:
- `company`
- `team`
- `owner`
- `client`

Downloads go through the authenticated document route instead of direct file links.

## Commissions

Use **Commissions** to track:
- agent owner
- client
- deal
- amount
- payout status
- statement month
- notes

This module is grouped separately so all payout-related operations are together instead of scattered across add-ons.

## Reports

Use **Reports** for filtered operating views.

Supported filters:
- date from
- date to
- user

Available summary areas:
- total deal value
- commissions due
- lead count
- task count
- message count
- workflow queue count

Exports available:
- deals
- leads
- tasks
- communications
- commissions

## Workflows

Use **Workflows** to create automation rules.

### Workflow fields
- Workflow name
- Module name
- Trigger key
- Condition field
- Condition operator
- Condition value
- Action key
- Action payload JSON
- Run mode
- Status
- Description

### Trigger examples
- `lead.created`
- `lead.updated`
- `lead.converted`
- `client.created`
- `client.updated`
- `deal.created`
- `deal.updated`
- `task.created`
- `task.updated`

### Supported actions
- `send_email`
- `send_sms`
- `create_task`
- `notify_user`
- `score_lead`

### Condition operators
- `equals`
- `not_equals`
- `contains`
- `greater_than`
- `less_than`

### Action payload examples

Create task:
```json
{"task_name":"Schedule discovery call for {{record.lead_name}}","priority":"High","notes":"Created automatically after qualification."}
```

Notify user:
```json
{"title":"Deal entered negotiation","message":"{{record.deal_name}} is now in negotiation stage.","link_url":"index.php?page=deals"}
```

Send email:
```json
{"subject":"Welcome {{record.lead_name}}","body":"Hello {{record.lead_name}}, thanks for contacting us."}
```

### Queue worker
The worker processes:
- workflow queue jobs
- outbound communication queue jobs

Run manually:
```bash
php cron/worker.php 1
```

Replace `1` with the tenant/company id.

## AI Workspace

Open **AI Workspace** for built-in intelligence tools.

### Lead scoring
- choose a lead
- click **Score Lead**
- the system updates `ai_score`
- an AI log entry is saved

### Client summary
- choose a client
- click **Generate Summary**
- the system summarizes client status, deal activity, task count, communication count, and notes

### Draft email
- enter context
- choose tone
- the system drafts a subject and body

This phase uses built-in heuristic intelligence with provider hooks available through settings for later external AI upgrades.

## User Management

Use **User Management** to create:
- Admin users
- Manager users
- Agent users
- Client users

Phase 6 additions:
- `manager_user_id` for team hierarchy
- `portal_client_id` for client portal linkage

## Permissions

Use **Permissions** to manage role-based access control.

The matrix supports four actions per module:
- view
- create
- edit
- delete

This lets you control access without enabling a long chain of separate plugins or fragmented menus.

## Settings

Use **System Settings** for:
- app name
- timezone
- tenant mode
- email provider details
- SMS provider details
- AI provider hook fields
- API token
- worker batch size

## API

Phase 6 adds a more usable API entrypoint at:
- `public/api.php`

Authentication:
- supply `token` in the query string, or
- send `X-API-Key`

Tenant resolution:
- if the request already runs inside a logged-in tenant session, that tenant is used
- if you pass `company_id`, the API uses that company scope
- if no company context is provided, the API will attempt to resolve the tenant from the token automatically

Supported resources:
- `clients`
- `leads`
- `deals`
- `tasks`
- `communications`

Examples:

Get list:
```text
public/api.php?resource=clients&token=YOUR_TOKEN
```

Get one:
```text
public/api.php?resource=clients&id=1&token=YOUR_TOKEN
```

POST JSON to create:
```json
{"company_name":"Example Co","contact_name":"Jordan Doe","status":"Active"}
```


## Worker

Use the worker to process queued workflow jobs and outbound messages.

Examples:

```text
php cron/worker.php
```

Processes all companies found in the database.

```text
php cron/worker.php 1
```

Processes only company id 1.

## Notifications

The notification center is used for:
- assignments
- workflow alerts
- exports
- document notices
- queue events

Users can mark notifications read individually or mark all read.

## Audit Trail

The audit log records key actions such as:
- login / logout
- create / update / delete actions
- workflow queueing and processing
- communication sends and failures
- exports
- document access
- permission changes
- settings changes

## Recommended next stage after Phase 6

The next build should focus on:
- stronger multi-tenant enforcement across every edge case
- full REST auth model with company-based tokens
- background retry rules and job scheduling
- polished UI/UX and white-labeling
- billing/subscription layer
- stronger client self-service flows
- deployment hardening and environment configuration
