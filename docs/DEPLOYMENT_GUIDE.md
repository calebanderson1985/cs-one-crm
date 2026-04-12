# Deployment Guide

## Requirements
- PHP 8.1+
- MySQL 8+
- Apache or Nginx
- `pdo_mysql` enabled
- Cron access for background jobs

## Recommended layout
- Point your web server document root to `/public`
- Keep `/storage` writable by the web server
- Keep database credentials out of version control

## Setup
1. Clone or extract this repository.
2. Copy `.env.example` to `.env` and set your environment values.
3. Create an empty MySQL database.
4. Open `install.php` in the browser and complete the installer.
5. Verify login at `/public/index.php?page=login`.

## Cron / background worker
Run the worker every minute for workflow jobs and outbound messages:

```bash
* * * * * /usr/bin/php /path/to/project/cron/worker.php >> /path/to/project/storage/logs/worker.log 2>&1
```

To process a single company only:

```bash
php cron/worker.php 1
```

## Production notes
- Serve the app over HTTPS only.
- Rotate API tokens regularly.
- Restrict direct public access to `/storage`.
- Use a real SMTP and SMS provider before launch.
- Back up the database and uploaded files.
