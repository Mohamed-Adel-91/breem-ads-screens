# Operations runbook

Running Breem's background work: the scheduler, the queue worker, data retention,
operational alerting and report generation.

Scoped to the operational layer:

| Topic | Document |
|---|---|
| Every production configuration value | [`production-env.md`](production-env.md) |
| Deploy sequence, worker/scheduler supervision, backup, restore, rollback | [`production-deployment.md`](production-deployment.md) |
| Pre-launch sign-off gate | [`production-launch-checklist.md`](production-launch-checklist.md) |
| Pairing and re-pairing a physical screen | [`device-repairing-runbook.md`](device-repairing-runbook.md) |

---

## Quick check

```bash
php artisan ops:status
```

Reports the operational recipient, the Slack route (whether one is set — never the
URL or token), the queue connection, the mailer, the offline threshold, and every
retention policy with its current cutoff and row count.

**Exits 1 when no operational recipient is configured**, so a deployment pipeline can
gate on it. It never writes anything.

---

## Two processes must be running

Neither is optional, and nothing in the application warns you at runtime if they are
absent — the symptoms are silence.

### 1. The scheduler

```
* * * * * cd /path/to/breem && php artisan schedule:run >> /dev/null 2>&1
```

One cron entry, every minute. `php artisan schedule:list` shows what it drives:

| Cadence | Task | Consequence if the scheduler is not running |
|---|---|---|
| every minute | `screens:detect-offline` | screens that die stay "online" for ever, and no offline alert is ever sent |
| daily 09:00 | `ads:check-expiring` | finished campaigns keep playing; nobody is warned before one ends |
| daily 03:30 | `operational:prune` | `screen_logs` and `playback_logs` grow without bound |

Offline detection latency is bounded by the offline threshold, not by the sweep
interval, which is why the sweep runs every minute rather than every five.

### 2. The queue worker

```bash
php artisan queue:work --tries=3 --timeout=60
```

`QUEUE_CONNECTION=database` by default, so the `jobs` table is the transport and no
Redis is required. Both scheduled jobs and both notifications are queued.

**Without a worker, nothing fails loudly — jobs simply accumulate in `jobs` and no
alert is ever delivered.** Check depth with:

```bash
php artisan queue:monitor database --max=100
```

**The worker must be supervised, and must be restarted on every deploy** — a
long-running worker holds the old code in memory. systemd and Supervisor units, the
tuned flags and the reasoning behind each are in
[`production-deployment.md`](production-deployment.md#2-queue-worker-supervision).

---

## Operational alerting

### Recipient

| Key | Env | Notes |
|---|---|---|
| `notifications.operations.email` | `OPS_NOTIFICATION_EMAIL` | preferred |
| `admin.email` | `ADMIN_EMAIL` | fallback, resolved at read time |

One mailbox, deliberately — not "every admin account". The `admins` table is an
authentication list; mailing all of it would make every new account an alert
subscriber. Use a distribution address if several people need alerts.

**With neither set, alerts are skipped and a warning is logged.** Detection still
runs and still records the transition; only delivery is skipped. Search for:

```
Operational notification skipped: no recipient is configured.
```

The log entry names the missing key and the context ("screen offline alert" /
"advertisement expiring alert"). This is deliberate — an alerting misconfiguration must
never roll back a screen's offline transition.

### Slack (optional)

Two routes, via the installed `laravel/slack-notification-channel`:

| Route | Env | Used when |
|---|---|---|
| Incoming webhook | `SLACK_WEBHOOK_URL` | takes precedence |
| Bot token + channel | `SLACK_BOT_USER_OAUTH_TOKEN` + `SLACK_BOT_USER_DEFAULT_CHANNEL` | webhook unset; **both** required |

A channel with no token is ignored rather than attempted, because the Web API call
would fail without credentials.

### What alerts exist

| Event | Trigger | Frequency |
|---|---|---|
| Screen offline | the sweep transitions `online` → `offline` | once per transition |
| Ad expiring soon | within 24h of an ad's **effective** end | once per ad per end date |

Neither repeats. The offline alert is idempotent because the sweep only selects
screens that are still online. The expiring warning is deduplicated by a cache key
that includes the effective end date, so extending a campaign warns again when the new
end approaches.

There is **no recovery ("screen back online") notification.** Recovery is
server-authoritative and visible in Monitoring, but nothing emails about it.

### Delivery failures

Both notifications are queued with `tries = 3` and a `[60, 300]` second backoff, then
give up. A permanent failure lands in `failed_jobs`:

```bash
php artisan queue:failed
php artisan queue:retry <uuid>
```

Retries are finite on purpose: a stale offline alert has little value, and a
misconfiguration should be visible in `failed_jobs` rather than retried for ever.

---

## Data retention

### The default is OFF

Every policy is disabled until someone sets a positive number of days. A null, empty,
zero, negative or non-numeric value means **keep everything**, and the nightly prune
deletes nothing.

That is deliberate. `screen_logs` is telemetry and `playback_logs` is proof-of-play
evidence; no retention period is recorded anywhere in this repository, so the mechanism
ships and the values are the operator's decision.

### Configuration

| Env | Table | Timestamp | Notes |
|---|---|---|---|
| `SCREEN_LOG_RETENTION_DAYS` | `screen_logs` | `reported_at` | grows ~`fleet × 1440` rows/day |
| `PLAYBACK_LOG_RETENTION_DAYS` | `playback_logs` | `played_at` | **proof-of-play — check the commercial requirement first** |
| `REPORT_RETENTION_DAYS` | `reports` | `created_at` | a report is the only surviving copy of its figures |

Each is independent; enabling one never prunes another's table.

### Preview before enabling

```bash
php artisan model:prune \
  --model="App\Models\ScreenLog" \
  --model="App\Models\PlaybackLog" \
  --model="App\Models\Report" \
  --pretend
```

`--pretend` reports what would be deleted and deletes nothing. Always run it after
changing a retention value.

### Safety properties

- The cutoff comparison is `<`, so a row exactly at the boundary is **kept**. Erring
  towards keeping data is the only safe direction for an irreversible delete.
- Deletes go through the indexed time column (`screen_logs.reported_at`,
  `playback_logs.played_at`, `reports.created_at`), so a fleet-wide prune is not a
  full table scan.
- `MassPrunable` deletes in bounded chunks; expired rows are never all loaded.
- Idempotent — re-running changes nothing.
- Retention removes telemetry only. It never touches `screens`, `ads`, assignments or
  schedules.

### Retention and reports

A generated report is an **immutable aggregate snapshot**: its totals live in
`reports.data` and the show page runs no log queries. So a report keeps displaying its
figures after the logs it was built from have been pruned.

The trade-off, stated plainly: **once the source logs are gone, there is no way to
drill from a report back to individual log rows.** The summary is permanent; the detail
is not. Set `PLAYBACK_LOG_RETENTION_DAYS` with that in mind.

There is deliberately no "delete logs" button in the admin UI. Retention is a server
policy, not an ad-hoc destructive action.

---

## Report generation

Synchronous, and intended to stay that way. Playback reports aggregate in SQL —
`COUNT`, `SUM`, `GROUP BY` — so the query count is flat regardless of how many log
rows the period contains. Screen-uptime walks the log timeline per screen, chunked
100 screens at a time, because availability is a duration calculation that no
`COUNT(*)` is equivalent to.

If generation ever does outgrow an HTTP request, move it to a queued job then — not
pre-emptively.

- **Types:** `playback` and `screen-uptime`. `App\Support\ReportType` is the registry.
  Legacy rows stored as `performance` / `availability` still render (they map to the
  canonical type they always meant) but cannot be generated.
- **Period:** UTC calendar days. `from_date` inclusive from its start of day, `to_date`
  inclusive of the whole day.
- **Availability:** the same `ScreenAvailabilityService` Monitoring uses, so the two
  can never disagree for the same screen and window.
- **Export:** CSV, streamed row by row, never assembled in memory.

---

## Environment reference

```dotenv
# Operational alerting
OPS_NOTIFICATION_EMAIL=
SLACK_WEBHOOK_URL=
SLACK_BOT_USER_OAUTH_TOKEN=
SLACK_BOT_USER_DEFAULT_CHANNEL=

# Retention — empty means keep everything
SCREEN_LOG_RETENTION_DAYS=
PLAYBACK_LOG_RETENTION_DAYS=
REPORT_RETENTION_DAYS=

QUEUE_CONNECTION=database
```

---

## Before going live

The operational prerequisites. The full gate — infrastructure, TLS, backups, uploads,
physical screens — is [`production-launch-checklist.md`](production-launch-checklist.md).

- [ ] `php artisan ops:status` exits 0
- [ ] cron runs `schedule:run` every minute
- [ ] a supervised queue worker is running, and starts at boot
- [ ] `php artisan queue:restart` is part of the deploy sequence
- [ ] `MAIL_MAILER` is a real transport (it defaults to `log`)
- [ ] retention values chosen, previewed with `--pretend`, then enabled
- [ ] `failed_jobs` is monitored, and never flushed on a schedule
