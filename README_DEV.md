# Project Runbook (Local Development)

This document explains how to run the project locally, how environment variables are organized, how migrations and seeders work, and common failure points.

## Supported versions

| Component | Supported version | Where to confirm |
| --- | --- | --- |
| PHP | >= 8.2 (composer.json) | composer.json |
| Composer | TBD | CI, tooling docs, or local environment |
| Node.js | TBD | package.json, .nvmrc, or CI |
| Database | SQLite/MySQL/PostgreSQL (version TBD) | config/database.php, infra/CI |

## Quick start (local)

1. Install prerequisites (see Supported versions table):
   - PHP + Composer
   - Node.js + npm
   - SQLite (default) or MySQL/PostgreSQL
   - ffprobe (optional, only if `ADS_TRY_FFPROBE=true`)
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
4. Prepare the database:
   - SQLite (default):
     ```bash
     New-Item -ItemType File -Path database/database.sqlite -Force
     php artisan migrate --seed
     ```
   - MySQL/PostgreSQL: set `DB_*` in `.env`, then run `php artisan migrate --seed`.
5. Link public storage (required for media):
   ```bash
   php artisan storage:link
   ```
6. Run assets:
   - Dev: `npm run dev`
   - Build: `npm run build`
7. Start the app and workers:
   - App: `php artisan serve`
   - Queue: `php artisan queue:work --queue=default,media`
   - Scheduler: `php artisan schedule:run` (every minute) or `php artisan schedule:work` (local loop)

### `composer run dev` details and prerequisites

The `dev` script in `composer.json` runs:
- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail --timeout=0`
- `npm run dev`
via `npx concurrently`.

Prerequisites:
- `composer install` (installs `laravel/pail`)
- `npm install` (installs `concurrently` and Vite toolchain)
- `npx` available (bundled with npm)

If it fails, run the four commands in separate terminals.

## Verification in 60 seconds

- [ ] `php artisan migrate:status` runs without error.
- [ ] `php artisan serve` starts and `/en/admin-panel/login` renders.
- [ ] `php artisan queue:work --once` exits cleanly.
- [ ] `php artisan schedule:run` completes without error.
- [ ] `php artisan storage:link` created `public/storage`.

## Local URLs

- Web: `http://localhost:8000` (or whatever `php artisan serve` prints)
- Admin login: `/en/admin-panel/login`

## Environment variables

Source of truth: `.env.example`, plus `config/ads.php`, `config/admin.php`, and `config/services.php`.

### Minimal required (local boot and admin login)

| Key | Why it matters | Notes |
| --- | --- | --- |
| `APP_KEY` | App encryption key | Generate with `php artisan key:generate` |
| `APP_URL` | Asset and storage URLs | Use your local server URL |
| `DB_CONNECTION` | Database driver | Default is `sqlite` |
| `DB_DATABASE` | SQLite file path or DB name | Create `database/database.sqlite` for SQLite |
| `ADMIN_EMAIL` | Seeded admin login | Required if running seeders |
| `ADMIN_PASSWORD` | Seeded admin login | Required if running seeders |
| `CMS_ADMIN_EMAIL` | Seeded CMS admin login | Required if running seeders |
| `CMS_ADMIN_PASSWORD` | Seeded CMS admin login | Required if running seeders |

### Optional or feature-specific

| Keys | When needed | Notes |
| --- | --- | --- |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | MySQL/PostgreSQL | Required when `DB_CONNECTION` is `mysql` or `pgsql` |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Non-default drivers | Defaults are `database` in `.env.example` |
| `FILESYSTEM_DISK`, `AWS_*` | S3 or alternate storage | Only if not using local `public` disk |
| `MAIL_*` | Real email delivery | Default is log mailer |
| `REDIS_*`, `MEMCACHED_HOST` | Cache/queue overrides | Only if switching drivers |
| `SCREENS_*` | Android screen API | If `SCREENS_HMAC_SECRET` is empty, signature validation is bypassed |
| `PLAYLIST_TTL`, `HEARTBEAT_OFFLINE_THRESHOLD` | Monitoring logic | Defaults in `.env.example` |
| `SLACK_WEBHOOK_URL` | Slack alerts | Leave empty to disable |
| `ADS_TRY_FFPROBE`, `FFPROBE_BIN` | Video duration probing | Requires ffprobe in PATH |
| `ADS_FALLBACK_*` | Fallback media | Defaults in `.env.example` |
| `ADMIN_FIRST_NAME`, `ADMIN_LAST_NAME`, `CMS_ADMIN_FIRST_NAME`, `CMS_ADMIN_LAST_NAME` | Seed metadata | Optional display fields |

## Migrations

- Run: `php artisan migrate`
- Reset: `php artisan migrate:fresh --seed`
- Migrations include sessions, cache, jobs, job_batches, failed_jobs, and domain tables (screens, ads, schedules, logs, CMS pages, etc.).

If you change `.env` values that affect config, run:
```bash
php artisan config:clear
```

## Seeding

Default seeding (`php artisan migrate --seed`) runs:
1. `RoleSeeder`
2. `AdminUserSeeder`
3. `HomePageSeeder`
4. `WhoWeArePageSeeder`
5. `ContactUsPageSeeder`
6. `DemoSeeder`
7. `ReportsAndLogsSeeder`

Notes:
- `AdminUserSeeder` uses the `ADMIN_*` and `CMS_ADMIN_*` env vars to create admin users. Provide real values before seeding to avoid invalid or duplicate records.
- `DemoSeeder` creates a sample place, screen, user, ad, and schedule for testing.

## Queues and scheduler

- Queue connection defaults to `database`. Start a worker:
  ```bash
  php artisan queue:work --queue=default,media
  ```
- Scheduler runs jobs in `app/Console/Kernel.php`:
  - `CheckScreenHealthJob` every minute
  - `CheckExpiringAdsJob` daily at 09:00
- Local convenience: `php artisan schedule:work` runs the scheduler loop in one terminal.

## Windows 11 notes

- SQLite: create `database/database.sqlite` using PowerShell (see Quick start) and ensure the `database` folder is writable.
- Scheduler: use `php artisan schedule:work` in a dedicated terminal, or configure Task Scheduler to run `php artisan schedule:run` every minute. Set the "Start in" directory to the repo root.

## Common failure points

- `APP_KEY` missing: run `php artisan key:generate`.
- SQLite file missing or not writable: create `database/database.sqlite` and ensure file permissions.
- Database tables missing: run `php artisan migrate` before starting the app.
- `SESSION_DRIVER=database` or `CACHE_STORE=database` errors: ensure migrations ran (sessions/cache tables).
- Queue not running: playlists, media processing, and alerts do not update.
- Scheduler not running: screen health and expiring ads jobs do not run.
- Storage 404s: run `php artisan storage:link` and confirm `APP_URL` is correct.
- HMAC auth failures: `SCREENS_HMAC_SECRET` must match Android device configuration.
- ffprobe errors: set `ADS_TRY_FFPROBE=false` or install ffprobe and set `FFPROBE_BIN`.
- Admin login fails after seeding: verify `ADMIN_EMAIL`/`ADMIN_PASSWORD` and `CMS_ADMIN_*` values.
- `composer run dev` fails: ensure `npm install` ran and `npx` is available.

## Assumptions

- Local development uses SQLite unless `DB_CONNECTION` is changed.
- Queue, cache, and session drivers stay on database for local work.
- ffprobe is optional and only required when asset duration detection is enabled.

## Onboarding checklist (new dev)

- [ ] Install PHP 8.2+, Composer, Node 18+, and a database (SQLite/MySQL/Postgres).
- [ ] Clone the repo and run `composer install` and `npm install`.
- [ ] Copy `.env.example` to `.env` and run `php artisan key:generate`.
- [ ] Set `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `CMS_ADMIN_EMAIL`, `CMS_ADMIN_PASSWORD`, and `SCREENS_HMAC_SECRET`.
- [ ] Create `database/database.sqlite` (if using SQLite).
- [ ] Run `php artisan migrate --seed`.
- [ ] Run `php artisan storage:link`.
- [ ] Start dev processes (`composer run dev` or run server/queue/vite manually).
- [ ] Log into `/en/admin-panel/login` with the seeded admin credentials.
- [ ] Run the "Verification in 60 seconds" checklist.
