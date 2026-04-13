## Phase 18 installable hotfix package

This build is a refreshed installable package based on the latest recoverable Phase 17 repo with the critical Phase 18 install/auth hotfixes integrated.

Included fixes:
- installer compatibility for mixed feature seed formats
- feature_categories seeding with category_name
- login_attempts schema repair and auth-safe LoginAttempt model
- onboarding_steps action_url schema repair
- onboarding default-step upserts to avoid duplicate key failures

---

# CS One CRM - Phase 17

A PHP/MySQL multi-tenant CRM platform with CRM core, communications, workflows, AI hooks, API tokens, diagnostics, queue operations, support governance, and Phase 17 conversation features.

## Phase 17 additions
- threaded support ticket conversations
- email-to-ticket ingestion endpoint
- queued email replies from ticket threads
- inbound email logging against support tickets

## Run locally
- configure database
- run `install.php` for fresh install or `php scripts/migrate.php` for upgrades
- serve `public/` through your web server
- post inbound email payloads to `public/email_ingest.php` with the `support_ingest_token`
