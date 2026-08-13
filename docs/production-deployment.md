# Production deployment, backup and rollback

The authoritative deployment sequence, the worker and scheduler supervision Breem
requires, how backups are taken and *proved*, and what to do when a release goes wrong.

Adapt paths, users and service names to the actual host. Do not copy a command whose
effect you cannot describe.

| Companion | Document |
|---|---|
| Every configuration value | [production-env.md](production-env.md) |
| Pre-launch sign-off | [production-launch-checklist.md](production-launch-checklist.md) |
| Day-to-day operations | [operations-runbook.md](operations-runbook.md) |
| Pairing physical screens | [device-repairing-runbook.md](device-repairing-runbook.md) |

Placeholders used throughout: `/var/www/breem` (release root), `deploy` (deploy user),
`www-data` (web/PHP user), `breem_production` (schema).

---

## 0. What deployment does *not* need

- **No Node, no npm, no `npm run build`, no `public/build`.** The admin is Blade +
  Bootstrap 4 served from `public/admin-assets/`; the public site from
  `public/frontend/`. The Vite/Tailwind/PostCSS scaffold files stay in the repository
  as repository-standard structure and take no part in deployment.
- **No `php artisan storage:link`.** Managed uploads are written directly under
  `public/cms/` and `public/upload/`. `public/storage` does not exist and nothing reads
  it.
- **No Redis.** Cache, queue and sessions all use the database.

---

## 1. Backups — take them before anything else

A deployment that migrates is not reversible without a backup. Neither is a first
retention prune.

### 1.1 Database

```bash
#!/usr/bin/env bash
# /usr/local/bin/breem-db-backup.sh
set -euo pipefail

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DEST="/var/backups/breem"
mkdir -p "$DEST"

# Credentials come from a 0600 defaults file, never from the command line —
# a password in argv is visible to every user via `ps`.
#   /root/.breem-backup.cnf:
#     [client]
#     user=breem_backup
#     password=...
#     host=127.0.0.1
mysqldump --defaults-extra-file=/root/.breem-backup.cnf \
  --single-transaction \
  --quick \
  --routines \
  --triggers \
  --events \
  --default-character-set=utf8mb4 \
  breem_production \
  | gzip -9 > "$DEST/breem-db-$STAMP.sql.gz"

# Fail loudly on a truncated dump rather than keeping a useless file.
gzip -t "$DEST/breem-db-$STAMP.sql.gz"

# A dump that does not end with MySQL's completion marker is incomplete.
if ! zcat "$DEST/breem-db-$STAMP.sql.gz" | tail -5 | grep -q 'Dump completed'; then
  echo "FATAL: dump did not complete" >&2
  exit 1
fi

echo "ok: $DEST/breem-db-$STAMP.sql.gz"
```

Why each flag matters:

- `--single-transaction` — a consistent snapshot on InnoDB **without locking writes**, so
  heartbeats keep landing while the dump runs. Omit it and the fleet stalls.
- `--quick` — streams rows instead of buffering a table in RAM. `screen_logs` and
  `playback_logs` get large.
- `--routines --triggers --events` — schema objects a data-only dump would silently drop.
- `--default-character-set=utf8mb4` — Arabic CMS content and place names. Get this wrong
  and the restore is mojibake.
- `gzip -t` and the `Dump completed` check — **a file existing is not a backup.**

The backup user needs only `SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER, PROCESS`.
It never needs write access.

### 1.2 Media — and why it must be paired with the database

The database stores **relative paths**; the bytes live on disk. A database restored to a
different point in time than the media leaves ads whose `file_path` points at nothing,
and screens that 404 on their creative.

```bash
#!/usr/bin/env bash
# /usr/local/bin/breem-media-backup.sh
set -euo pipefail

STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
DEST="/var/backups/breem"
APP="/var/www/breem"
mkdir -p "$DEST"

tar -czf "$DEST/breem-media-$STAMP.tar.gz" \
  -C "$APP" \
  public/cms \
  public/upload \
  public/images

tar -tzf "$DEST/breem-media-$STAMP.tar.gz" > /dev/null
echo "ok: $DEST/breem-media-$STAMP.tar.gz"
```

**Back up:** `public/cms/`, `public/upload/`, `public/images/` — and whatever
`MEDIA_UPLOAD_ROOT` points at if it is set.

**Do not back up as user data:** `storage/framework/*` (cache, compiled views,
sessions), `storage/logs/`, `bootstrap/cache/`, `vendor/`, `node_modules/`, or any
`storage/framework/testing/` upload root. They are all reproducible, and including them
makes the archive large enough that people stop taking it.

**Pair them.** Use the same `STAMP` for both, or wrap both scripts in one so a restore
can always find the matching pair:

```bash
/usr/local/bin/breem-db-backup.sh && /usr/local/bin/breem-media-backup.sh
```

### 1.3 Schedule, retention and storage expectations

```
# /etc/cron.d/breem-backup
15 2 * * * root /usr/local/bin/breem-db-backup.sh && /usr/local/bin/breem-media-backup.sh
```

| Property | Requirement |
|---|---|
| Frequency | Daily, plus an on-demand run immediately before any deploy that migrates |
| Retention | 7 daily, 4 weekly, 12 monthly — at minimum long enough to notice a slow corruption |
| Off-host copy | **Required.** A backup on the same disk as the database is not a backup |
| Encryption at rest | Required. The dump contains admin password hashes, contact submissions and encrypted device secrets |
| Permissions | `0600`, owned by root; `/var/backups/breem` mode `0700` |
| Naming | UTC ISO-8601 stamp, as above — sortable and unambiguous across time zones |

> **The device secrets in the dump are encrypted with `APP_KEY`, which is not in the
> dump.** Back `APP_KEY` up separately and store it somewhere other than beside the
> database backups. Losing it makes every backup useless for restoring a paired fleet,
> and keeping it next to them means one stolen archive yields both.

### 1.4 Restore procedure

**Never restore over the live database.** Restore into a scratch schema, verify, and only
then decide.

```bash
# 1. A scratch schema. Nothing here touches breem_production.
mysql --defaults-extra-file=/root/.breem-admin.cnf \
  -e "CREATE DATABASE breem_restore_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Load the dump.
zcat /var/backups/breem/breem-db-20260813T021500Z.sql.gz \
  | mysql --defaults-extra-file=/root/.breem-admin.cnf breem_restore_check

# 3. Prove the shape and the volumes.
mysql --defaults-extra-file=/root/.breem-admin.cnf breem_restore_check -e "
  SELECT COUNT(*) AS tables_present FROM information_schema.tables
   WHERE table_schema='breem_restore_check';
  SELECT 'screens' t, COUNT(*) n FROM screens
  UNION ALL SELECT 'ads', COUNT(*) FROM ads
  UNION ALL SELECT 'places', COUNT(*) FROM places
  UNION ALL SELECT 'screen_device_credentials', COUNT(*) FROM screen_device_credentials
  UNION ALL SELECT 'playback_logs', COUNT(*) FROM playback_logs
  UNION ALL SELECT 'screen_logs', COUNT(*) FROM screen_logs
  UNION ALL SELECT 'reports', COUNT(*) FROM reports
  UNION ALL SELECT 'migrations', COUNT(*) FROM migrations;"

# 4. Media, into a scratch directory.
mkdir -p /tmp/breem-restore-check
tar -xzf /var/backups/breem/breem-media-20260813T021500Z.tar.gz -C /tmp/breem-restore-check
find /tmp/breem-restore-check -type f | wc -l

# 5. Clean up.
mysql --defaults-extra-file=/root/.breem-admin.cnf -e "DROP DATABASE breem_restore_check;"
rm -rf /tmp/breem-restore-check
```

A restore counts as **verified** only when all of these hold:

- [ ] every expected table is present, `migrations` included
- [ ] row counts are within a plausible delta of production
- [ ] Arabic CMS text renders correctly, not as `?` or mojibake
- [ ] the media file count matches roughly what production holds
- [ ] `screen_device_credentials` row count matches the paired-screen count

Run this **quarterly**, and after any change to the backup scripts, MySQL version, or
character set. Record the date each time.

> **A restore has not been performed against real production infrastructure from this
> repository.** The procedure above is documented and internally consistent, but the
> commands have not been executed against a production dump — there is no production
> host, database or backup destination reachable from here. **Executing §1.4 once, on
> the real backup, is a launch requirement** and is tracked in the launch checklist.

---

## 2. Queue worker supervision

`QUEUE_CONNECTION=database`. **A continuously running worker is required.** Without one,
offline alerts and expiring-ad notifications accumulate in `jobs` and nobody is ever
told anything — the failure mode is silence, not an error.

### systemd (preferred on a modern Linux host)

```ini
# /etc/systemd/system/breem-queue.service
[Unit]
Description=Breem queue worker
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/breem
# --tries=3 matches the notification retry policy; --timeout=60 exceeds the longest
# job timeout (CheckScreenHealthJob::$timeout = 55) and stays under the queue's
# retry_after of 90s, so a slow job is never released and run twice concurrently.
# --max-time recycles the process hourly, which bounds any slow memory growth
# without needing a memory watchdog.
ExecStart=/usr/bin/php /var/www/breem/artisan queue:work database \
  --queue=default \
  --sleep=3 \
  --tries=3 \
  --timeout=60 \
  --max-jobs=1000 \
  --max-time=3600 \
  --rest=0
Restart=always
RestartSec=5
StandardOutput=append:/var/log/breem/queue.log
StandardError=append:/var/log/breem/queue.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo mkdir -p /var/log/breem && sudo chown www-data:www-data /var/log/breem
sudo systemctl daemon-reload
sudo systemctl enable --now breem-queue
systemctl status breem-queue --no-pager
```

### Supervisor (shared hosting, cPanel, older hosts)

```ini
; /etc/supervisor/conf.d/breem-queue.conf
[program:breem-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/breem/artisan queue:work database --sleep=3 --tries=3 --timeout=60 --max-time=3600
directory=/var/www/breem
autostart=true
autorestart=true
startsecs=5
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/breem/queue.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
; Give a running job time to finish rather than killing it mid-notification.
stopwaitsecs=70
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status breem-queue
```

One worker is sufficient for the launch workload: a fleet-wide outage queues one
notification per screen, and each is a single mail send. Raise `numprocs` only with
evidence from `queue:monitor`.

### The parameters, and why they are those values

| Flag | Value | Reason |
|---|---|---|
| `--tries` | `3` | Matches the notifications' own retry policy. |
| `--timeout` | `60` | Must exceed the longest job timeout (55 s) and stay below `retry_after` (90 s). Violate either and a job is either killed early or run twice. |
| `--sleep` | `3` | Poll interval when idle. Alert latency budget is minutes, not milliseconds. |
| `--max-time` | `3600` | Recycles the process hourly, releasing any accumulated memory. |
| `--max-jobs` | `1000` | Second recycling bound for a busy period. |
| `Restart=always` / `autorestart=true` | — | The worker must survive a crash and start at boot. |

### Restarting the worker after a deploy — required

A long-running worker holds the **old** code in memory. New code is not picked up until
the process restarts, so a deploy without this step ships nothing to the queue.

```bash
php artisan queue:restart
```

This is the safe, Laravel-native mechanism: it sets a restart signal that each worker
notices **after finishing its current job**, then exits cleanly, and systemd/Supervisor
starts a fresh one. Never `kill -9` a worker — that abandons a job mid-flight, and a
notification job killed after sending but before completing will be retried and sent
twice.

### Failed jobs

```bash
php artisan queue:failed                 # list
php artisan queue:monitor database --max=100   # queue depth alarm
php artisan queue:retry <uuid>           # retry one, after fixing the cause
php artisan queue:retry all              # retry everything
```

Operational checks:

| Check | Command | Healthy |
|---|---|---|
| Depth | `php artisan queue:monitor database --max=100` | no alarm |
| Failures | `SELECT COUNT(*) FROM failed_jobs;` | `0`, or a known and triaged set |
| Recent failures | `SELECT id, failed_at, LEFT(exception,120) FROM failed_jobs ORDER BY failed_at DESC LIMIT 10;` | none since the last deploy |

**Never `queue:flush` on a schedule.** A `failed_jobs` row is the evidence that an alert
did not reach a human. Read it, fix the cause, retry it, and only then delete it —
individually, and only once its cause is understood.

---

## 3. Scheduler

**One cron entry. Every minute. Not optional.**

```
# /etc/cron.d/breem-scheduler
* * * * * www-data cd /var/www/breem && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

systemd timer alternative, if the host prefers timers to cron:

```ini
# /etc/systemd/system/breem-schedule.service
[Unit]
Description=Breem scheduler tick
[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/breem
ExecStart=/usr/bin/php /var/www/breem/artisan schedule:run
```

```ini
# /etc/systemd/system/breem-schedule.timer
[Unit]
Description=Run the Breem scheduler every minute
[Timer]
OnCalendar=*:0/1
AccuracySec=1s
Persistent=false
[Install]
WantedBy=timers.target
```

```bash
sudo systemctl enable --now breem-schedule.timer
systemctl list-timers breem-schedule.timer --no-pager
```

### Verify the scheduler surface

```bash
php artisan schedule:list
```

Expect exactly three entries, and **confirm every command named actually exists**:

| Cadence | Task | If the scheduler is not running |
|---|---|---|
| every minute | mark screens offline after the threshold | a dead screen stays "online" for ever and no alert is sent |
| daily 09:00 | notify about ads nearing their end date | finished campaigns keep playing, unannounced |
| daily 03:30 | `model:prune` for `ScreenLog`, `PlaybackLog`, `Report` | operational tables grow without bound |

> Breem has shipped a scheduler entry for a command that **did not exist**
> (`screens:check-status`), so it failed on every tick while `schedule:list` still
> displayed it. That is now pinned by a test — `OfflineDetectionTest` asserts both that
> the sweep is registered and that no phantom command remains. If `schedule:list` ever
> shows a task you cannot run by hand, treat it as a launch blocker.

---

## 4. The deployment sequence

Steps 1–4 are safe at any time. Step 5 onwards changes the running application.

```bash
set -euo pipefail
cd /var/www/breem
```

**1 — Back up the database and media, and confirm the target schema.**

```bash
/usr/local/bin/breem-db-backup.sh
/usr/local/bin/breem-media-backup.sh
php artisan tinker --execute="echo DB::connection()->getDatabaseName();"   # confirm!
```

**2 — Record the current release, so rollback is a known commit and not an archaeology exercise.**

```bash
git rev-parse HEAD | tee /var/www/breem-last-good-sha
php artisan migrate:status | tail -20
```

**3 — Fetch the new code.**

```bash
git fetch --all --tags
git checkout <tag-or-sha>
```

> **Deploy from git, or clear `bootstrap/cache/` first.** `bootstrap/cache/packages.php`
> is git-ignored, so a git-based deploy never carries it. An rsync or zip of a working
> tree *does*, and a stale manifest listing a dev-only provider makes
> `composer install --no-dev` abort with
> `Class "Laravel\Pail\PailServiceProvider" not found`. If you deploy by copying files:
> `rm -f bootstrap/cache/*.php` before step 4. This failure has been reproduced.

**4 — Install production dependencies. No Node.**

```bash
rm -f bootstrap/cache/*.php
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
```

`--no-dev` removes 35 packages including PHPUnit, Faker, Mockery, Pint, Pail, Sail and
`filp/whoops` — so a production host cannot render a Whoops stack trace even if
`APP_DEBUG` were set by mistake. No runtime code references a dev package.

**5 — Verify the environment before touching the schema.**

```bash
php artisan about --only=environment     # production, Debug OFF, https URL
php artisan ops:status; echo "exit=$?"   # must be 0
```

**6 — Migrate.**

```bash
php artisan migrate --force --pretend    # read it
php artisan migrate --force
```

**7 — Rebuild the caches. In this order.**

```bash
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Config first: `route:cache` and `view:cache` both boot the application and would
otherwise capture stale configuration.

> **Do not run `php artisan cache:clear` here.** It is the *application* cache, not a
> build artifact. Flushing it discards every screen's playlist and the notification
> de-duplication markers, so the whole fleet refetches at once and a duplicate expiry
> alert becomes possible. Clear it only when you specifically intend to invalidate
> cached payloads.

| Command | What it clears | Routine deploy step? |
|---|---|---|
| `config:cache` | rebuilds config cache | **yes** |
| `route:cache` | rebuilds route cache | **yes** |
| `view:cache` | precompiles Blade | **yes** |
| `cache:clear` | application cache: playlists, page payloads, alert de-dupe | **no** — only deliberately |
| `optimize:clear` | all of the above, including `cache:clear` | **no** — includes `cache:clear` |

**8 — Restart the queue worker so it runs the new code.**

```bash
php artisan queue:restart
```

**9 — Confirm the scheduler.**

```bash
php artisan schedule:list
```

**10 — Smoke-test.**

```bash
curl -sI https://breem.example/up | head -n 1                        # 200
curl -sI http://breem.example/up  | head -n 1                        # 301 → https
curl -s  -o /dev/null -w '%{http_code}\n' https://breem.example/en    # 200
curl -s  -o /dev/null -w '%{http_code}\n' https://breem.example/ar    # 200
curl -s  -o /dev/null -w '%{http_code}\n' https://breem.example/en/admin-panel/login  # 200
# The Device API must fail CLOSED without credentials:
curl -s  -o /dev/null -w '%{http_code}\n' https://breem.example/api/v1/config          # 401
```

**11 — Probe the Device API properly**, with a staging screen's real credentials: pair,
config, heartbeat, playlist, playback. See
[android-device-api.md](android-device-api.md) for the canonical signing message.
**Never test against a live customer screen without approval.**

**12 — Health.**

```bash
php artisan ops:status; echo "exit=$?"        # 0
php artisan queue:failed                      # empty
tail -50 storage/logs/laravel-$(date -u +%F).log
```

### Maintenance mode — usually skip it

```bash
php artisan down --secret="<one-off-random-string>"   # then browse /<secret> to bypass
php artisan up
```

**Prefer not using it.** `php artisan down` returns 503 for *every* route including
`/api/v1/*`, so:

- heartbeats fail → the fleet goes quiet;
- the offline sweep keeps running → **screens are marked offline and alerts are sent
  for a maintenance window you chose**;
- playlist requests fail → players fall back to their cached playlist, if they have one.

Additive migrations — the only kind Breem has — run in milliseconds and do not need it.
Use `down` only for a genuinely long or destructive operation, keep it under the offline
threshold if you can, and expect offline alerts if you cannot. Warn whoever receives
those alerts first.

### Zero downtime — what is and is not promised

Additive migrations plus a config-cache rebuild give a brief window where a request
might see old code and new schema. Because every migration is additive, old code simply
ignores the new column, so that window is safe.

Nothing here promises zero downtime: a single-server deployment that swaps files in
place has a short period where PHP-FPM's opcache and the file system disagree. If truly
zero-downtime is required, that means atomic release-directory symlink swapping and a
PHP-FPM reload — which is a hosting-architecture decision, not something this repository
can assert.

Safe ordering for a schema change:

1. deploy code that tolerates **both** shapes;
2. migrate;
3. rebuild caches;
4. restart workers;
5. only in a *later* release, remove the compatibility path.

---

## 5. Rollback

Decide first **which** of these went wrong. The remedies are not interchangeable.

### 5.1 Bad application deployment, no migration

The easy case. Revert the code.

```bash
cd /var/www/breem
git checkout "$(cat /var/www/breem-last-good-sha)"
rm -f bootstrap/cache/*.php
composer install --no-dev --optimize-autoloader --no-interaction
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
curl -sI https://breem.example/up | head -n 1
```

### 5.2 Bad deployment that migrated

**Do not reflexively run `migrate:rollback`.** Every Breem migration is additive and
every `down()` drops the column or table it added — so a rollback is a **data delete**.
Rolling back `create_screen_device_credentials_table` destroys the entire fleet's
credentials and forces a manual re-pair of every screen.

Choose in this order:

1. **Roll the code back, leave the schema.** Additive migrations are backward
   compatible: old code ignores the new column. This is almost always right and it loses
   nothing.
2. **Forward-fix.** Write a new migration that corrects the problem. Preferred whenever
   the schema itself is wrong.
3. **Restore from backup.** The only correct answer when a migration *corrupted or
   destroyed data*. Restore the paired database and media (§1.4) into place, accepting
   the loss of everything written since the backup — including heartbeats and
   proof-of-play rows.
4. **`migrate:rollback`.** Last resort, only for a migration you are certain added
   nothing but an empty structure, and only with a fresh backup in hand.

```bash
php artisan migrate:status          # what actually ran
php artisan migrate:rollback --step=1 --pretend   # READ THIS FIRST
```

Migration safety review of the recent (Phase 10–14) migrations:

| Migration | Shape | Lock risk | `down()` destroys |
|---|---|---|---|
| `add_is_active_to_section_items_table` | add column | negligible | the flag |
| `add_admin_audit_columns_to_ads_table` | add nullable FK columns | negligible | the audit trail |
| `add_operational_reporting_indexes` | **add indexes** | see below | the indexes only (safe) |
| `add_maintenance_to_screen_logs_status` | widen an enum | brief metadata lock | narrows the enum — **fails if any row holds the new value** |
| `create_screen_device_credentials_table` | new table | none | **all device credentials** |
| `create_screen_pairing_codes_table` | new table | none | live pairing codes |
| `create_screen_request_nonces_table` | new table | none | replay-protection state |
| `add_acknowledgement_to_screen_logs_table` | add nullable column + index | index build | acknowledgements |

Two things to plan for:

- **Index creation on `screen_logs` / `playback_logs` is the one slow migration.** MySQL
  8 builds secondary indexes online, so writes continue, but on a table with tens of
  millions of rows it takes real time and I/O. Run it in a quiet window and do not
  interrupt it.
- **The enum widening cannot be cleanly reversed** once a `maintenance` row exists. Treat
  it as forward-only.

All new FK columns are nullable with `ON DELETE SET NULL`, so deleting an admin never
deletes an ad or a report — it only forgets who did it.

> **Two destructive cascades to know about before you delete anything:**
> `screens.place_id` is `ON DELETE CASCADE`, so deleting a **Place** deletes its screens,
> and with them their credentials, logs and **playback_logs** — proof-of-play evidence.
> `playback_logs.screen_id` is also `ON DELETE CASCADE`, so deleting a single screen
> destroys its play history. Take a backup before deleting either, and prefer marking a
> screen inactive over deleting it.

### 5.3 Credential or pairing problem

Pairing state is not part of a code rollback.

```bash
# What is actually paired, without printing a secret:
php artisan tinker --execute="
  echo 'screens: ' . App\Models\Screen::count() . PHP_EOL;
  echo 'active credentials: ' . App\Models\ScreenDeviceCredential::whereNull('revoked_at')->count() . PHP_EOL;
"
```

To recover one screen: revoke its device in the admin panel, generate a new pairing code,
and re-pair the player. See [device-repairing-runbook.md](device-repairing-runbook.md).
**A revoked credential cannot be un-revoked** — the plaintext is not stored — so
re-pairing is the only path.

If `APP_KEY` was changed, every `hmac_secret` is undecryptable and the **whole fleet**
must be re-paired. Restore the previous `APP_KEY` if you still have it; that is the only
non-manual fix.

### 5.4 Queue problem

```bash
systemctl status breem-queue            # is it even running?
php artisan queue:failed                # what failed, and why
php artisan queue:monitor database --max=100
php artisan queue:restart               # after any code change
```

A backlog with a healthy worker usually means jobs are failing and retrying. Read
`failed_jobs.exception` before restarting anything. A queue problem never requires a
schema rollback.

---

## 6. Logs

### Application

`LOG_STACK=daily` with `LOG_DAILY_DAYS=30` gives Laravel-managed rotation and needs no
OS configuration. That is the recommended setup — see
[production-env.md](production-env.md).

If you prefer OS rotation, set `LOG_STACK=single` and configure logrotate — but **do not
do both**, or logrotate will rotate Laravel's already-rotated files:

```
# /etc/logrotate.d/breem
/var/www/breem/storage/logs/*.log {
    daily
    rotate 30
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    su www-data www-data
}
```

`copytruncate` matters: PHP holds the file handle open, so a plain `rotate` would leave
it writing into a renamed inode.

### Web server

Not Laravel's concern, and must not be rotated from inside the application. Configure
the platform's own rotation for nginx/Apache access and error logs — typically
`/etc/logrotate.d/nginx`, shipped by the distribution. Confirm it exists and that
`postrotate` reopens the log handles; verify with `logrotate -d /etc/logrotate.d/nginx`.

Keep at least 30 days of access logs: they are the only record of who called the Device
API, and the only way to investigate a suspected credential compromise after the fact.

---

## 7. Database production settings

Sane MySQL 8 settings for this workload. **No connection pooler, no exotic
infrastructure** — Laravel opens a connection per request and PHP-FPM bounds the count.

```ini
[mysqld]
character-set-server = utf8mb4
collation-server     = utf8mb4_unicode_ci

# Roughly 50–70% of available RAM on a dedicated database host.
innodb_buffer_pool_size = 2G

# Durability. Do not weaken these for proof-of-play data.
innodb_flush_log_at_trx_commit = 1
sync_binlog = 1

# Must comfortably exceed PHP-FPM workers x 1 connection, plus the queue worker,
# the scheduler and your own sessions.
max_connections = 200

# Long enough for an idle FPM child, short enough to reclaim leaked connections.
wait_timeout = 600
```

**Do not enable persistent PDO connections** (`PDO::ATTR_PERSISTENT`). With PHP-FPM they
pin one MySQL connection per worker for the process's lifetime, exhaust
`max_connections` under load, and can leak transaction or temporary-table state between
unrelated requests. Laravel does not enable them; leave it that way.

Take a slow-query baseline in the first week:

```ini
slow_query_log = 1
long_query_time = 1
slow_query_log_file = /var/log/mysql/slow.log
```

Measured query behaviour at 100 screens is recorded in
`tests/Feature/Operations/FleetScaleSmokeTest.php`, which asserts the shapes rather than
timings: playlist generation is flat (8 queries), a playback report is 3 aggregate
queries regardless of row count, and the offline sweep and uptime report are bounded
*per screen* rather than unbounded.

---

## 8. First install only

For a brand-new environment, between steps 6 and 7 of §4.

```bash
php artisan key:generate            # ONCE, EVER. Then back the key up off-host.
php artisan db:seed --class=RoleSeeder --force        # roles and permissions
php artisan db:seed --class=AdminUserSeeder --force   # the first super admin
# CMS content, only if this environment should start with the stock pages:
php artisan db:seed --class=HomePageSeeder --force
php artisan db:seed --class=WhoWeArePageSeeder --force
php artisan db:seed --class=ContactUsPageSeeder --force
```

> ## Never run bare `php artisan db:seed` on a live database
>
> `DatabaseSeeder` chains **every** seeder, and three of them are destructive against
> existing data:
>
> - **`AdminUserSeeder`** `updateOrCreate`s the super admin, **resetting its password** to
>   `ADMIN_PASSWORD` — or, if that is unset, to a hash of the empty string.
> - **`DemoSeeder`** writes a demo place, a screen with the fixed code `SCR-001` and a
>   demo advertiser into production. If a real screen already holds `SCR-001`, its
>   status is overwritten.
> - **`ReportsAndLogsSeeder`** writes **fabricated playback logs** — contaminating
>   proof-of-play, the one dataset that must never contain invented rows.
>
> Seed named classes only, as above. Phase 15 removed the HTTP endpoint that ran
> `db:seed`; there is no way to trigger this from a browser any more.

### The first super admin

`AdminUserSeeder` reads `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_FIRST_NAME`,
`ADMIN_LAST_NAME` from the environment. `config/admin.php` supplies **no default
password**, so nothing is hard-coded and nothing weak ships in the repository.

1. Set `ADMIN_EMAIL` and a strong, unique `ADMIN_PASSWORD` in the production `.env`.
2. Run `RoleSeeder` then `AdminUserSeeder`.
3. Sign in and **change the password through the admin UI**, which triggers the OTP flow.
4. **Remove `ADMIN_PASSWORD` from `.env`** — it is only needed to bootstrap, and leaving
   it there means any later accidental `AdminUserSeeder` run silently resets the account
   to it.

Never place a production credential in source, a seeder default, a README, or this file.
The same applies to `CMS_ADMIN_PASSWORD`, which seeds the CMS-only account.

---

## 9. Post-deploy verification checklist

```bash
php artisan about --only=environment          # production / Debug OFF / https
php artisan ops:status; echo "exit=$?"        # 0
php artisan migrate:status | tail -5          # nothing Pending
php artisan schedule:list                     # three tasks, all real commands
php artisan queue:failed                      # empty
systemctl status breem-queue --no-pager       # active (running)
systemctl list-timers breem-schedule.timer --no-pager   # next run < 60s
curl -sI https://breem.example/up | head -n 1 # 200
```

Then, in the admin panel: Monitoring shows the fleet with plausible last-heartbeat
times, and at least one screen is online.
