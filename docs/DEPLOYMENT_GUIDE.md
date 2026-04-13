# CS One CRM Phase 16 Deployment Guide

## Upgrade steps
1. Deploy the Phase 16 codebase.
2. Run `php scripts/migrate.php`.
3. Confirm the new tables exist:
   - `support_ticket_comments`
   - `support_escalation_rules`
4. Keep cron running for support escalations:
   - `php cron/worker.php`
5. Review SLA escalation rules in the admin UI.

## Operational notes
- Auto-escalation depends on the cron worker.
- Client-visible comments are stored separately from internal notes but on the same ticket.
- Help Center visibility depends on article `visibility_scope='client'` and `is_published=1`.


## Phase 17 email ingestion
- Expose `public/email_ingest.php` behind HTTPS.
- Send a bearer token or `X-INGEST-TOKEN` header matching the `support_ingest_token` setting.
- Pass `company_id`, `from_email`, `subject`, `body_text`, and optional `message_id`/`in_reply_to`.
- Use `php scripts/migrate.php` before enabling inbound email in an upgraded environment.
