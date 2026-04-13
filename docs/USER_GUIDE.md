# CS One CRM Phase 16 User Guide

## New in Phase 16
- Add internal or client-visible comments to support tickets.
- Create escalation rules under **SLA Policies & Escalation Rules**.
- Run `php scripts/migrate.php` after upgrade, then keep the cron worker active so overdue tickets can auto-escalate.
- Client users now see a simplified **Help Center** experience for published client-facing knowledge-base articles.

## Support ticket comments
1. Open **Support Center**.
2. Create or open a ticket row.
3. Use the comment form in the Actions column.
4. Choose **Internal** for staff-only notes or **Client Visible** for help-center-safe updates.
5. Save the comment.

## Escalation rules
1. Open **SLA Policies**.
2. In **Create Escalation Rule**, define the matching priority/category.
3. Set how many hours after breach the rule should apply.
4. Optionally assign a new owner, priority, or status.
5. Add the comment template that should be written when the escalation runs.
6. Save the rule.

## Auto-escalation processing
- The cron worker now processes workflow queue, outbound communications, and overdue support escalation checks.
- Example: `php cron/worker.php`
- When a rule fires, the ticket is updated, a comment is written, and the assigned owner receives a notification.

## Help Center
- Client users only see published client-facing articles.
- Staff users can still create, edit, publish, and delete articles from the same screen.


## Phase 17
- Support Center now supports threaded ticket replies.
- Queue an email reply to the requester directly from a ticket reply form.
- Inbound email can create new tickets or append replies through `public/email_ingest.php`.
- Configure the inbound endpoint with the `support_ingest_token` setting.
