# CS One CRM - Phase 11 Repo

CS One CRM is a standalone PHP, HTML5, and MySQL multi-tenant CRM scaffold that consolidates CRM Core, communications, commissions, reporting, workflows, AI tools, portals, and SaaS operations into one installable application.

## Phase 11 highlights
- Scoped API token center with create, rotate, revoke, and last-used tracking
- Stronger API auth with bearer tokens, resource scopes, and write-field whitelisting
- Onboarding launch wizard for go-live readiness
- Billing webhook scaffold at `public/webhook.php`
- Forward-only migration runner at `scripts/migrate.php`

## Quick start
1. Copy the repo to your PHP host
2. Create a MySQL database
3. Open `install.php`
4. Complete the installer and optional demo seed
5. Log in at `public/index.php?page=login`

## API auth
- Preferred: `Authorization: Bearer <scoped token>`
- Legacy compatibility: `X-API-Key` or `?token=` using the single company token

## Worker
Run `php cron/worker.php` from cron to process queued workflow activity and outbound communications.

## Migration runner
Use `php scripts/migrate.php` after pulling future updates to apply new SQL migrations.

## Health check
Use `public/health.php` for a simple JSON health response.


## Phase 10 additions

- API analytics dashboard
- Super-admin company context switching
- Stripe verification service wrapper
- Rate-limit setting for login hardening
- Additional migration coverage


## Phase 11 additions

- Login rate limiting with configurable window and audit logging
- Signed checkout preview scaffold and stronger Stripe-style webhook verification
- Launch wizard custom steps and action links
- API analytics CSV export and scope summary
