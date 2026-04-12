# CS One CRM Phase 13 Deployment Guide

## Requirements
- PHP 8.0+
- MySQL 8+
- PDO MySQL enabled
- writable `storage/` directory

## Install
1. Copy files to the server.
2. Create `config/database.php` from `config/database.example.php` or `.env` conventions used by your environment.
3. Open `install.php` for a fresh install.
4. For upgrades, run:
   - `php scripts/migrate.php`

## Worker setup
Schedule the worker:

```bash
php cron/worker.php
```

The worker now records a heartbeat in `worker_heartbeats`, which the Ops Console displays.

## Maintenance
Use the Maintenance Center in the app or automate cleanups later with a cron wrapper around application logic.


## Phase 14 additions
- Support Center
- Audit filters and CSV export
- Company suspension/reactivation controls
