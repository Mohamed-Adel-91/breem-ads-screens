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

`device_uid` is **security-sensitive**: it is how a physical device claims an
identity. Do not casually reassign, regenerate or expose it.

## Device security

Never, without explicit architecture approval:

- trust a client-supplied device UID as authentication
- bypass or weaken token/signature validation (`screen.auth` middleware,
  `services.screens.hmac_secret`)
- expose a global shared secret to devices
- silently re-pair a device to a different screen
- accept replay-prone requests knowingly

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

`playback_logs` are operational/audit data. When the Device API is hardened,
validate that the screen exists, the ad exists, the ad is genuinely assigned to
that screen, and the report belongs to an authenticated context.

Do not change those semantics outside an approved stabilization phase.

## Heartbeat

Server-authoritative. `HeartbeatService::touch()` updates `screens.status` and
`screens.last_heartbeat` and appends a `ScreenLog`.

**Never derive online/offline in Blade or JavaScript.** Render the stored column.

## Monitoring

Presentation over operational state. It must not invent device activity, and it
must not recompute status.

## Deferred functional defects

Documented, deliberately unfixed. Do not "fix" these as a side effect of another
task.

**Device API** — registered since Phase 9; the endpoints below are live.

| Method | URI | Name | Auth |
|---|---|---|---|
| POST | `api/v1/screens/handshake` | `api.v1.screens.handshake` | signature only (no `screen.auth`) |
| POST | `api/v1/screens/heartbeat` | `api.v1.screens.heartbeat` | `screen.auth` + signature |
| GET | `api/v1/screens/{screen}/playlist` | `api.v1.screens.playlist` | `screen.auth` + signature |
| POST | `api/v1/playbacks` | `api.v1.playbacks.store` | `screen.auth` + signature |
| GET | `api/v1/config` | `api.v1.config.show` | `screen.auth` + signature |

`{screen}` resolves by **id or code**. All five carry `api` + `throttle:api.v1`
(120/min, keyed by IP). `bootstrap/app.php` must register `routes/api.php`
explicitly inside the `using:` closure — a custom `using:` callback makes the
`api:`, `web:` and `health:` arguments inert.

Authentication is two independent layers:

1. `EnsureScreenAuthentication` (`screen.auth`) — accepts a request if **any** of:
   an `X-Screen-Uid` header matching `screens.device_uid`; a resolvable `{screen}`
   route parameter; or the mere presence of a `device_uid`/`code` input. A bearer
   token is copied into request attributes and **never validated**.
2. `ApiRequest` — HMAC-SHA256 over the raw body (POST) or the full URL (GET),
   compared against `X-Screen-Signature` using `hash_equals`, keyed by the single
   global `services.screens.hmac_secret`. POST bodies additionally carry a
   `timestamp` checked against `signature_leeway` (300 s). GET has no timestamp.

**Confirmed weaknesses — do not treat the current design as secure:**
- `hasValidSignature()` returns **true when the secret is empty**, so an unset
  `SCREENS_HMAC_SECRET` disables signing entirely (fail-open).
- The HMAC secret is **global**, not per-screen, so any holder can sign for any
  screen.
- The handshake **re-pairs an already-paired screen** to whatever `device.uid` is
  supplied; the `bearer_token` it returns is literally the `device_uid`.
- The `device_uid` is a bearer-equivalent credential sent in a plain header.
- **No nonce and no used-signature store**: a captured request is replayable
  within the leeway window, and a signed GET (no timestamp) is replayable forever.
- Playback reports are **not validated against the ad↔screen assignment**.
- `GET /api/v1/config` returns every row of the `settings` table to any
  authenticated device.

All of the above are pinned by `tests/Feature/Api/DeviceApiContractTest.php` under
`test_known_finding_*` names so a fix has to be a deliberate change.

**Heartbeat / Monitoring**
- Acknowledging a monitoring alert sets `last_heartbeat = now()` with no device
  contact, making a dead screen look healthy.
- "Uptime" is `online log events / total log events`, an event ratio, not elapsed
  time. The UI labels it "Online reports" to stay honest.
- Monitoring's index eager-load `with(['logs' => fn => latest()->limit(1)])`
  applies `LIMIT 1` to the whole query, so only one row on the page gets a last
  report.
- Offline detection depends entirely on `screens:check-status`; if the scheduler
  is not running, `screens.status` silently goes stale.
- No retention or pruning for `screen_logs` / `playback_logs`.

**Scheduling**
- Saving a schedule deactivates *any* overlapping schedule on the same screen —
  including schedules belonging to other ads — silently and irreversibly.
- A schedule submitted as **inactive** still triggers that conflict resolution.
- An `active` ad attached to a screen can play outside every schedule window.
- `datetime-local` values are parsed in the app timezone; there is no per-screen or
  per-place timezone concept.
- Schedule create/update/delete does **not** flush the playlist cache, so a cached
  playlist can outlive a schedule boundary change.
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
