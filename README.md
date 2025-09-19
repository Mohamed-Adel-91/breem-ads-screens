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
