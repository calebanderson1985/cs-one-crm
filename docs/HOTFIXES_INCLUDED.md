# Phase 18 installable hotfixes included

This package includes the critical fixes requested during live installation and upgrade testing.

## Included fixes
- `install.php` now repairs key schema gaps during install.
- `database/schema.sql` now includes `login_attempts`.
- `database/schema.sql` now includes `onboarding_steps.action_url`.
- `database/migrations/010_phase18_install_hotfix.sql` adds the same fixes for upgraded installs.
- `app/Models/LoginAttempt.php` now fails safely if `login_attempts` is missing and keeps `clearFailures()` compatibility.
- `app/Models/OnboardingStep.php` now seeds defaults with upserts instead of duplicate inserts.

## Fixes specifically covering previous errors
- Undefined feature seed keys in installer
- Missing `feature_categories.category_name`
- Duplicate onboarding step inserts
- Missing `login_attempts` table during login
- Missing `clearFailures()` method mismatch

## Recommended commands for upgrades
```bash
php scripts/migrate.php
```
