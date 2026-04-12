# Deployment Guide - Phase 9

## Requirements
- PHP 8.1+
- MySQL 8+ or MariaDB compatible
- Web root pointed at `/public` when possible

## Install
1. Upload the repo to your server
2. Create database credentials in `config/database.php` after installation or via installer
3. Open `install.php` and complete setup

## Post-install
- Review `System Settings`
- Generate scoped API tokens under `API Token Center`
- Complete the `Launch Wizard`
- Configure your cron job for `cron/worker.php`

## Webhooks
Stripe-compatible webhook scaffold is exposed at `public/webhook.php`. This build stores audit evidence and is ready for a fuller provider integration layer.

## Migrations
Run:

```bash
php scripts/migrate.php
```

This applies forward-only SQL files from `database/migrations`.


## Phase 10 deployment notes

- Run `php scripts/migrate.php` after upgrading.
- Set `stripe_webhook_secret` before enabling live billing webhooks.
- Restrict the company switch page to trusted super admins only.
