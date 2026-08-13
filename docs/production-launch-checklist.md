# Production launch checklist

Sign-off gate for putting Breem in front of real screens and real advertisers.

Work top to bottom. **Anything marked 🔴 P0 blocks launch. 🟠 P1 should block launch.
🟡 P2 launches with explicit written acceptance. 🔵 P3 is post-launch.**

Every item is either verifiable by a command given here, or it is a decision with a
named owner. "Looks fine" is not a state.

| Reference | Document |
|---|---|
| Every configuration value and what breaks without it | [production-env.md](production-env.md) |
| Deploy, backup, restore, rollback | [production-deployment.md](production-deployment.md) |
| Day-to-day operations | [operations-runbook.md](operations-runbook.md) |
| Pairing a physical screen | [device-repairing-runbook.md](device-repairing-runbook.md) |
| The device contract | [android-device-api.md](android-device-api.md) |

---

## A. Repository state

- [ ] `composer validate` exits 0 with no warnings
- [ ] `composer audit` reports **no advisories** 🔴 **P0**
- [ ] `php artisan test` is fully green, with the count recorded below
      → recorded: ______ tests / ______ assertions / 0 failures
- [ ] `git status --short` is clean; no `.env`, media, dump or credential is newly tracked
- [ ] `git diff --check` reports no whitespace errors
- [ ] All seven Laravel scaffold files are still tracked: `package.json`,
      `vite.config.js`, `postcss.config.js`, `tailwind.config.js`, `resources/js/app.js`,
      `resources/js/bootstrap.js`, `resources/css/app.css`

```bash
composer validate && composer audit && php artisan test
git status --short && git diff --check
git ls-files package.json vite.config.js postcss.config.js tailwind.config.js \
  resources/js/app.js resources/js/bootstrap.js resources/css/app.css | wc -l   # expect 7
```

---

## B. Infrastructure

- [ ] PHP **8.2+** on the web SAPI (developed on 8.3) 🔴 **P0**
- [ ] MySQL 8 reachable, `utf8mb4` / `utf8mb4_unicode_ci` 🔴 **P0**
- [ ] `composer install --no-dev --optimize-autoloader` completes on the host 🔴 **P0**
- [ ] **No Node, no npm, no `public/build`** required or present
- [ ] Writable, least-privilege: `storage/`, `bootstrap/cache/`, `public/cms/`,
      `public/upload/` — and **nothing else** writable by the web user 🔴 **P0**
- [ ] Application code, `vendor/` and `config/` are **not** web-writable 🔴 **P0**
- [ ] `php artisan storage:link` **not** run — Breem does not use it
- [ ] `bootstrap/cache/*.php` is cleared before `composer install` on any
      copy-based (rsync/zip) deploy 🟠 **P1**

---

## C. Environment

- [ ] `APP_ENV=production` 🔴 **P0**
- [ ] `APP_DEBUG=false` 🔴 **P0**
- [ ] `APP_KEY` set, and **backed up off-host** — never regenerated on a live install 🔴 **P0**
- [ ] `APP_URL=https://…` — the real canonical hostname 🔴 **P0**
- [ ] `.env` is mode `0600`, owned by the deploy user, and git-ignored 🔴 **P0**
- [ ] `LOG_STACK=daily`, `LOG_LEVEL=warning`, `LOG_DAILY_DAYS=30` 🟠 **P1**
- [ ] `BCRYPT_ROUNDS` ≥ 12 (never the test value of 4)

```bash
php artisan about --only=environment    # production / Debug OFF / https URL
```

---

## D. Database

- [ ] `DB_DATABASE` **verified to be the intended production schema** — Breem has been
      pointed at the wrong database before 🔴 **P0**
- [ ] Application DB user is least-privilege; no `SUPER`, `FILE` or `GRANT` 🟠 **P1**
- [ ] `php artisan migrate:status` shows **nothing pending** 🔴 **P0**
- [ ] `php artisan migrate --pretend` reviewed before any migrating deploy
- [ ] Index-building migrations scheduled for a quiet window (the only slow ones)
- [ ] Team knows deleting a **Place** cascades to its screens, credentials, logs **and
      proof-of-play** 🟠 **P1**

```bash
php artisan tinker --execute="
  echo 'database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
  foreach (['screens','ads','screen_device_credentials','playback_logs','migrations'] as \$t) {
      echo str_pad(\$t, 30) . (Schema::hasTable(\$t) ? 'present' : '*** MISSING ***') . PHP_EOL;
  }"
```

---

## E. Backup and restore

- [ ] Database backup script installed and scheduled 🔴 **P0**
- [ ] Media backup covers `public/cms/`, `public/upload/`, `public/images/` 🔴 **P0**
- [ ] DB and media backups share a timestamp so a restore can pair them 🟠 **P1**
- [ ] Backups copied **off-host** 🔴 **P0**
- [ ] Backups encrypted at rest, mode `0600` 🟠 **P1**
- [ ] `APP_KEY` backed up **separately from** the database backups 🔴 **P0**
- [ ] Backup integrity is *checked*, not assumed (`gzip -t` + `Dump completed`) 🟠 **P1**
- [ ] **A restore has actually been executed** into a scratch schema, with table and row
      counts and Arabic text verified 🔴 **P0** — see
      [production-deployment.md §1.4](production-deployment.md)
      → **NOT YET DONE from this repository. No production backup destination is reachable
      from the development environment.**
- [ ] Restore rehearsal scheduled quarterly, with the last date recorded 🔵 **P3**

---

## F. Security

- [ ] HTTPS serves the admin, the public site **and** the Device API 🔴 **P0**
- [ ] HTTP → HTTPS redirect enforced at the web server / proxy 🔴 **P0**
- [ ] TLS certificate valid, and **auto-renewal verified by a dry run** 🟠 **P1**
- [ ] `TRUSTED_PROXIES` set to match the real topology — required behind any
      TLS-terminating proxy 🔴 **P0**
- [ ] `SESSION_SECURE_COOKIE=true` 🔴 **P0**
- [ ] `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax` 🟠 **P1**
- [ ] `config/cors.php` `allowed_origins` is a real list or `[]` — **never `*`** 🟠 **P1**
- [ ] `APP_DEBUG=false` verified by triggering a real 404 and 500 and seeing no stack
      trace, path or SQL 🔴 **P0**
- [ ] No secret in any log (tokens, HMAC secrets, `Authorization`, DB or mail passwords,
      Slack webhook) — audited, none found
- [ ] Maintenance endpoints (`/clear-cache`, `/run-optimize/dayNN`, `/run-migrate/dayNN`)
      reachable only by an authenticated **super-admin** 🔴 **P0**
- [ ] No route seeds the database over HTTP — `run-seeder` removed in Phase 15 🔴 **P0**
- [ ] Security headers present on admin and public responses
      (`X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`)
- [ ] Admin login throttled; session regenerated on login; session invalidated on logout
- [ ] Device API fails closed: unauthenticated `/api/v1/config` returns **401** 🔴 **P0**

```bash
curl -sI https://breem.example/up | head -n 1                              # 200
curl -sI http://breem.example/up  | head -n 1                              # 301
curl -s -o /dev/null -w '%{http_code}\n' https://breem.example/api/v1/config  # 401
curl -sI https://breem.example/en | grep -iE 'x-content-type-options|x-frame-options|referrer-policy'
sudo certbot renew --dry-run
```

**🔵 P3 — recommended, not done:** a Content-Security-Policy. The admin ships
summernote/tinymce and inline handlers, so a policy strict enough to be worth setting
needs a nonce-and-refactor pass across the existing Blade views. Deliberately not added
rather than shipping `unsafe-inline` or breaking the admin.

**🔵 P3 — recommended, not done:** convert the three maintenance GET routes to POST so
Laravel's CSRF middleware covers them. They are authenticated and super-admin gated, and
`SESSION_SAME_SITE=lax` stops a cross-site `<img>` from reaching them, but a top-level
navigation a signed-in super-admin is tricked into following would still fire one. The
remaining actions are a cache clear and a forward-only `migrate --force`, so the impact
is availability at worst.

**🔵 P3 — recommended, not done:** `TrustHosts` is not enabled, because no production
domain list is recorded. Pin the hostname in the web server's `server_name` instead.

**🔵 P3 — recommended, not done:** the admin OTP is compared with `===` rather than
`hash_equals`. Timing analysis through HTTP against a 10/minute throttle is not a
practical attack, but the constant-time comparison is free.

---

## G. Queue

- [ ] `QUEUE_CONNECTION=database`; `jobs`, `failed_jobs`, `job_batches` all exist 🔴 **P0**
- [ ] A supervised worker runs, restarts on failure, and **starts at boot** 🔴 **P0**
- [ ] `--timeout=60` — above the longest job timeout (55 s), below `retry_after` (90 s) 🟠 **P1**
- [ ] `--max-time=3600` bounds process lifetime
- [ ] `php artisan queue:restart` is part of the deploy sequence 🔴 **P0** — without it
      the worker keeps running the old code
- [ ] `failed_jobs` is monitored; **no scheduled `queue:flush`** 🟠 **P1**
- [ ] Queue-depth alarm configured (`queue:monitor database --max=100`) 🔵 **P3**

```bash
systemctl status breem-queue --no-pager
php artisan queue:failed
```

---

## H. Scheduler

- [ ] `schedule:run` runs **every minute** via cron or a systemd timer 🔴 **P0**
- [ ] `php artisan schedule:list` shows exactly three tasks 🔴 **P0**
- [ ] **Every command named by the scheduler actually exists** — this has been broken
      before, and is now pinned by a test 🔴 **P0**
- [ ] Offline detection observed working end to end: a screen stops heartbeating and is
      transitioned within the threshold 🔴 **P0**

```bash
php artisan schedule:list
systemctl list-timers breem-schedule.timer --no-pager
```

---

## I. Retention — **product decision required**

- [ ] `SCREEN_LOG_RETENTION_DAYS` decided (recommended **90–180**) 🟠 **P1**
      → chosen: ______ · owner: ______ · date: ______
- [ ] `PLAYBACK_LOG_RETENTION_DAYS` decided (**proof-of-play — commercial evidence**;
      recommended **1095** minimum, confirm the contractual requirement first) 🟠 **P1**
      → chosen: ______ · owner: ______ · date: ______
- [ ] `REPORT_RETENTION_DAYS` decided (recommended **1095**, or never prune) 🟠 **P1**
      → chosen: ______ · owner: ______ · date: ______
- [ ] `model:prune --pretend` run with the intended values, eligible counts reviewed 🔴 **P0**
- [ ] A fresh backup taken **before** the first real prune 🔴 **P0**

> **PRODUCT CONFIGURATION BLOCKER.** All three are unset, which means *keep everything* —
> nothing is deleted. That is a safe launch state, but it is not a decision, and
> `screen_logs` grows at roughly *fleet × 1440 rows/day* (~4.3 M rows/month for 100
> screens). Either choose values, or accept unbounded growth in writing **and put
> `screen_logs` row count on someone's dashboard.**
>
> `playback_logs` is the evidence behind an advertiser's invoice. Do not pick a short
> period casually.

```bash
php artisan model:prune --model="App\Models\ScreenLog" \
  --model="App\Models\PlaybackLog" --model="App\Models\Report" --pretend
```

---

## J. Mail and Slack

- [ ] `MAIL_MAILER` is a **real transport** — the default `log` delivers nothing 🔴 **P0**
- [ ] `MAIL_FROM_ADDRESS` passes SPF/DKIM for the sending domain 🟠 **P1**
- [ ] One approved test message sent and confirmed **in the provider's delivery log** 🟠 **P1**
- [ ] `OPS_NOTIFICATION_EMAIL` set to a real operations distribution address 🔴 **P0**
- [ ] Whoever receives those alerts knows they will get them, and what to do
- [ ] Slack decision recorded: **DISABLED** (no business requirement on file). No partial
      configuration left behind.
- [ ] `php artisan ops:status` **exits 0** 🔴 **P0**

```bash
php artisan ops:status; echo "exit=$?"
```

---

## K. Uploads and media

- [ ] Web-SAPI `upload_max_filesize` ≥ **160M** 🔴 **P0**
- [ ] Web-SAPI `post_max_size` ≥ **176M** (must exceed `upload_max_filesize`) 🔴 **P0**
- [ ] `memory_limit` ≥ 256M · `max_execution_time` ≥ 300 · `max_input_time` ≥ 300 🟠 **P1**
- [ ] Web-server body limit ≥ 176M (`client_max_body_size` / `LimitRequestBody`) 🔴 **P0**
- [ ] CDN body limit checked — Cloudflare caps at 100 MB on several plans 🟠 **P1**
- [ ] **A real ~140 MB video uploaded successfully through the admin form** 🔴 **P0** —
      the only check that exercises every limit at once
- [ ] `ADS_TRY_FFPROBE` decision recorded (recommended **`false`**)
      → chosen: ______ · If `true`: `FFPROBE_BIN` is an absolute path, verified runnable
      **as the PHP user**, on a known-good file, and failing safely on a non-video
- [ ] A zero-duration video **cannot** be created — verified by trying to save a video
      with the duration field empty 🔴 **P0**
- [ ] Creative URLs in a real playlist response are absolute `https://`, resolve, carry
      the right MIME type, and contain **no localhost and no filesystem path** 🔴 **P0**
- [ ] Fallback creative decision recorded: `ADS_FALLBACK_URL` points at a real file, or
      the empty-playlist behaviour is accepted. With nothing eligible the API returns a
      well-formed empty playlist and the player shows nothing — verify that is what the
      product wants 🟠 **P1**

```bash
php -i | grep -E 'upload_max_filesize|post_max_size|memory_limit|Loaded Configuration'
```

---

## L. Device fleet

- [ ] Screen inventory confirmed: how many exist, how many are paired
- [ ] **Every screen needs explicit pairing.** Phase 10 replaced device-UID-as-token with
      per-device credentials, and a screen without a credential row cannot authenticate 🔴 **P0**
- [ ] A pairing plan exists: who visits which screen, in what order, over what window 🔴 **P0**
- [ ] Operators have read [device-repairing-runbook.md](device-repairing-runbook.md)
- [ ] Pairing codes are delivered to installers over a channel that is not the screen
      itself, and expire in 15 minutes 🟠 **P1**
- [ ] The revoke path has been exercised once on a staging screen

Save as `pairing-audit.php` and run `php artisan tinker pairing-audit.php` — it reads
only, and prints no secret:

```php
<?php

use App\Models\Screen;
use App\Models\ScreenDeviceCredential;

$paired = ScreenDeviceCredential::query()->whereNull('revoked_at')->pluck('screen_id')->unique();

echo 'screens: ' . Screen::count() . PHP_EOL;
echo 'active credentials: ' . ScreenDeviceCredential::whereNull('revoked_at')->count() . PHP_EOL;
echo 'need pairing: ' . Screen::whereNotIn('id', $paired)->count() . PHP_EOL;
```

---

## M. Physical screen / player end-to-end

> 🔴 **P0 — MANUAL LAUNCH BLOCKER. NOT EXECUTED.**
>
> No Android player build, no staging HTTPS endpoint and no physical screen is reachable
> from this repository, so **none of the following has been verified against real
> hardware.** The backend side of every step is covered by automated tests
> (`tests/Feature/Operations/DigitalSignageAcceptanceTest.php` walks the whole lifecycle
> through the real HTTP surfaces), but a passing backend test is not a working screen.

Against a staging or production-like **HTTPS** endpoint, with a real player build:

- [ ] Pairing: operator generates a code, installer enters it, device receives credentials
- [ ] Credentials are persisted in the device keystore and survive an app restart
- [ ] Signature generation matches the server: canonical message, sorted query,
      `sha256("")` body hash on GET
- [ ] Clock is NTP-synchronised — a device more than 300 s out is rejected
- [ ] Heartbeat cadence is honoured and the screen shows online in Monitoring
- [ ] Playlist `ETag` / `If-None-Match` produces **304** on an unchanged poll
- [ ] Media downloads and caches
- [ ] Image playback correct
- [ ] Video playback correct, with the **duration the server sent** honoured
- [ ] Schedule start boundary: the ad appears without a restart
- [ ] Schedule end boundary: the ad disappears
- [ ] Playback reporting reaches `playback_logs`
- [ ] Network loss: the player keeps playing its cached playlist
- [ ] Network recovery: heartbeats resume, the screen recovers online unaided
- [ ] Credential revoke: the device stops being served and reports the failure
- [ ] Re-pair after revoke: the device works again with new credentials
- [ ] Screen left running unattended for **24 hours** without drift, leak or stall

---

## N. Monitoring and reporting

- [ ] Monitoring shows the real fleet with plausible last-heartbeat times
- [ ] An availability figure has been cross-checked against a screen's actual log history
- [ ] A playback report generated over a real period, with correct totals
- [ ] CSV export downloads and opens
- [ ] `REPORT_MAX_PERIOD_DAYS` accepted at the default **366**, or changed deliberately
      → chosen: ______
- [ ] Uptime alerting recipient understands there is **no "screen back online" email** —
      recovery is visible in Monitoring only

---

## O. Documentation and handover

- [ ] [production-env.md](production-env.md) matches the deployed configuration
- [ ] [production-deployment.md](production-deployment.md) matches how this host is
      actually deployed
- [ ] [device-repairing-runbook.md](device-repairing-runbook.md) in installers' hands
- [ ] [android-device-api.md](android-device-api.md) given to whoever maintains the player
- [ ] Postman collection and the **Production** environment file hold no real secret
- [ ] `docs/future/partner-api-v1-DRAFT.md` understood to be **not implemented** — never
      quoted to a client as an available integration
- [ ] On-call rota exists for the operational alert mailbox 🟠 **P1**
- [ ] Whoever holds `APP_KEY` and the backup credentials is recorded, and it is more than
      one person 🟠 **P1**

---

## P. Rollback readiness

- [ ] Last-good commit SHA recorded before deploying 🔴 **P0**
- [ ] A backup exists from **before** this deploy 🔴 **P0**
- [ ] The team knows `migrate:rollback` **deletes data** on this schema and is a last
      resort, not a first response 🔴 **P0**
- [ ] The team knows changing `APP_KEY` forces re-pairing the **entire fleet** 🔴 **P0**
- [ ] The forward-fix path is understood as the default for a bad migration

---

## Sign-off

| Area | Verified by | Date | Notes |
|---|---|---|---|
| Repository / dependencies | | | |
| Infrastructure / TLS | | | |
| Database / migrations | | | |
| Backup **and proven restore** | | | |
| Queue / scheduler | | | |
| Retention decision | | | |
| Mail / alerting | | | |
| Uploads / media | | | |
| Device pairing plan | | | |
| **Physical screen E2E** | | | |
| Documentation handover | | | |

**Launch requires every 🔴 P0 checked, and every 🟠 P1 either checked or accepted in
writing by a named owner.**
