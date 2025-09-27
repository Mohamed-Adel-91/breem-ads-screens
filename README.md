<p align="center"><a href="https://laravel.com" target="_blank"><img src="public/frontend/assets/logo.png" width="400" alt="breem Logo"></a></p>

## About Breem

- Breem is a platform for managing and monitoring social media campaigns. It is built on Laravel and Vue.js. It is a work in progress.
- An ad screens system controls Android TV ads view, and profile landing page.
- A dashboard for monitoring and managing social media campaigns.

## Deployment

After updating the codebase, run the database migrations to apply schema changes:

```bash
php artisan migrate --force
```

**Breaking Change:** The `admins` table no longer contains a `role` column. Ensure any custom code or integrations remove references to this column before deploying.

### Ads System Runtime Requirements

The ad screens features rely on several long-running services and configuration values. Make sure the following are in place whenever the project is deployed:

- **Public storage symlink** – run `php artisan storage:link` on each environment to expose uploaded media to the player applications.
- **Queue worker** – keep a worker running (for example via Supervisor) with `php artisan queue:work` so playlist updates, asset downloads, and other background jobs are processed promptly.
- **Scheduler** – execute the scheduler every minute using `* * * * * php /path/to/artisan schedule:run` in cron or trigger it manually with `php artisan schedule:run` to refresh playlists and send heartbeat alerts.
- **Environment configuration** – review the `.env` file and set the ads-specific variables:
  - `SCREENS_HMAC_SECRET` – shared secret used to sign requests between the screens and the API.
  - `SCREENS_SIGNATURE_LEEWAY` – number of seconds of clock drift allowed when validating signatures.
  - `SCREENS_HEARTBEAT_INTERVAL` – expected heartbeat frequency from each screen in seconds.
  - `SCREENS_PLAYLIST_TTL` and `SCREENS_CONFIG_TTL` – cache lifetimes that control how often screens fetch new playlists and configuration.
  - `PLAYLIST_TTL` and `HEARTBEAT_OFFLINE_THRESHOLD` – backend thresholds for playlist caching and marking screens offline.
  - `SLACK_WEBHOOK_URL` – optional webhook used to post operational alerts when screens go offline.

# Overview

Breem Ads Screens combines a Laravel-powered administration dashboard with an Android device player API. Marketing teams curate campaigns, playlists, and locations through the web admin, while registered Android players poll the API for signed configuration payloads, upload health heartbeats, and download synchronized media.

Key user journeys:

- **Campaign managers** schedule creatives, assign screens, and review analytics from the Laravel admin UI.
- **Field technicians** register new Android devices using provisioning codes and monitor device connectivity.
- **Android screen players** authenticate via signed requests, fetch the latest playlists, report heartbeat metrics, and request fallback media when necessary.

Authoritative API contracts, request/response samples, and webhook formats are maintained in `docs/android-device-api.md`.

# Architecture

The solution consists of three cooperating layers:

1. **Laravel Admin (Monolith)** – Hosts the management interface, REST API controllers, background jobs, and scheduler definitions. It persists screen state, playlist manifests, and audit logs in the relational database.
2. **Android Device Player** – A lightweight Kotlin service that calls the API, validates HMAC-signed payloads using `SCREENS_HMAC_SECRET`, plays back downloaded media, and uploads heartbeats according to `SCREENS_HEARTBEAT_INTERVAL`.
3. **Media Storage** – Uses Laravel's filesystem abstraction (default `public` disk) for campaign assets. Production deployments can swap in S3-compatible storage by configuring `FILESYSTEM_DISK` and corresponding credentials.

Supporting services include database-backed queues (`QUEUE_CONNECTION=database`), Laravel scheduler-driven tasks (`php artisan schedule:run`), and optional Slack alerts for operations.

# Setup

## Prerequisites

- PHP 8.2+ with Composer
- Node.js 18+ with npm
- SQLite (default) or MySQL/PostgreSQL for production
- ffprobe binary (from FFmpeg) for media metadata when `ADS_TRY_FFPROBE=true`
- Android device(s) running the Breem player build with network connectivity

## Bootstrap steps

1. **Clone & install dependencies**
   ```bash
   git clone git@github.com:breem/breem-ads-screens.git
   cd breem-ads-screens
   composer install
   npm install
   ```
2. **Copy & configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Fill in database credentials, storage drivers, and the ads-specific keys outlined below.
3. **Build front-end assets**

   ```bash
   npm run build
   ```
   During local development you can run `npm run dev` instead.
4. **Run migrations & seeders** – See the Seeding section for details.
5. **Start queues and scheduler** – See the Jobs & Scheduler section.
6. **Provide device credentials** – Share the generated provisioning codes with Android field teams to enroll devices. API usage details live in `docs/android-device-api.md`.

# Environment variable reference

| Key | Description |
| --- | --- |
| `APP_URL` | Base URL served to the Android players for API requests and media downloads. |
| `SCREENS_HMAC_SECRET` | Shared secret used by Android devices to sign `X-Screens-Signature` headers. |
| `SCREENS_SIGNATURE_LEEWAY` | Allowed clock skew (seconds) when validating signed requests. |
| `SCREENS_HEARTBEAT_INTERVAL` | Expected heartbeat cadence from each device. |
| `SCREENS_PLAYLIST_TTL` / `SCREENS_CONFIG_TTL` | Cache lifetimes (seconds) for playlist/config endpoints. |
| `PLAYLIST_TTL` | Backend playlist cache expiration used when recalculating manifests. |
| `HEARTBEAT_OFFLINE_THRESHOLD` | Threshold (seconds) before a device is flagged offline in the admin. |
| `SLACK_WEBHOOK_URL` | Optional webhook for downtime or sync failure alerts. Leave empty to disable Slack. |
| `ADS_TRY_FFPROBE` | Enables ffprobe duration sniffing before persisting assets. |
| `FFPROBE_BIN` | Path to the ffprobe binary. Override if not in `$PATH`. |
| `ADS_FALLBACK_TYPE` | Fallback media type (`image` or `video`) returned when playlists fail. |
| `ADS_FALLBACK_URL` | Asset path/URL for fallback playback. |
| `ADS_FALLBACK_DURATION` | Fallback media duration in seconds for image slides. |
| `ADMIN_FIRST_NAME` / `ADMIN_LAST_NAME` | Seeded super admin display name. |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Credentials for the initial administrator account. |

Refer to `config/ads.php` and `config/admin.php` for full context and defaults.

# Media Storage guidance

- Create the public storage symlink after deployment: `php artisan storage:link`.
- Keep campaign assets on the configured filesystem disk. When using S3, ensure signed URLs remain reachable by Android devices.
- Use dedicated buckets/folders per environment (e.g., `breem-ads-staging/` vs `breem-ads-production/`).
- Documented ingestion and purge policies reside in `docs/media-pipeline.md`.

# ffprobe installation tips

- macOS (Homebrew): `brew install ffmpeg`
- Ubuntu/Debian: `sudo apt-get install ffmpeg`
- Alpine: `apk add ffmpeg`
- Windows (WSL or native): download the static build from [FFmpeg.org](https://ffmpeg.org/download.html) and add the `bin` folder to your PATH.

Confirm installation with `ffprobe -version`. Update `FFPROBE_BIN` if the binary lives outside your PATH.

# Seeding instructions

Run the default seeders to create the admin account, demo places, and sample playlists:

```bash
php artisan migrate --seed
```

Override seeded admin details by providing `ADMIN_EMAIL` and `ADMIN_PASSWORD` before running the seeder. To reseed from scratch, reset the database with `php artisan migrate:fresh --seed`.

# Jobs & Scheduler expectations

- **Queues**: Start a worker in the background (`php artisan queue:work --queue=default,media`) or configure Horizon/Supervisor. Queue jobs process playlist regeneration, asset downloads, and heartbeat notifications.
- **Scheduler**: Run `php artisan schedule:run` every minute via cron. Scheduled tasks prune expired playlists, escalate offline alerts, and sync Slack notifications when enabled.
- **Monitoring**: Capture logs from both queue workers and scheduler runs for debugging player sync issues.

# Slack optionality

Operational alerts flow through Slack when `SLACK_WEBHOOK_URL` is populated. If your organization prefers another incident channel, leave the variable empty and configure alternative notification channels within Laravel (e.g., email, PagerDuty).

# Troubleshooting

| Symptom | Next steps |
| --- | --- |
| Devices report `401 Unauthorized` | Confirm `SCREENS_HMAC_SECRET` matches on both admin and Android builds. Verify device clock drift is within `SCREENS_SIGNATURE_LEEWAY`. |
| Devices stuck on fallback media | Ensure playlists are published, queue workers are running, and ffprobe is installed if media durations are missing. |
| Media 404 errors | Re-run `php artisan storage:link` and confirm the filesystem driver exposes public URLs reachable by the devices. |
| Slack alerts not firing | Check `SLACK_WEBHOOK_URL`, queue worker logs, and scheduler cron execution. |
| Seeder fails on unique constraints | Reset the database (`php artisan migrate:fresh --seed`) or adjust seeded emails/UIDs in `.env`. |

# Security notes

- Require HTTPS for all device-to-API traffic and enforce TLS pinning on Android where feasible.
- Rotate `SCREENS_HMAC_SECRET` periodically and redeploy player builds with the new secret.
- Limit admin panel access via SSO or IP allowlists and monitor audit logs.
- Store ffprobe and media binaries in read-only directories to prevent tampering.
- Follow Laravel's security updates and apply patches promptly.

