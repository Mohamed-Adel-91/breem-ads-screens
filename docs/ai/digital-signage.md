# Digital signage

**Mandatory reading before any change to Screens, Ads, Schedules, Playlist,
Playback, Heartbeat, Monitoring or the Device API.**

## Domain chain

```
Place ──< Screen ──< AdSchedule >── Ad
             │                        │
             └──< ad_screen (pivot, play_order) >──┘
             │
             ├──< ScreenLog      (operational history)
             └──< PlaybackLog    (proof of play)

Screen → Heartbeat → ScreenLog → Monitoring
Screen → AdSchedulerService → Playlist → Device → PlaybackLog
```

## Tables and stored values

| Table | Key columns |
|---|---|
| `places` | `name` (json), `address` (json), `type` enum `club/cafe/mall/other` |
| `screens` | `place_id`, `code` (unique), `device_uid` (unique, nullable), `status` enum `online/offline/maintenance`, `last_heartbeat` |
| `ads` | `title`/`description` (json), `file_path`, `file_type` enum `video/image/gif`, `duration_seconds`, `status` enum `pending/approved/rejected/active/expired`, `created_by`, `approved_by`, `start_date`, `end_date` |
| `ad_screen` | `ad_id`, `screen_id`, `play_order`, unique(`ad_id`,`screen_id`) |
| `ad_schedules` | `ad_id`, `screen_id`, `start_time`, `end_time`, `is_active` |
| `screen_logs` | `screen_id`, `status` enum `online/offline/maintenance`, `current_ad_code`, `reported_at` |
| `playback_logs` | `screen_id`, `ad_id`, `played_at`, `duration`, `extra` (json) |

Enums are the contract: `App\Enums\ScreenStatus`, `AdStatus`, `PlaceType`.
Translate labels in views; never rewrite a stored value.

## The change rule

Before modifying any signage behaviour, trace **all seven layers**:

1. admin write path (controller + Form Request)
2. database representation (column, enum, cast)
3. domain service (`AdSchedulerService`, `HeartbeatService`, `ScreenApiService`)
4. Device API (controller + `Http/Resources/Api/**` envelope)
5. cache (`playlist:{screenId}`, TTL `services.screens.playlist_ttl`)
6. heartbeat / playback consumers, jobs, Monitoring
7. tests

**Never change one layer alone.** `screen_logs.status` once lagged `ScreenStatus`
by one case and produced an HTTP 500 on a visible admin button plus a latent
Device API failure.

## Screens

Preserve screen identity, the place relation, the status lifecycle, the
`device_uid` contract, `last_heartbeat` and the log stream.

`device_uid` is an **inventory identifier, not a credential**. Since Phase 10 it
authenticates nothing: it records which hardware occupies a screen. Do not
casually reassign or regenerate it, and do not expose it in a device-facing
Resource — but do not treat possession of it as proof of anything either.

## Device security

The current model (Phase 10) is:

- **Pairing** is a one-time code an administrator generates per screen. It is
  stored hashed, expires after `services.screens.pairing_code_ttl`, and is
  consumed atomically inside a transaction (`DevicePairingService::claim()`).
- **Credentials are per device.** Pairing mints a random 32-byte bearer token
  (stored only as `hash('sha256', $token)`) and a separate random 32-byte HMAC
  secret (stored encrypted). Both live in `screen_device_credentials`; the model
  hides them from serialisation.
- **Every protected request proves four things**: the bearer token, an
  HMAC-SHA256 signature over the canonical message, a timestamp inside
  `services.screens.signature_leeway`, and a nonce unused by that credential.
- **The canonical message** is defined once, in `App\Support\DeviceSignature`,
  and both the middleware and the tests use it. It is
  `METHOD\n/path\ncanonical_query\ntimestamp\nnonce\nsha256(body)`, so method,
  path, query, freshness, uniqueness and body are all bound into the signature.
- **Replay protection is atomic**: `DeviceReplayGuard::consume()` relies on a
  unique constraint in `screen_request_nonces`, not a read-then-write check.
- **`EnsureScreenAuthentication` fails closed** at every step, including when a
  credential's stored secret is unreadable or empty.

Never, without explicit architecture approval:

- trust a client-supplied device UID as authentication
- reintroduce a fleet-wide signing secret — compromising one device must not
  compromise the fleet
- store a bearer token in plaintext, or return a stored hash / secret through a
  Resource
- silently re-pair an already-paired screen (an administrator reset is required)
- claim replay protection without an atomic guarantee behind it

## Advertisements

Preserve creative file, media type, duration, status, screen assignment and play
order.

Status is **not cosmetic**: `AdSchedulerService` filters on `AdStatus::Active` and
on `start_date`/`end_date`. Changing what a status means changes what plays.

Assignment happens in two places — `AdController::syncScreens()` (admin form) and
`ScheduleController::ensureScreenAttachment()` (auto-attach when a schedule is
created). Both must stay consistent.

### Status lifecycle

Five statuses, unchanged since the original schema. What Phase 13 added is that a
status may only move along an edge `AdStatus::allowedTransitions()` declares —
before that, `status` was a free select on the ad form, so any value could overwrite
any other and "approval" meant whatever the last person to save chose.

| From | approve | reject | publish | unpublish | expire |
|---|---|---|---|---|---|
| `pending` | `approved` | `rejected` | — | — | — |
| `approved` | — | `rejected` | `active` | — | `expired` |
| `rejected` | `approved` | — | — | — | — |
| `active` | — | `rejected` | — | `approved` | `expired` |
| `expired` | `approved` | — | — | — | — |

- **Only `active` plays.** Approval and going live are separate edges: `approved`
  means "cleared for broadcast", `active` means "broadcasting".
- `pending` has **no** `publish` edge. Going live without review is the bypass the
  map exists to prevent.
- `rejected` and `expired` can be approved again, so no ad is stranded — editing an
  ad only ever returns it to `pending`.
- Every action requires the **`ads.approve`** permission and goes through
  `POST admin.ads.transition`. That permission was already seeded (super-admin and
  admin, not viewer) with zero consumers; Phase 13 wired it up rather than inventing
  new RBAC.
- The request takes an **action name**, never a target status: the target is derived
  server-side, so an undeclared pair cannot be reached however the request is shaped.
- `status` and `approved_by` are **absent from StoreAdRequest and UpdateAdRequest**
  and from the ad form. Do not add them back.

### Approval audit trail

`created_by` and `approved_by` are foreign keys to **`users`**, but the dashboard is
operated by the **`admins`** guard — a different actor domain. Writing an admin id
into a users FK would misattribute the action, so Phase 13 added columns instead of
repurposing them:

| Column | Meaning |
|---|---|
| `created_by` | legacy content owner (users FK, NOT NULL, still on the form) |
| `created_by_admin_id` | the admin who performed the create |
| `approved_by` | legacy (users FK). Preserved, never written to any more |
| `approved_by_admin_id` | the admin who approved |
| `approved_at` | when they approved |

The migration is additive and backfills nothing. Rows created before Phase 13 have
NULL admin columns: there is no mapping from a user id to an admin id, and a null
audit value is honest where a fabricated one is not.

### Edit after approval

A playback-relevant edit to a **reviewed** ad (`approved` or `active`) returns it to
`pending` and clears `approved_by_admin_id` / `approved_at`. The attributes that
count are `Ad::PLAYBACK_RELEVANT_ATTRIBUTES`: `file_path`, `file_type`,
`duration_seconds`, `start_date`, `end_date`.

- Only a genuine **change of value** triggers it — re-saving identical values does
  not.
- **Title and description never do**: they are not in the device manifest, so
  editing them changes nothing a viewer sees.
- **Assignment, schedules and play order never do.** Approval covers the creative,
  not where or when it runs; those carry their own authorization.

Because the re-approval write goes through the model, `AdObserver` flushes the
affected playlists, so an unreviewed creative stops playing immediately rather than
at the end of the cache TTL.

### Creative media lifecycle

The filesystem is not transactional, so safety is ordering plus explicit
compensation:

```
upload new  →  probe  →  DB transaction  →  commitReplacedFiles() (delete the old)
                  │            │
                  └────────────┴──→  discardUploadedFiles() (keep the old)
```

- ffprobe runs **outside** the transaction — it shells out to an external binary and
  must never hold one open.
- A failed probe or a failed database write discards **only** the new candidate. The
  old file and the old database value both survive.
- The replaced file is deleted **only after** the transaction commits, so the
  database never points at a file that is gone.
- `AdController::update()` previously did neither half: a failed probe returned early
  leaving the new upload orphaned, and a successful replacement never called
  `commitReplacedFiles()`, so every superseded creative stayed on disk forever.

Delete order is: capture affected screens → delete rows in a transaction →
invalidate those playlists → delete the file last, and only when no other ad row
references the same path.

### Duration

One implementation, `AdController::resolveDuration()`. Precedence:

1. an explicit non-zero `duration_seconds` from the operator;
2. otherwise, for a **video**, ffprobe reads it from the file;
3. otherwise the current value (update) or zero (create).

Images and GIFs are never probed — how long a still is shown is a playlist decision,
not a property of the file. A required probe that fails returns null, which becomes a
validation error; an ad row is never written with an unknown-duration video. The dead
`resolveDurationSeconds()` and the `failDurationProbe()` methods it was the only
caller of were removed.

Step 3 applies only where zero is a legitimate answer — an image or a GIF. **For a
video, "no duration available" now returns null rather than zero, whatever the reason.**
Phase 15 closed the case where probing is switched off (`ADS_TRY_FFPROBE=false`, the
shipped default): that used to fall through to zero and hand the player an unplayable
item. A video of zero seconds is not a shorter advertisement, so it is treated as the
same "duration required and unavailable" outcome as a broken binary and reported through
the same validation error. Pinned by `ProductionGateTest`.

### Global validity dates

`ads.start_date` / `ads.end_date` come from `type="date"` inputs, so they are
calendar dates stored at midnight. `App\Support\AdValidity` owns their boundary
contract — deliberately **not** folded into `TimeWindow`, whose literal
to-the-second rule schedule rows still follow:

- `start_date` is used as stored, **inclusive**;
- a **midnight** `end_date` covers the whole of that calendar day, so the exclusive
  bound becomes the following midnight;
- an `end_date` carrying a **time** is a precise instant and is used as stored.

So `Aug 1 → Aug 31` plays from `Aug 1 00:00` up to `Sep 1 00:00` exclusive — the ad
runs throughout Aug 31, which is what the form implies. The second clause is what
makes this safe for existing data: legacy rows holding real datetimes (the seeded
demo ads do) keep their exact meaning. **No stored date is rewritten**; only the
interpretation of date-only values changed, so no data migration was needed.

Dates are UTC calendar days, matching `config('app.timezone')`. This bound is used
consistently by eligibility, the cache boundary TTL, the admin "effective window"
display, and the manifest's `valid_until` / `ad_valid_until`.

## Scheduling

One source of truth: `AdSchedulerService`. Do **not** duplicate eligibility logic
in controllers, views, API Resources or JavaScript. `App\Support\TimeWindow` owns
the single boundary rule that the service, the admin state badge and the overview
filters all share.

### Eligibility matrix

An ad reaches a screen's playlist only when every applicable rule passes.

| Assigned | Status Active | Inside global window | Schedule rows for (ad, screen) | Matching active row now | Eligible |
|---|---|---|---|---|---|
| no | — | — | — | — | **no** |
| yes | no | — | — | — | **no** |
| yes | yes | no (not started) | — | — | **no** |
| yes | yes | no (ended) | — | — | **no** |
| yes | yes | yes | none | n/a | **yes** |
| yes | yes | yes | ≥1 | yes | **yes** |
| yes | yes | yes | ≥1 | no | **no** |
| yes | yes | yes | ≥1, all inactive | no (inactive rows are ignored) | **no** |

Row existence is counted over **all** rows; matching is counted over **active**
rows only. So an ad whose only row is inactive is gated and cannot play — an
inactive schedule can never grant eligibility.

### Unscheduled ads

Zero schedule rows for a (ad, screen) pair means **always scheduled**, still
subject to assignment, status and the ad's global window. Assignment-only ads are
valid always-on content.

### Ads with schedules

Once rows exist for that pair, the ad plays **only** inside an active one. It never
falls back to always-on playback outside every window — that was the historical
defect.

### Inactive schedules

An inactive row means exactly *ignore this row*. It grants no eligibility, moves no
boundary, and saving one changes nothing else.

### Overlaps

Overlapping windows are **valid** and are never silently resolved.

- **Different ads, same screen** — both are eligible during the overlap and both
  appear in the playlist. The playlist is the rotation. Saving one ad's schedule
  must never touch another advertiser's row; `resolveScheduleConflicts()` did that
  and was removed.
- **Same ad, same screen** — eligibility is boolean, so several matching rows still
  produce exactly **one** playlist item. The item's `schedule_id` / `schedule` /
  `valid_from` / `valid_until` come from one deterministic representative row:
  earliest `end_time`, then earliest `start_time`, then lowest `id`. That order is
  total, so the payload is reproducible.

### Both windows apply

The ad's global window and the schedule window are ANDed; the effective window is
their intersection. Global `Aug 1 → Aug 31` with a schedule `Aug 10 → Sep 10` plays
`Aug 10 → Aug 31`. A schedule can never extend playback past the ad's own validity.

### Play order

`ad_screen.play_order` is the only ordering source. Items are sorted by
`play_order` ascending, then `ads.id` ascending — a total order, so the same state
at the same instant always yields the same sequence. Do not add a competing order
field.

### Timezone

`config('app.timezone')` is **UTC**, and nothing overrides it. Admin
`datetime-local` inputs are naive strings parsed in the app timezone, Eloquent
casts read them back in the same zone, and MySQL stores them unshifted — so schedule
comparisons are UTC end to end, with no conversion anywhere in the path. **UTC does
not observe DST**, so there is no DST boundary to model; do not invent one. There is
still no per-screen or per-place timezone concept.

### Boundary inclusivity

**Start inclusive, end exclusive** — `start <= now < end` — via
`TimeWindow::contains()`, applied identically to schedule rows and to the ad's
global window. Adjacent windows `10:00→11:00` and `11:00→12:00` therefore hand over
cleanly: at exactly 11:00 the first is over and the second is live.

Caveat: the ad's `start_date`/`end_date` come from `type="date"` inputs, so they
land on midnight. An ad with `end_date = Aug 31` stops at `Aug 31 00:00`, i.e. it
does not play *during* Aug 31. That was equally true under the previous
inclusive-to-the-instant comparison; making the end day inclusive would be a
product change, not a boundary fix. See Deferred functional defects.

## Playlist

Output must be deterministic from authoritative state.

- Cached per screen. `AdSchedulerService::cacheKeyFor()` owns the key format
  (`playlist:{screenId}`); nothing else may compose it.
- **Effective lifetime = `min(services.screens.playlist_ttl, time until the next
  boundary)`.** Boundaries are the assigned ads' `start_date`/`end_date` and their
  active schedule rows' `start_time`/`end_time`, from
  `AdSchedulerService::nextBoundaryFor()` — one calculation, not duplicated in
  cache code. A cached payload also re-checks its own `expires_at` on read, so the
  transition is exact even though store TTLs are whole seconds. A cached empty
  playlist can therefore never survive an ad's activation, and an expiring ad can
  never linger for the rest of the TTL.
- **ETag = `sha1(json({screen: {id, code}, items}))`.** It validates the bytes the
  device receives and nothing else. It deliberately excludes `screens.updated_at`,
  `status` and `last_heartbeat` (operational state, not content — hashing them made
  every heartbeat force a re-download), and `generated_at`/`expires_at` (cache
  bookkeeping — including them would give the same state a new ETag on every
  rebuild).
- `PlaylistResource` embeds `PlaylistScreenResource` (`id`, `code`), **not** the
  general `ScreenResource`. The playlist is a playback manifest; monitoring state
  belongs to the heartbeat response.
- Invalidation has exactly two owners: **observers** for model writes
  (`AdObserver`, `AdScheduleObserver` — create/update/delete) and the **calling
  controller** for pivot writes via `Ad::flushScreensCache()`, because
  attach/detach/sync fire no model events. Do not add a third path, and never flush
  the whole cache store.
- Must not N+1. Generation is three queries — screen refresh, ads with pivot,
  the screen's schedule rows — flat in the number of ads and schedules.
- The device must never decide its own eligibility.

### ETag contract

Changes ETag: ad added/removed, assigned/unassigned, media path or type, duration,
`play_order`, a schedule edit that changes the effective playlist, a global-window
change that changes it, an active schedule boundary being crossed, fallback content.

Does **not** change ETag: a heartbeat, `last_heartbeat`, monitoring
acknowledgement, a change to an unrelated screen, or an ad **title**/description
edit.

The title rule is deliberate and approved: the ETag represents device playlist
payload semantics, and the title is not in that payload. The title was **not** added
to the manifest merely to make invalidation fire.

### Fallback

`config('ads.fallback')` stands in **only** when no assigned ad is eligible at that
instant — future schedule, expired schedule, inactive ad, or no assignment. It never
plays alongside eligible ads. Its cache entry respects the same boundary rule, so
the fallback disappears exactly when real content becomes eligible.

Fallback is static configuration read per build (`ADS_FALLBACK_*`). Changing it is a
deployment plus `config:clear`, not a runtime write — there is deliberately no
invalidation hook for it.

## Playback

`playback_logs` are operational/audit data. Since Phase 10 a batch is attributed
to the **authenticated** screen — never to a screen named in the body — and every
`ad_id` is validated against that screen's assignments, so a device cannot report
plays for an ad it was never given.

Do not change those semantics outside an approved stabilization phase.

## Heartbeat and connectivity truth

**The dashboard must never claim a screen is online because an administrator
clicked a button.** Everything below exists to hold that line.

`HeartbeatService` owns every write to `screens.status` and
`screens.last_heartbeat`. Nothing else may write them.

| Fact | Owner | Rule |
|---|---|---|
| `last_heartbeat` | `HeartbeatService::touch()` | The instant the **server** accepted a signed heartbeat. Never derived from a device clock; never writable by an administrator; never stamped by an admin action. |
| `online` | `HeartbeatService::touch()` | A request that passed `screen.auth` proves the device is reachable now. |
| `offline` | `HeartbeatService::markOffline()`, via `CheckScreenHealthJob` | Silence longer than the threshold. Leaves `last_heartbeat` untouched — that is the evidence. |
| `maintenance` | Admin, on the Screen edit form; or a device declaring it | Explicit operational mode, not connectivity. |

Consequences that are easy to get wrong:

- **A device cannot declare itself offline.** The heartbeat carrying the claim is
  itself proof of reachability, so `status: offline` from a device resolves to
  online. Only `CheckScreenHealthJob` can produce `offline`.
- **A device may declare `maintenance`** — reachable but not serving.
- **Administrator maintenance is sticky.** A heartbeat refreshes
  `last_heartbeat` but does not silently return the screen to service; an
  explicit Screen edit does.
- **Maintenance suppresses the offline sweep.** Operators own the screen, so
  connectivity alerting stops until they hand it back. `last_heartbeat` still
  shows the staleness in the UI.
- **`reported_at` is telemetry.** It orders the log stream, so it is clamped to
  `[server receipt − signature_leeway, server receipt]`. It never reaches
  `last_heartbeat`.
- **Recovery is automatic.** offline → valid heartbeat → online, with no admin
  action.
- **Pairing marks a screen online** because the handshake is a real device
  communication — but it grants no exemption; a device that pairs and goes quiet
  is swept offline like any other.

**Never derive online/offline in Blade or JavaScript.** Render the stored column.

### Timing

`App\Support\ScreenHealth` is the single source of truth. Do not read
`services.screens.heartbeat_interval` directly and never inline a timeout.

- `heartbeatInterval()` — cadence advertised to devices (`SCREENS_HEARTBEAT_INTERVAL`, default 60 s)
- `offlineAfter()` — silence tolerated before offline (`SCREENS_OFFLINE_AFTER`; defaults to `interval × 2` = 120 s)
- The threshold is always floored at `interval + 1`: a threshold at or below the
  cadence would mark healthy screens offline between two on-time reports.

### Offline detection

`CheckScreenHealthJob`, scheduled every minute in **`routes/console.php`** and
visible in `php artisan schedule:list`.

Idempotent: it selects only screens that are still `online` with a stale
heartbeat, and `markOffline()` re-checks eligibility before writing. A screen
transitions once, logs once and notifies once, no matter how many ticks run.

`app/Console/Kernel.php` also declares a `schedule()` method. Laravel 12 never
binds that class, so it has never run — which is why offline detection was dead
for the life of the project while appearing to be configured. Add schedule
entries to `routes/console.php`.

**The scheduler must actually be running in production** (`* * * * * php artisan
schedule:run`). Without it `screens.status` silently goes stale. See the
deployment runbook.

## Monitoring

Presentation over operational state. It must not invent device activity, and it
must not recompute status.

**Acknowledging an alert means an administrator has seen it — nothing else.** It
writes `acknowledged_at`, `acknowledged_by` and `acknowledgement_note` onto the
`screen_logs` row that raised the alert, preserving the original event. It does
not touch `screens.status` or `screens.last_heartbeat`, and it writes no new log.
Putting a screen into maintenance is a separate action on the Screen edit form.

**Availability is elapsed time.** `ScreenAvailabilityService` walks the log
stream as a timeline over a 7-day window and returns per-status seconds.
`availability = online / (online + offline)`. Maintenance is excluded from the
denominator as planned downtime; time before the first-ever report is `unknown`
and is excluded too — never counted as online. A zero denominator returns null
and the UI says so rather than printing a misleading 0% or 100%.

The old figure was `online log rows / total log rows`, an event ratio: a screen
that reported online once and then died for six days scored 100%. If you ever
need that number again, label it "reports", not "uptime".

**Reports use this same service.** The `screen-uptime` report used to count online
and offline *events* — the identical event-ratio mistake — so Monitoring and the
report could disagree about the same screen and window. There must never be a second
availability calculation; `ReportGenerationTest` asserts the two agree figure for
figure.

## Reports

Two types, and `App\Support\ReportType` is the only list. There used to be three
disagreeing lists — the Form Request accepted `playback` / `screen-uptime`, the seeder
wrote `performance` / `availability`, and the generator's `match` fell through to the
playback builder for anything unrecognised, so a report could claim one thing and
contain another.

| Type | Content | Generation |
|---|---|---|
| `playback` | plays, total duration and screens reached per advertisement | SQL aggregation: `COUNT`, `SUM`, `GROUP BY` |
| `screen-uptime` | time-based availability per screen | `ScreenAvailabilityService`, per screen, chunked |

`performance` and `availability` are **legacy values on existing rows**, mapped by
`ReportType::canonical()` to the types they always meant. They render and export with
the correct columns but are not offered for new generation, and no historical row is
rewritten. `reports.type` is a plain string, not an enum cast, precisely so reading a
legacy row cannot throw.

Unsupported types are **refused**, not substituted — `ReportGenerationService::build()`
throws rather than quietly building something else.

### Snapshot contract

A report is an **immutable aggregate snapshot**. `reports.data` holds `rows`
(aggregates — one per advertisement or per screen, never raw log rows), `summary`,
`period` and `schema_version`. The show page renders from that payload and runs no log
queries, so a report's figures never change afterwards and survive retention pruning.

The trade-off is explicit: **once source logs are pruned there is no drill-down from a
report to individual log rows.** The summary is permanent; the detail is not.

Generation is synchronous and should stay that way while it is bounded — playback
aggregation is flat in the number of log rows. It previously ran
`PlaybackLog::with(['ad','screen'])->get()` and `ScreenLog::with(['screen.place'])->get()`
and grouped in PHP, hydrating every row in the period with its relations to produce a
handful of totals.

### Period semantics

UTC calendar days. `from_date` is inclusive from its start of day; `to_date` is
inclusive of the **whole** day, so the exclusive upper bound is the following midnight
— the same shape as `AdValidity`. `endOfDay()` would drop the final second; a bare
midnight bound drops the entire final day.

Export is CSV, streamed row by row, never assembled in memory.

## Operational data retention

`screen_logs` grows at roughly `fleet size × 1440` rows a day (one per heartbeat plus
one per transition) and `playback_logs` can grow faster. **That volume is intentional
telemetry — do not reduce the writes.** Retention is what bounds it.

Mechanism: Laravel's `Prunable` contract (`MassPrunable`) on `ScreenLog`,
`PlaybackLog` and `Report`, driven by `config/retention.php` through
`App\Support\Retention`, executed by a nightly `model:prune` scheduler entry.

**Disabled by default, and that is a decision rather than an oversight.** No
authoritative retention period is recorded anywhere in this repository, and
`playback_logs` is proof-of-play evidence, so the mechanism ships and the values are
the operator's. `Retention::days()` treats null, empty, zero, negative and non-numeric
alike: **disabled**, never "0 days". A disabled policy's `prunable()` query matches no
rows.

Safety: the cutoff comparison is `<`, so a row exactly at the boundary is kept;
deletes use the indexed time column; chunks are bounded; the operation is idempotent;
and it never touches `screens`, `ads`, assignments or schedules.

`php artisan model:prune --pretend` previews. `php artisan ops:status` reports what is
active. Full procedure: [`docs/operations-runbook.md`](../operations-runbook.md).

## Operational notifications

One resolver, `App\Support\OperationsRecipients`. It replaced an `adminNotifiable()`
method **duplicated verbatim** in both scheduled jobs.

Recipient: `notifications.operations.email` (`OPS_NOTIFICATION_EMAIL`), falling back at
read time to `admin.email` (`ADMIN_EMAIL`). Deliberately one mailbox, not every admin
account — the `admins` table is an authentication list, not a distribution list.

**A missing recipient logs a clear warning and returns null. It never throws**, because
an alerting misconfiguration must not roll back a screen's offline transition: the
state change is already committed by the time delivery is attempted. It previously
returned silently, so a screen went offline correctly and nobody was told, with nothing
in the log to say so.

Channels are `mail` (when routed), `slack` (when routed) and always `log`. Slack was
previously **never** in `via()`: it was posted from inside `toArray()` — the log
channel's payload builder — as an `Http::post()` side effect whose failures were
swallowed by `report()`, bypassing the installed
`laravel/slack-notification-channel` entirely. `toArray()` is now a pure
representation.

Both notifications are queued with `tries = 3` and `[60, 300]` backoff, then land in
`failed_jobs`. Retries are finite on purpose.

Dedup: the offline alert is idempotent because the sweep only selects screens still
`online`. The expiring-ad warning is deduplicated by a cache key including the
**effective** end date, so extending a campaign warns again for the new end.

There is no recovery ("back online") notification, and none was invented.

### Expiring advertisements

`CheckExpiringAdsJob` both retires finished ads and warns about imminent ones, and
**both halves use `AdValidity::endsBefore()`**. Comparing the raw `end_date` retired a
date-only campaign at `Aug 31 00:00` — a full day of paid airtime lost — and warned a
day early. `Ad::scopeExpiringSoon()` is now documented as a **superset** for narrowing
the candidate set, never as the answer.

The automatic retirement goes through the Phase 13 transition map
(`active --expire--> expired`), so a system action can only produce a declared state.

## Deferred functional defects

Documented, deliberately unfixed. Do not "fix" these as a side effect of another
task.

**Device API** — registered since Phase 9, hardened in Phase 10. The endpoint
names below are a **frozen contract**; do not rename them.

| Method | URI | Name | Auth |
|---|---|---|---|
| POST | `api/v1/screens/handshake` | `api.v1.screens.handshake` | pairing code only (opts out of `screen.auth`) |
| POST | `api/v1/screens/heartbeat` | `api.v1.screens.heartbeat` | token + signature + timestamp + nonce |
| GET | `api/v1/screens/{screen}/playlist` | `api.v1.screens.playlist` | token + signature + timestamp + nonce |
| POST | `api/v1/playbacks` | `api.v1.playbacks.store` | token + signature + timestamp + nonce |
| GET | `api/v1/config` | `api.v1.config.show` | token + signature + timestamp + nonce |

`{screen}` resolves by **id or code**, but the credential decides access: a
credential may only address its own screen (403 `screen_mismatch` otherwise).

Throttling is split deliberately. The four authenticated routes carry
`throttle:api.v1` (120/min); the handshake opts out of it and carries
`throttle:api.v1.handshake` (10/min) instead, so pairing-code guessing cannot
consume a real device's request budget and a busy device cannot relax the
guessing limit.

`bootstrap/app.php` must register `routes/api.php` explicitly inside the `using:`
closure — a custom `using:` callback makes the `api:`, `web:` and `health:`
arguments inert.

Authentication is one fail-closed chain in `EnsureScreenAuthentication`, in this
order, each step returning a distinct machine-readable `error` code:

`missing_token` → `invalid_token` → `revoked_token` / `expired_token` →
`screen_mismatch` (403) → `stale_timestamp` → `missing_nonce` →
`invalid_signature` → `replayed_request`.

On success the credential and screen are attached as request attributes; every
controller reads the screen from there, never from user input.

Tables added in Phase 10, all additive — `screens` was not altered:

| Table | Purpose |
|---|---|
| `screen_device_credentials` | per-device `token_hash` (sha256) + encrypted `hmac_secret`, `issued_at`, `last_used_at`, `expires_at`, `revoked_at`. `active_screen_id` is a nullable-unique mirror of `screen_id` that goes NULL on revoke, so the database enforces at most one live credential per screen. |
| `screen_pairing_codes` | hashed one-time codes with `expires_at` and `consumed_at`. |
| `screen_request_nonces` | unique(`credential_id`, `nonce`) — the replay guard. Pruned opportunistically. |

Behaviour pinned by `tests/Feature/Api/DeviceApiContractTest.php` (38 tests).

**Remaining Device API limitations** — known, deliberately unfixed:
- Credentials do not expire by default (`expires_at` is supported and enforced,
  but pairing leaves it null). There is no rotation endpoint; recovery is an
  administrator reset plus re-pair.
- Nonce pruning is opportunistic (1-in-200 requests), not a scheduled job, so
  `screen_request_nonces` growth is bounded only by traffic on a quiet fleet.
- The handshake throttle is keyed by IP, so a fleet behind one NAT shares the
  10/min pairing budget.

**Heartbeat / Monitoring** — the first four entries below were fixed in Phase 11;
see the sections above for the resulting contract. What remains:

- **Offline alerts are dropped when `ADMIN_EMAIL` is unset.** `CheckScreenHealthJob`
  builds its recipient from `admin.email`; with none configured it returns
  silently. Detection still works — nobody is told. Pinned by
  `OfflineDetectionTest::test_offline_alerts_are_silently_dropped_without_a_recipient`.
- `CheckScreenHealthJob::adminNotifiable()` reads
  `services.slack.notifications.channel` and `.bot_user_oauth_token`, neither of
  which exists in `config/services.php` (only `slack.webhook_url` does), so that
  branch is permanently null. Harmless — the notification posts the webhook from
  `toArray()` — but misleading.
- A screen logs an entry on **every** heartbeat, not only on transitions, so
  `screen_logs` grows at roughly `fleet size × 1440/day` at the default cadence.
  There is still no retention or pruning for `screen_logs` / `playback_logs`, and
  `ScreenAvailabilityService` walks the window's rows on every page view.
- The offline sweep runs every minute regardless of fleet size; there is no
  batching or chunking.

**Scheduling** — the first three entries were fixed in Phase 12 (silent conflict
mutation, inactive-submission side effects, and playback outside every window); see
the Scheduling and Playlist sections above for the resulting contract. What remains:

- The ad's global `start_date`/`end_date` come from `type="date"` inputs, so an
  `end_date` of `Aug 31` stops playback at `Aug 31 00:00` rather than at the end of
  that day. Consistent with the documented end-exclusive rule and unchanged in
  behaviour from earlier phases, but it is not what an operator reading "ends
  Aug 31" expects. Making the end day inclusive is a product decision.
- `datetime-local` values are parsed in the app timezone (UTC); there is no
  per-screen or per-place timezone concept.
- No recurring schedules (no daily/weekly rules), no location targeting.
- The schedules overview reads `ad_schedules` directly; it has no index tuned for
  the `state` filter beyond the existing `(screen_id, start_time, end_time)`.

**Ads** — every entry previously listed here was fixed in Phase 13 (free status
editing, the missing approval workflow, the users/admins actor mismatch,
extension-based type detection, the absent size limit, the missing
`commitReplacedFiles()` call, the unprotected delete, and the dead
`resolveDurationSeconds()`); see the Advertisements section above for the resulting
contract. What remains:

- The size ceilings in `config('ads.upload')` are an *application* limit. An upload is
  still bounded by PHP's `upload_max_filesize` / `post_max_size` and by any
  web-server body limit, whichever is smallest, and raising a config value does
  nothing unless the platform allows it too. There is no check that the two agree, so
  a generous config value can silently be capped by a stricter `php.ini`.
- ffprobe is off by default (`ADS_TRY_FFPROBE=false`). **Phase 15 fixed what that used
  to mean:** a video uploaded without an explicit duration was stored with
  `duration_seconds = 0` and the playlist handed the player a zero duration. It is now
  refused at validation instead, so the operator supplies a duration or the ad is not
  created. Zero remains legal for images and GIFs, where the playlist decides.
- `Ad::scopeExpiringSoon()` (used by `CheckExpiringAdsJob` for notifications) compares
  the raw `end_date`, not the effective one from `AdValidity`, so the "expiring soon"
  email can fire up to a day early. It is a notification heuristic, not eligibility.
- `Ad::scopeActiveIn()` has no callers.
- `creative` accepts a still-image MIME set of JPEG/PNG only; WebP is not accepted,
  which is a product decision rather than an oversight.
- There is no per-advertiser identity: `created_by` points at a `users` row, but there
  is no advertiser, campaign or billing domain and none was invented.

**Reports** — the type mismatch, the in-memory generation and the unbounded payload
were all fixed in Phase 14; see the Reports section above. What remains:

- ~~The reports index selects whole rows, so listing hydrates each `data` blob.~~
  **Fixed in Phase 15:** the index names its columns
  (`id, name, type, generated_by, created_at`), so no snapshot is read to render a table
  that shows none of it. `show` and `download` still load the model in full.
- `screen-uptime` walks the log timeline per screen. That is inherent to a duration
  measurement, not an N+1, but generation time grows with fleet size × logs in the
  window. **Phase 15 bounded the requested period** at `REPORT_MAX_PERIOD_DAYS`
  (default 366), enforced server-side in `GenerateReportRequest` via
  `App\Support\ReportPeriod` — an open-ended `from_date` is measured to now, because the
  builder measures to now. Empty/zero/negative restores the unbounded behaviour.
- No drill-down from a report to the underlying log rows, by design — see the snapshot
  contract above.
- Generation is synchronous. Fine while bounded; if a very large fleet or a very long
  period ever exceeds the request budget, move it to a queued job then.

**Operational logging / notifications** — Phase 14 fixed the silent notification drop,
the dead Slack config branch, the `toArray()` delivery side effect, expiring-ad spam and
the one-day expiry error. What remains:

- A screen still logs an entry on **every** heartbeat, not only on transitions. That is
  intentional telemetry; retention bounds it.
- Retention ships disabled, so an operator who never configures it gets unbounded
  growth. `ops:status` warns. **Still an open product decision** — Phase 15 recorded
  recommended ranges and left the choice to the owner rather than inventing a
  proof-of-play retention period. See
  [`../production-env.md`](../production-env.md#9-data-retention--requires-a-business-decision).
- `MAIL_MAILER` defaults to `log`, so alerts go nowhere real until a transport is
  configured. Documented as a launch requirement in
  [`../production-launch-checklist.md`](../production-launch-checklist.md).
- Queue worker supervision and `failed_jobs` handling are documented in
  [`../production-deployment.md`](../production-deployment.md#2-queue-worker-supervision);
  a `failed_jobs` **depth alarm** is still an unbuilt post-launch improvement.
- ~~`CheckScreenHealthJob` loads all stale screens in one query with no chunking.~~
  **Fixed in Phase 15:** the sweep uses `lazyById()`, so a fleet-wide outage streams in
  id-ordered pages instead of hydrating every stale screen at once. It has to be
  id-keyed — the loop makes each row stop matching the `status = online` filter, so an
  OFFSET cursor would skip a page's worth of screens per boundary.
