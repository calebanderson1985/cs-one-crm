# CS One CRM Recovered Repo

This repository was reconstructed from the latest recoverable build artifacts after a runtime reset. The application code in this repo comes from the working **Phase 6** package, with surviving later-phase docs included as references where available.

## Status
- Recoverable code baseline: **Phase 6**
- Recoverable repo docs: partial **Phase 7** references
- Phase 8/9 code: not present in the runtime filesystem at recovery time

## Purpose
Use this as the stable GitHub source of truth and continue rebuilding later phases under version control.

---

# CS One CRM Phase 6

A categorized all-in-one PHP/MySQL CRM foundation with:

- CRM Core: clients, leads, deals, tasks
- Communication: email/SMS queue, templates, activity log
- Commissions & Finance
- Reporting & Analytics
- Workflow Automation with queue worker
- AI Workspace for lead scoring, summaries, and drafts
- Role-based permissions and team ownership
- Multi-tenant company isolation starter model
- API entrypoint at `public/api.php`

## Install

1. Create an empty MySQL database.
2. Open `install.php` in the browser.
3. Enter database credentials and admin account details.
4. Sign in at `public/index.php?page=login`.

## Worker

Run the worker regularly to process workflow jobs and outbound communications:

```bash
php cron/worker.php
```

To process a single tenant/company only, pass the company id:

```bash
php cron/worker.php 1
```
