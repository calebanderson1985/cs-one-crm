# CS One CRM

CS One CRM is an all-in-one PHP/MySQL CRM foundation designed to consolidate CRM core, communications, workflows, AI utilities, reporting, commissions, portals, and admin operations into one installable web application.

## Current repository baseline
This repository is based on the latest recoverable build from the session: **Phase 6**.

## Included modules
- CRM Core: clients, leads, deals, tasks
- Communications: templates, message logs, outbound queue, inbound tracking
- Commissions & Finance
- Reports & Analytics
- Workflow Engine with queued execution
- AI Workspace for summaries, drafts, and lead scoring
- Documents
- Notifications
- Admin, Manager, Agent, and Client portal shell
- Role-based permissions and starter tenant isolation
- API entrypoint for CRM resources

## Tech stack
- PHP
- HTML5
- MySQL
- Minimal MVC-style structure

## Repository structure
```text
app/            Controllers, models, services, views
config/         Application configuration
cron/           Background workers
database/       Schema and seed files
docs/           User guide, deployment guide, roadmap
public/         Web entrypoints and assets
storage/        Uploads, logs, cache
install.php     Browser installer
bootstrap.php   App bootstrap
```

## Quick start
1. Clone this repository.
2. Copy `.env.example` to `.env` and fill in your local values.
3. Create an empty MySQL database.
4. Open `install.php` in your browser.
5. Log in at `public/index.php?page=login`.

## Background worker
Process workflows and outbound communications:

```bash
php cron/worker.php
```

Process a single company only:

```bash
php cron/worker.php 1
```

## Important note
Phase 7 and Phase 8 were planned after this build in the original session, but their generated packages were not recoverable once the runtime expired. Their intended scope is preserved in `docs/ROADMAP_PHASE7_8.md`.
