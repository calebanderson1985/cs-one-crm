# Deployment Guide

## Requirements
- PHP 8.1+
- MySQL 8+
- Web server pointing document root to `public/` (or expose the full app carefully for internal use)
- Write access to `storage/uploads`, `storage/logs`, and `storage/cache`

## Install
1. Upload repository files to your server.
2. Copy `config/database.example.php` to `config/database.php` if you want to preseed credentials manually, or use the browser installer.
3. Create the target MySQL database.
4. Open `install.php`.
5. Enter tenant/admin/install information.
6. Finish go-live tasks in **Onboarding**.

## Health checks
- App endpoint: `public/index.php`
- Health endpoint: `public/health.php`

## Worker / cron
Run regularly to process workflows and outbound communications:

```bash
php /path/to/project/cron/worker.php
```

Example crontab every minute:

```bash
* * * * * /usr/bin/php /var/www/cs-one-crm/cron/worker.php >> /var/www/cs-one-crm/storage/logs/worker.log 2>&1
```

## Recommended post-install steps
1. Open Branding Center and set app name, support email, accent color, login headline, and footer branding.
2. Open Billing and confirm plan, seats, renewal date, and starter invoices.
3. Open Onboarding and complete the go-live checklist.
4. Configure email/SMS providers in System Settings.
5. Review permissions for each role.
6. Create additional managers and agents.
