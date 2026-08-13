# Production environment contract

Every configuration value Breem needs in Production, what happens when it is wrong,
and how to verify it without printing a secret.

**No real credential, host, domain, webhook or key appears in this file, and none may
be added to it.** Values below are placeholders.

Companion documents:

| Topic | Document |
|---|---|
| Deploy, backup, restore, rollback | [production-deployment.md](production-deployment.md) |
| Pre-launch sign-off checklist | [production-launch-checklist.md](production-launch-checklist.md) |
| Day-to-day operations | [operations-runbook.md](operations-runbook.md) |
| Pairing a physical screen | [device-repairing-runbook.md](device-repairing-runbook.md) |
| The device contract | [android-device-api.md](android-device-api.md) |

---

## How to read the classifications

| Class | Meaning |
|---|---|
| **REQUIRED** | Breem is broken, insecure, or silently wrong without it. |
| **RECOMMENDED** | Breem works, but something operationally important is degraded. |
| **OPTIONAL** | A feature is off until it is set. Off is a valid production state. |
| **DEV-ONLY** | Must never carry its development value in Production. |

---

## 1. Core application

| Key | Class | Production value | If wrong |
|---|---|---|---|
| `APP_NAME` | RECOMMENDED | `Breem` | Cosmetic; also the default session cookie name. |
| `APP_ENV` | **REQUIRED** | `production` | Framework and package behaviour keys off this. |
| `APP_KEY` | **REQUIRED** | `base64:...`, generated once | See the warning below. |
| `APP_DEBUG` | **REQUIRED** | `false` | Stack traces, SQL and filesystem paths served to the internet. |
| `APP_URL` | **REQUIRED** | `https://breem.example` | Absolute URLs — including creative URLs sent to devices — are built from this. Must be `https://`. |
| `APP_LOCALE` | RECOMMENDED | `en` | Default locale; `en` and `ar` are both served via `{lang?}`. |
| `APP_FALLBACK_LOCALE` | RECOMMENDED | `en` | Missing translation fallback. |
| `APP_MAINTENANCE_DRIVER` | RECOMMENDED | `file` | `php artisan down` storage. |

> ### APP_KEY: generate once, never again
>
> `APP_KEY` encrypts `screen_device_credentials.hmac_secret` (an `encrypted` cast) and
> signs cookies and signed URLs. **Rotating it on a live installation makes every
> paired device's signing secret undecryptable, and the entire fleet must be
> re-paired by hand.** Generate it once, on first install, with
> `php artisan key:generate`. Then back it up somewhere other than the server and
> never run that command again.
>
> Verify without printing it:
>
> ```bash
> php artisan tinker --execute="echo config('app.key') ? 'APP_KEY present' : 'APP_KEY MISSING';"
> ```

**Verify the whole block:**

```bash
php artisan about --only=environment    # expect: production, Debug Mode OFF, https URL
```

---

## 2. Database

| Key | Class | Production value | If wrong |
|---|---|---|---|
| `DB_CONNECTION` | **REQUIRED** | `mysql` | — |
| `DB_HOST` | **REQUIRED** | `127.0.0.1` or the managed host | — |
| `DB_PORT` | **REQUIRED** | `3306` | — |
| `DB_DATABASE` | **REQUIRED** | the production schema name | See below. |
| `DB_USERNAME` | **REQUIRED** | a least-privilege application user | — |
| `DB_PASSWORD` | **REQUIRED** | strong, unique | — |

The application has previously been pointed at the **wrong database** during local
work. Confirm the target before every deploy that migrates, and do it without
printing credentials:

```bash
php artisan tinker --execute="
  echo 'database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
  echo 'migrations table: ' . (Schema::hasTable('migrations') ? 'yes' : 'NO') . PHP_EOL;
  foreach (['screens','ads','places','screen_device_credentials','playback_logs'] as \$t) {
      echo str_pad(\$t, 28) . (Schema::hasTable(\$t) ? 'present' : '*** MISSING ***') . PHP_EOL;
  }
"
```

Expect the database name you intended, a `migrations` table, and every Breem table
present. A missing `screen_device_credentials` means you are looking at a
pre-Phase-10 schema, not at Production.

The application database user needs `SELECT, INSERT, UPDATE, DELETE` on the schema,
plus `CREATE, ALTER, INDEX, DROP, REFERENCES` **only while migrating**. It never needs
`GRANT`, `FILE`, `SUPER`, or access to any other schema.

---

## 3. HTTPS, reverse proxy and hosts

Device credentials and HMAC signing do **not** make plaintext HTTP acceptable: a
signature proves who sent a request, it does not hide the creative URLs, the playlist
or the admin session cookie travelling alongside it. **HTTPS is mandatory for the
Device API, the admin panel and the public site.**

| Key | Class | Production value | If wrong |
|---|---|---|---|
| `TRUSTED_PROXIES` | **REQUIRED** *when behind a proxy* | `REMOTE_ADDR`, an IP list, or `*` | Three silent failures — see below. |
| `SESSION_SECURE_COOKIE` | **REQUIRED** | `true` | Admin session cookie sent without `Secure`. |

Enforce the redirect **at the web server or the proxy**, not in the application —
that is where this deployment already terminates TLS, and an application-level
redirect cannot fire for a request the proxy has already mishandled:

```nginx
server {
    listen 80;
    server_name breem.example;
    return 301 https://$host$request_uri;
}
```

### Why TRUSTED_PROXIES is not optional behind TLS termination

`Illuminate\Http\Middleware\TrustProxies` is already in Laravel's global middleware
stack and reads `config('trustedproxy.proxies')` on every request. Unset, forwarded
headers are ignored — correct for a direct-to-PHP local environment, wrong behind a
proxy, where the application then sees plain HTTP from the proxy's own address:

1. **Creative URLs come out as `http://`.** `App\Support\MediaUrl` builds on `asset()`,
   which uses the request scheme. Devices get plaintext media URLs and admin pages
   load mixed content.
2. **The session cookie loses `Secure`** when `SESSION_SECURE_COOKIE` is unset, because
   Laravel infers the flag from whether the request looks encrypted. Set both.
3. **Rate limiting collapses to one bucket.** The unauthenticated handshake limiter is
   10/minute keyed on the client IP. Every screen and every attacker arrives as the
   proxy address, so one screen re-pairing can exhaust the fleet's budget and a
   brute-force source is invisible.

Pick the narrowest value that matches reality:

| Deployment | Value |
|---|---|
| nginx/Apache in front of PHP-FPM on the same host | `REMOTE_ADDR` |
| One or more load balancers with known addresses | `10.0.0.4,10.0.0.5` |
| Cloudflare or similar, **origin firewalled to the CDN's ranges only** | `*` |
| No proxy — PHP serves directly | leave unset |

`*` trusts whoever connected. It is safe only when the origin is unreachable except
through the proxy; otherwise any client can forge its own scheme and IP by sending
`X-Forwarded-*` itself.

**Verify after deploy** — from outside, against the real hostname:

```bash
curl -sI https://breem.example/up | head -n 1          # expect 200
curl -sI http://breem.example/up  | head -n 1          # expect 301 to https
# Creative URLs must be absolute https, with no localhost and no filesystem path:
#   pair a staging screen and inspect data.items[].file_url in the playlist response.
```

### Trusted hosts — RECOMMENDED, not enabled

Laravel's `TrustHosts` middleware is **not** registered (Laravel 12 only includes it
when `withMiddleware()` asks for it, and `app/Http/Middleware/TrustHosts.php` is
inherited Laravel-10 scaffold that nothing wires up). Host-header handling is
therefore whatever the web server accepts.

This is deliberate: Breem has no recorded list of production domains, and switching
host validation on without one turns every request on an unlisted hostname — a bare
IP, a staging alias, a `www.` variant — into a 400. Pin the hostname in the web
server's `server_name` instead, which is where this deployment already decides what
it answers to.

If you later want it in the application, add to `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->trustHosts(at: ['breem.example'], subdomains: true);
    // ... existing configuration
})
```

Do that only once every hostname that must work is known, and re-run the suite.

---

## 4. Sessions and cookies

| Key | Class | Production value | Notes |
|---|---|---|---|
| `SESSION_DRIVER` | **REQUIRED** | `database` | The `sessions` table already exists. |
| `SESSION_SECURE_COOKIE` | **REQUIRED** | `true` | See §3. |
| `SESSION_HTTP_ONLY` | **REQUIRED** | `true` (default) | Keeps JavaScript away from the cookie. |
| `SESSION_SAME_SITE` | **REQUIRED** | `lax` (default) | `lax` is what stops a cross-site `<img>` reaching the authenticated maintenance endpoints. Do not set `none`. |
| `SESSION_LIFETIME` | RECOMMENDED | `120` | Minutes of idle time. |
| `SESSION_DOMAIN` | OPTIONAL | unset | Set only to share a session across subdomains. |
| `SESSION_ENCRYPT` | OPTIONAL | `false` | Session data already lives server-side. |

---

## 5. Cache

| Key | Class | Production value |
|---|---|---|
| `CACHE_STORE` | **REQUIRED** | `database` |

Breem caches the **playlist**, **page/layout payloads**, and the **operational
notification de-duplication marker**.

**Replay protection does not use the cache.** Single-use nonce enforcement is a
`UNIQUE (credential_id, nonce)` constraint on `screen_request_nonces`
(`App\Services\Screen\DeviceReplayGuard`), which is atomic on every supported engine.
That was a deliberate Phase 10 decision precisely so a `CACHE_STORE` change could not
silently weaken a security guarantee — so the choice of cache driver carries no replay
risk.

The one place that needs atomicity is notification de-duplication
(`CheckExpiringAdsJob` uses `Cache::add()`). Laravel's `database` store implements
`add()` as an `insertOrIgnore` against the cache key's primary key, so it is atomic on
MySQL. A failure there would at worst duplicate an expiry email.

If you ever move to Redis, `add()` is atomic there too — but re-run the suite, because
no test currently pins Redis semantics.

**Do not run `php artisan cache:clear` as a routine deploy step.** It flushes playlist
entries and notification de-dupe markers, causing every screen to refetch at once. See
[production-deployment.md](production-deployment.md).

---

## 6. Queue

| Key | Class | Production value |
|---|---|---|
| `QUEUE_CONNECTION` | **REQUIRED** | `database` |

Breem queues offline alerts and expiring-ad notifications. **A continuously running
worker is required** — without one, jobs accumulate in `jobs` and nobody is ever
notified of anything.

`jobs`, `failed_jobs` and `job_batches` all exist in the schema. `retry_after` is 90 s,
which correctly exceeds the longest job timeout (`CheckScreenHealthJob::$timeout = 55`);
keep that relationship if either changes, or a still-running job gets released and run
twice.

Worker supervision, the deploy-time restart, and failed-job handling are in
[production-deployment.md](production-deployment.md).

---

## 7. Mail

| Key | Class | Production value | Notes |
|---|---|---|---|
| `MAIL_MAILER` | **REQUIRED** | `smtp` (or a real transport) | **`log` is not a transport.** |
| `MAIL_HOST` | **REQUIRED** | the provider's host | — |
| `MAIL_PORT` | **REQUIRED** | `587` | — |
| `MAIL_USERNAME` | **REQUIRED** | provider credential | — |
| `MAIL_PASSWORD` | **REQUIRED** | provider credential | Never in documentation or git. |
| `MAIL_SCHEME` | RECOMMENDED | `tls` | — |
| `MAIL_FROM_ADDRESS` | **REQUIRED** | `no-reply@breem.example` | Must pass SPF/DKIM for the domain. |
| `MAIL_FROM_NAME` | RECOMMENDED | `Breem` | — |

The repository default is `MAIL_MAILER=log`. That writes alerts into
`storage/logs/laravel.log` and delivers nothing. Offline detection still runs and
still records the transition — but **no human is told**, which is the whole point of
the alert.

**Safe verification** — no real send without target-environment approval:

```bash
# 1. Confirm the transport is not `log`:
php artisan ops:status | grep -i mailer

# 2. Then, and only with approval, one message to an address you own:
php artisan tinker --execute="Mail::raw('Breem production mail check', fn(\$m) => \$m->to('you@yourdomain.example')->subject('Breem mail check'));"
```

Check the provider's delivery log, not just the absence of an exception. If it does
not arrive, the failure is SPF/DKIM or provider policy, not Laravel.

---

## 8. Operational alerting

| Key | Class | Production value | Notes |
|---|---|---|---|
| `OPS_NOTIFICATION_EMAIL` | **REQUIRED** | an operations distribution address | Falls back to `ADMIN_EMAIL`; do not rely on that silently. |
| `SLACK_WEBHOOK_URL` | OPTIONAL | — | Slack is off unless set. |
| `SLACK_BOT_USER_OAUTH_TOKEN` | OPTIONAL | — | Needs the channel too, or it is ignored. |
| `SLACK_BOT_USER_DEFAULT_CHANNEL` | OPTIONAL | — | — |

This is an **operations** mailbox, not a customer or CMS contact address, and
deliberately one configured address rather than "every admin account" — the `admins`
table is an authentication list, not a distribution list.

With neither `OPS_NOTIFICATION_EMAIL` nor `ADMIN_EMAIL` set, detection still runs and
still records transitions; delivery is skipped and a warning is logged.
**`php artisan ops:status` exits non-zero in that state**, so a deployment pipeline can
treat it as a failed readiness gate.

### Slack: DISABLED by default

No business requirement for Slack is recorded anywhere in this repository, so the
production decision is **DISABLED** — leave all three keys empty. There is no partial
state: the webhook takes precedence, and the bot route needs *both* the token and the
channel or it is ignored entirely. Never put a webhook URL or bot token in
documentation, a commit, or a log line; `ops:status` prints only whether they are set.

To enable later, set `SLACK_WEBHOOK_URL` alone, confirm with `ops:status`, and force
one offline transition on a staging screen to see it arrive.

---

## 9. Data retention — **requires a business decision**

| Key | Class | Recommended range | Status |
|---|---|---|---|
| `SCREEN_LOG_RETENTION_DAYS` | **REQUIRED (decision)** | 90–180 | **Unset** |
| `PLAYBACK_LOG_RETENTION_DAYS` | **REQUIRED (decision)** | 1095 (3 years) minimum | **Unset** |
| `REPORT_RETENTION_DAYS` | **REQUIRED (decision)** | 1095, or never prune | **Unset** |

Empty, zero, negative and non-numeric all mean **keep everything**. Nothing is ever
deleted by default — `App\Support\Retention` is the only thing that interprets these
keys, and it treats a missing value as "disabled", never as "0 days".

**These are recommended ranges, not chosen values.** No authoritative retention period
exists anywhere in this repository, and the phase that built the mechanism deliberately
declined to invent one:

- **`screen_logs`** is telemetry. It grows at roughly *fleet size × 1440 rows/day* —
  a 100-screen fleet writes ~144,000 rows a day, ~4.3 M a month. It is the one table
  that genuinely needs a bound. 90–180 days keeps availability reporting useful.
- **`playback_logs` is proof-of-play: commercial evidence that an advertiser's creative
  actually played.** Deleting it destroys the only record backing an invoice. Do not
  pick a short period casually, and confirm the contractual and tax retention
  requirement before setting anything. Three years is a common commercial floor.
- **`reports`** are immutable snapshots and are frequently the *only* surviving copy of
  figures whose source logs have already been pruned. Pruning a report is
  irreversible. Consider never pruning them.

Until an owner decides, this is a **PRODUCT CONFIGURATION BLOCKER**, tracked in
[production-launch-checklist.md](production-launch-checklist.md). Leaving retention
disabled is a legitimate launch state as long as somebody is watching `screen_logs`
growth — it is only *undecided* that is unacceptable.

**Always preview before enabling, against the real database:**

```bash
php artisan model:prune \
  --model="App\Models\ScreenLog" \
  --model="App\Models\PlaybackLog" \
  --model="App\Models\Report" \
  --pretend
```

`--pretend` reports the eligible count and deletes nothing. If a number surprises you,
do not proceed. Take a fresh backup before the first real prune.

---

## 10. Uploads: application, PHP and web server must agree

Application ceilings (`config/ads.php`):

| Media | Ceiling |
|---|---|
| Image (JPEG, PNG) | 5 MB |
| GIF | 10 MB |
| Video | 150 MB |

An upload is bounded by the **smallest** of the application ceiling, PHP's limits, and
the web-server body limit. Raising `ADS_VIDEO_MAX_KB` does nothing if PHP caps the
request at 8 MB — and the failure is not a clean validation error, it is a truncated
or rejected request.

**Do not edit `php.ini` from application code.** These are host settings; the values
below are the required minimums.

`php.ini` — **the FPM/web pool, not just the CLI** (they are frequently different files):

| Directive | Minimum | Why |
|---|---|---|
| `upload_max_filesize` | `160M` | Above the 150 MB video ceiling with headroom. |
| `post_max_size` | `176M` | Must exceed `upload_max_filesize` — it bounds the whole request, form fields included. |
| `memory_limit` | `256M` | 512M if ffprobe is enabled. |
| `max_execution_time` | `300` | A 150 MB upload over a slow link. |
| `max_input_time` | `300` | Time spent *receiving* the body. |
| `max_file_uploads` | `20` | Default is fine; one creative per request. |

nginx:

```nginx
client_max_body_size 176M;
client_body_timeout  300s;
fastcgi_read_timeout 300s;
```

Apache (`mod_php` or `proxy_fcgi`):

```apache
LimitRequestBody 184549376   # 176 MB in bytes
Timeout 300
```

Cloudflare or a similar CDN imposes its own body limit (100 MB on several plans) that
you cannot raise from the server. If creatives exceed it, upload through an origin
hostname that bypasses the proxy.

**Verify against the running web SAPI, not the CLI:**

```bash
php -i | grep -E 'upload_max_filesize|post_max_size|memory_limit|Loaded Configuration'
# then confirm the pool actually in use, e.g.:
php-fpm -i 2>/dev/null | grep -E 'upload_max_filesize|post_max_size'
```

Then upload a real ~140 MB video through the admin form. That is the only check that
exercises every limit at once.

---

## 11. Video duration / ffprobe

| Key | Class | Production value |
|---|---|---|
| `ADS_TRY_FFPROBE` | **REQUIRED (decision)** | `false` (recommended) |
| `FFPROBE_BIN` | Required *if* probing is on | absolute path to a verified binary |

**Recommended production decision: `ADS_TRY_FFPROBE=false`.**

With probing off, the operator types the duration, and Phase 15 made that enforceable:
the admin form now **refuses** a video whose duration is neither supplied nor
probeable, instead of silently storing `duration_seconds = 0` and handing an unplayable
item to a screen. Zero is not a shorter advertisement; it is a broken one. So probing
is a convenience, not a correctness requirement, and the deployment does not need to
depend on an external binary being present, executable by the PHP user, and on `PATH`.

Turn it on only if operators must not type durations. Then verify **as the PHP user**:

```bash
sudo -u www-data ffprobe -version                      # must print a version
sudo -u www-data ffprobe -v error -show_entries format=duration \
  -of default=noprint_wrappers=1:nokey=1 /path/to/known-good.mp4   # must print seconds
sudo -u www-data ffprobe -v error -show_entries format=duration \
  -of default=noprint_wrappers=1:nokey=1 /etc/hostname             # must fail, not hang
```

Then set `FFPROBE_BIN` to the absolute path (`which ffprobe`), because the web user's
`PATH` is usually not yours. Confirm `shell_exec` is not in `disable_functions` — with
it disabled, probing silently returns nothing, which now surfaces as a validation
error rather than a zero-duration ad.

**Never commit an ffprobe binary to this repository.**

---

## 12. Logging

| Key | Class | Production value | Notes |
|---|---|---|---|
| `LOG_CHANNEL` | **REQUIRED** | `stack` | — |
| `LOG_STACK` | **REQUIRED** | `daily` | The default is `single` — one file that grows without bound. |
| `LOG_LEVEL` | **REQUIRED** | `warning` | `debug` writes a line per contact-form submission. |
| `LOG_DAILY_DAYS` | RECOMMENDED | `30` | Rotation window. |
| `LOG_DEPRECATIONS_CHANNEL` | OPTIONAL | `null` | — |

`LOG_STACK=single` is the shipped default and writes everything to one
`storage/logs/laravel.log` for ever. On a 100-screen fleet at `debug` that file will
eventually fill the disk, and a full disk takes down MySQL and the queue worker with
it. Use `daily`, or rotate at the OS level — not both, or you will rotate rotated files.

Web-server access and error logs are **not** Laravel's concern and must not be rotated
from inside the application. Configure `logrotate` (or the platform equivalent) for
nginx/Apache separately; see [production-deployment.md](production-deployment.md).

---

## 13. Filesystem and media

| Key | Class | Production value | Notes |
|---|---|---|---|
| `FILESYSTEM_DISK` | **REQUIRED** | `public` | — |
| `MEDIA_UPLOAD_ROOT` | OPTIONAL | unset | Absolute path override for managed uploads. Unset keeps them under `public/`. |
| `AWS_*` | OPTIONAL | — | Only if a creative is served from S3. |

**`php artisan storage:link` is NOT required.** Managed uploads are written directly
under `public/` (`public/cms/`, `public/upload/`) and served by the web server from
there; `public/storage` does not exist and nothing reads it. Do not create the symlink
just because Laravel projects usually have one.

Writable paths — least privilege, and **never `chmod 777`**:

| Path | Access | Purpose |
|---|---|---|
| `storage/` (recursive) | web user write | logs, framework cache, compiled views, sessions |
| `bootstrap/cache/` | web user write | config/route/package caches |
| `public/cms/` | web user write | CMS media |
| `public/upload/` | web user write | ad creatives |

```bash
sudo chown -R deploy:www-data storage bootstrap/cache public/cms public/upload
sudo find storage bootstrap/cache public/cms public/upload -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache public/cms public/upload -type f -exec chmod 0664 {} \;
```

Everything else — application code, `vendor/`, `config/` — should **not** be writable by
the web user. A web process that can rewrite its own PHP turns any upload flaw into
code execution.

---

## 14. Device API timings

| Key | Class | Production value | Notes |
|---|---|---|---|
| `SCREENS_HEARTBEAT_INTERVAL` | RECOMMENDED | `60` | Seconds between device heartbeats. |
| `SCREENS_OFFLINE_AFTER` | RECOMMENDED | `120` | Silence tolerated before offline. Always floored at interval + 1. |
| `SCREENS_SIGNATURE_LEEWAY` | RECOMMENDED | `300` | Clock skew tolerated on a signed request. |
| `SCREENS_PAIRING_CODE_TTL` | RECOMMENDED | `900` | Pairing-code lifetime, seconds. |
| `SCREENS_PLAYLIST_TTL` | RECOMMENDED | `300` | Playlist cache seconds; schedule boundaries shorten it. |
| `SCREENS_CONFIG_TTL` | RECOMMENDED | `900` | Device config cache seconds. |

There is no fleet-wide signing key. Each device's secret is minted at pairing and
stored encrypted, so there is nothing here to rotate.

`HEARTBEAT_OFFLINE_THRESHOLD` appears in `.env.example` but **no config file reads it**;
it is superseded by `SCREENS_OFFLINE_AFTER`. It is kept only so existing `.env` files
are not silently contradicted.

---

## 15. CORS

`config/cors.php` applies to `api/*` and `sanctum/csrf-cookie`.

**Native Android HTTP clients do not perform CORS preflight** — CORS is a browser
mechanism. The physical screens therefore need nothing here, and the correct production
posture is the narrowest configuration that satisfies real browser callers.

Current configuration, and why:

| Setting | Value | Rationale |
|---|---|---|
| `allowed_origins` | `['https://android-app.example']` | A single placeholder, not `*`. Replace with a real browser origin or empty it. |
| `allowed_methods` | `GET, POST, OPTIONS` | Exactly what the Device API exposes. |
| `allowed_headers` | `Accept, Authorization, Content-Type, If-None-Match, X-Client-Id, X-Screen-Signature, X-Screen-Timestamp, X-Screen-Nonce, X-Screen-Uid` | The signing headers the middleware genuinely reads, plus conditional-GET. |
| `supports_credentials` | `false` | The Device API is token-authenticated; no cookies. |
| `max_age` | `600` | Preflight cache. |

**Action at launch:** if no browser-based client calls `/api/v1/*`, set
`'allowed_origins' => []`. Nothing in the product needs a permissive value, and `*`
must not be reintroduced by habit. If a browser dashboard is added later, list its
exact origin — and only then. Do not broaden `allowed_headers` beyond the headers the
API actually reads.

`config/oldcors.php` is a superseded leftover that nothing loads. It is harmless but
carries no meaning; treat `config/cors.php` as the only CORS configuration.

---

## 16. Reporting limits

| Key | Class | Production value |
|---|---|---|
| `REPORT_MAX_PERIOD_DAYS` | RECOMMENDED | `366` (default) |

The longest period one report may cover. A playback report is SQL aggregation and is
flat whatever the period; a **screen-uptime report is a timeline walk per screen**, so
an unbounded period was an authenticated denial-of-service — `from_date=1970-01-01` asked
the server to reconstruct sixty years of log stream once per screen, inside a web
request.

366 days covers monthly, quarterly and annual reporting. Empty, zero or negative means
no ceiling; that is an escape hatch for a one-off historical report, not a setting to
leave in place. Enforcement is server-side in `GenerateReportRequest` — the form also
states the limit, but nothing relies on it doing so.

---

## 17. Development-only values

Never carry these into Production:

| Key | Dev value | Production requirement |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_URL` | `http://localhost` | `https://<real host>` |
| `MAIL_MAILER` | `log` | a real transport |
| `LOG_LEVEL` | `debug` | `warning` |
| `LOG_STACK` | `single` | `daily` |
| `DB_CONNECTION` | `sqlite` in `.env.example` | `mysql` |
| `SESSION_SECURE_COOKIE` | unset | `true` |
| `TRUSTED_PROXIES` | unset | set, if behind a proxy |
| `BCRYPT_ROUNDS` | `12` | `12` or higher — never the test value of `4` |

`VITE_APP_NAME` is inherited Laravel scaffold. **The runtime uses no Node, Vite,
Tailwind or Alpine**, and deployment requires no `npm install` and no `public/build`.
The scaffold files stay in the repository as repository-standard structure; they are
not part of deployment.

---

## 18. The readiness gate

```bash
php artisan ops:status; echo "exit=$?"
```

Exit `0` is required before a deployment is considered complete. It reports — and never
prints a secret value:

- the operational email recipient (**exit 1 when there is none**)
- whether Slack is configured, and by which route
- the queue connection and the mailer, so `log` cannot hide
- heartbeat interval and offline threshold
- each retention policy with its row count and cutoff, warning when all are disabled

It is deliberately a **readiness** check and stays separate from `/up`, which is
**liveness**: `/up` returns `200 OK` and says nothing about configuration, so it is safe
for a public load-balancer probe.
