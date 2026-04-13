# CS One CRM Phase 19

Phase 19 extends the installable CRM with:
- provider-native mailbox polling scaffolding via IMAP
- inbound support attachment ingestion and scan logging
- a client-facing support reply portal
- a more business-oriented login experience and application shell
- richer client profile fields so records feel like CRM accounts, not a compact contact list

## Fresh install
1. Upload the package.
2. Visit `/install.php`.
3. Complete database setup.

## Upgrading
Run:

```bash
php scripts/migrate.php
```

## New endpoints
- `public/email_ingest.php`
- `public/mailbox_poll.php`
- `public/support_attachment.php` via `index.php?page=support_attachment&id=...`

## New cron
- `cron/mailbox_poll.php`
