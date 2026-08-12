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

## Scheduling

One source of truth: `AdSchedulerService`. Do **not** duplicate eligibility logic
in controllers, views, API Resources or JavaScript.

Before touching scheduling, inspect: overlaps, time boundaries, timezone handling,
`is_active`, fallback behaviour, `play_order`, and cache invalidation.

## Playlist

Output must be deterministic from authoritative state.

- Cached per screen under `playlist:{screenId}` for `services.screens.playlist_ttl`
  seconds.
- ETag = `sha1(screen_id | screen.updated_at | json(items))`. It covers the
  **playlist items**, not ad metadata such as the title.
- Invalidated by `AdObserver`, `AdScheduleObserver` and explicit
  `flushScreensCache()` calls.
- Must not N+1. The device must never decide its own eligibility.

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

- **The playlist ETag is invalidated by every heartbeat.** The ETag includes
  `screens.updated_at`, which every heartbeat bumps, so a device re-downloads an
  identical manifest on every poll. Not fixable inside Phase 11: `PlaylistResource`
  embeds `ScreenResource`, whose `status` and `last_heartbeat_at` genuinely do
  change each heartbeat, so the response bytes really are different and a stable
  ETag would be a lie. Fixing it means changing the playlist payload — Phase 12's
  territory. Pinned by `PlaylistEtagHeartbeatInteractionTest`.
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

**Scheduling**
- Saving a schedule deactivates *any* overlapping schedule on the same screen —
  including schedules belonging to other ads — silently and irreversibly.
- A schedule submitted as **inactive** still triggers that conflict resolution.
- An `active` ad attached to a screen can play outside every schedule window.
- `datetime-local` values are parsed in the app timezone; there is no per-screen or
  per-place timezone concept.
- No recurring schedules, no location targeting.

**Ads**
- Status is directly editable; there is no approval workflow and `approved_by` is
  free-form.
- `created_by`/`approved_by` are FKs to `users`, but the panel is operated by
  `admins`.
- `determineFileType()` trusts the client filename extension.
- No upload size limit on the creative.
- `AdController::update()` never calls `commitReplacedFiles()`, so every replaced
  creative stays on disk forever.
- `AdController::destroy()` deletes the creative immediately after `delete()`
  without the deferred-commit protection.
- `resolveDurationSeconds()` is dead code; the logic is inlined twice.

**Reports**
- Seeded types (`performance`, `availability`) are not producible by
  `GenerateReportRequest::TYPES` (`playback`, `screen-uptime`) and fall through to
  the playback builder and playback CSV headers.
- Generation loads every matching log into memory with no chunking or queue.
- Whole result sets are stored as JSON in `reports.data`, and the index hydrates
  every blob it lists.
