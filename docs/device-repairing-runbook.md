# Runbook — device credentials and screen health

## Part A — the scheduler is mandatory

**Offline detection only works if the Laravel scheduler is running.** Without it,
`screens.status` silently goes stale: a dead screen keeps reporting "online"
forever and no offline alert is ever raised.

Install exactly one entry per application host:

```cron
* * * * * cd /path/to/breem-ads-screens && php artisan schedule:run >> /dev/null 2>&1
```

Or as a systemd timer, if that is the house style:

```ini
# /etc/systemd/system/breem-scheduler.service
[Service]
Type=oneshot
WorkingDirectory=/path/to/breem-ads-screens
ExecStart=/usr/bin/php artisan schedule:run

# /etc/systemd/system/breem-scheduler.timer
[Timer]
OnCalendar=*:0/1
AccuracySec=1s
```

Verify what is registered:

```bash
php artisan schedule:list
```

Expected — **three** entries:

| Frequency | Task |
|---|---|
| `* * * * *` | Mark screens offline after 120s without a heartbeat |
| `0 9 * * *` | Notify administrators about ads nearing their end date |
| `30 3 * * *` | `model:prune` for `ScreenLog`, `PlaybackLog`, `Report` (a no-op until a retention value is set) |

If `schedule:list` names a command you cannot run by hand, stop. Breem has shipped a
scheduler entry for a command that did not exist, so it failed on every tick while
still being displayed here. A test now pins both that the sweep is registered and
that no phantom command remains.

The offline sweep is idempotent, so a missed tick costs only detection latency,
never correctness. If the scheduler has been down, screens that died during the
outage are transitioned on the next successful run.

**Set `OPS_NOTIFICATION_EMAIL`.** With no recipient configured the sweep still
transitions screens correctly but tells nobody — delivery is skipped and a warning is
logged. `ADMIN_EMAIL` is honoured as a fallback, resolved at read time, so an older
deployment keeps working; new deployments should set the operations key explicitly.
`php artisan ops:status` reports which is in effect and **exits 1 when neither is**.

Relevant settings:

| Key | Default | Meaning |
|---|---|---|
| `SCREENS_HEARTBEAT_INTERVAL` | 60 | Cadence advertised to devices, in seconds. |
| `SCREENS_OFFLINE_AFTER` | `interval × 2` (120) | Silence tolerated before a screen is called offline. Floored at `interval + 1`. |
| `SCREENS_PAIRING_CODE_TTL` | 900 | Pairing-code lifetime, in seconds. |
| `OPS_NOTIFICATION_EMAIL` | — | Recipient for operational alerts. `ADMIN_EMAIL` is the fallback. |

There is one queue dependency: `CheckScreenHealthJob` and the notifications it
sends are queued, so a **supervised** worker must also be running —
[`production-deployment.md`](production-deployment.md#2-queue-worker-supervision) has
the systemd and Supervisor units.

---

## Part B — re-pairing the fleet after the Phase 10 credential change

**This is a breaking change for every device already in the field.** Read it
before deploying.

### What changed and why every device breaks

Before Phase 10, a device authenticated by sending its `device_uid` and a
signature made with a single fleet-wide secret (`SCREENS_HMAC_SECRET`). Both
values were effectively public: the UID travelled in a plain header on every
request, and one leaked secret signed for every screen in the fleet.

After Phase 10, a device authenticates with a **per-device** bearer token and a
**per-device** HMAC secret, both minted during pairing and stored in
`screen_device_credentials`. `device_uid` is now an inventory field and
authenticates nothing.

Existing screens have a `device_uid` but **no credential row**. There is no way
to derive one: the new token and secret are random, and the old shared secret is
not a valid substitute for either. So on the first request after deploy every
existing device receives:

```json
{ "error": "missing_token" }
```

and the screen stops fetching playlists. The screens will show as `offline` once
the offline sweep next runs (see Part A). **Every screen must be re-paired by hand.**

This is the intended outcome, not a defect. A migration that auto-issued
credentials to already-known `device_uid` values would grant a credential to
whatever hardware currently claims that UID — which is precisely the weakness
Phase 10 removes.

### Before you deploy

1. **Schedule a window.** Content stops rotating on each screen until that screen
   is re-paired. Devices keep playing whatever they last cached, so screens go
   stale rather than blank, but nothing new reaches them.
2. **Confirm physical or remote access** to every device. Pairing requires
   entering a code on the device.
3. **Count the work.** One screen at a time through the admin panel; budget a few
   minutes each. There is deliberately no bulk-issue command — see *Limitations*.
4. **Take a database backup.** The three new tables are additive and `screens` is
   untouched, but back up anyway.

### Deploy

Follow the full sequence in
[`production-deployment.md`](production-deployment.md#4-the-deployment-sequence). In
outline:

```bash
/usr/local/bin/breem-db-backup.sh && /usr/local/bin/breem-media-backup.sh
git fetch --all --tags && git checkout <tag>
rm -f bootstrap/cache/*.php
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force    # additive only; screens is not altered
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart      # or the worker keeps running the old code
```

**These migrations do not need `php artisan down`.** They are additive and run in
milliseconds, and maintenance mode returns 503 for `/api/v1/*` too — so the fleet stops
heartbeating while the offline sweep keeps running, and you generate the very offline
alerts you were trying to avoid.

The migrations create `screen_device_credentials`, `screen_pairing_codes` and
`screen_request_nonces`. None of them alter an existing table, so a rollback of
the application code leaves no orphaned schema changes on `screens`.

Remove `SCREENS_HMAC_SECRET` from the production `.env`. Nothing reads it any
more; leaving it there implies a fleet secret still exists.

Add `SCREENS_PAIRING_CODE_TTL` if you want a lifetime other than the 900-second
default.

### Re-pair one screen

1. Admin panel → **Screens** → open the screen.
2. In the **Device pairing** panel, click **Reset device** if the screen still
   shows as paired from before. (Screens carried over from the old scheme have no
   credential and will already show *Not paired*.)
3. Click **Generate pairing code**. The code is displayed **once** — it is stored
   hashed and cannot be shown again. Regenerating invalidates the previous one.
4. Enter the code on the device along with the screen code.
5. The device calls `POST /api/v1/screens/handshake` and stores the token and
   HMAC secret it receives. Those values are also returned only once.
6. Confirm the screen flips to **online** and the pairing panel shows a paired-at
   timestamp.

A code expires after `SCREENS_PAIRING_CODE_TTL`. Generate it when you are ready
to use it, not in a batch the day before.

### Verifying the fleet

```sql
-- Screens with no live credential — these still need re-pairing.
SELECT s.id, s.code
FROM screens s
LEFT JOIN screen_device_credentials c
  ON c.screen_id = s.id AND c.revoked_at IS NULL
WHERE c.id IS NULL;
```

```sql
-- Credentials issued but never used: paired, but the device has not called yet.
SELECT screen_id, issued_at
FROM screen_device_credentials
WHERE revoked_at IS NULL AND last_used_at IS NULL;
```

The Monitoring page is the operational view, but it reports stored status; the
queries above are the authoritative answer to "is this screen paired".

### Rollback

> **There is no route back to the fleet-secret scheme.** An earlier version of this
> runbook said you could restore `SCREENS_HMAC_SECRET` and "the old scheme resumes".
> That is no longer true and following it would waste an outage: the shared-secret
> authentication path was removed from the codebase, and Phases 11–15 built
> server-authoritative heartbeats, offline detection, playlist cache boundaries and the
> approval workflow on top of per-device credentials. Rolling back far enough to restore
> it would roll back all of that too.
>
> `SCREENS_HMAC_SECRET` is read by nothing. Delete it from `.env`.

To roll back the **application code** to another post-Phase-10 release, follow
[`production-deployment.md` §5](production-deployment.md#5-rollback). Two rules specific
to pairing:

1. **Leave the three credential tables in place.** Dropping them destroys every
   credential already issued, forcing every re-paired screen through the whole process
   again when you roll forward. They are additive; old code ignores them.
2. **Never change `APP_KEY` as part of a rollback.** `hmac_secret` is stored with an
   `encrypted` cast, so a different key makes every device's secret undecryptable and
   the **entire fleet** must be re-paired by hand. If `APP_KEY` has already been changed,
   restoring the previous value is the only non-manual fix.

### Routine operations after migration

| Situation | Action |
|---|---|
| Device replaced or reimaged | Reset the device on the screen, generate a code, pair the new hardware. |
| Device lost its credentials | Same — they cannot be recovered or re-sent. |
| Suspected compromise | Reset the device. This revokes the credential immediately; the old one returns `revoked_token` on its next request. |
| Screen decommissioned | Reset the device so no live credential remains. |

Resetting one screen affects only that screen. There is no fleet-wide secret, so
there is nothing fleet-wide to rotate.

### Pairing and playback failures — what the device is telling you

Every `401`/`403` from the Device API carries a machine-readable `error` field. Read it
before touching anything; each one has a different cause and a different fix. The full
contract is in [`android-device-api.md`](android-device-api.md).

| `error` | Status | What it actually means | Fix |
|---|---|---|---|
| `invalid_pairing` | 401 | Unknown screen code, wrong code, or an expired one. Deliberately one response for all three, so the endpoint cannot be used to enumerate screens. | Generate a fresh code and use it promptly. Check the **screen code** as well as the pairing code. |
| `already_paired` | 409 | The screen already holds a live credential. | **Reset device** first, then generate a new code. |
| `missing_token` | 401 | No `Authorization` header. On an existing screen after the Phase 10 deploy this is the expected state — it has never been paired. | Pair it. |
| `invalid_token` | 401 | The token is not recognised. | Re-pair. It cannot be recovered. |
| `revoked_token` | 401 | An administrator reset the screen. | Generate a code and re-pair. |
| `expired_token` | 401 | `expires_at` has passed. Pairing leaves it null, so this only appears if one was set deliberately. | Re-pair. |
| `screen_mismatch` | 403 | The device is addressing a screen that is not its own. A client bug, not a configuration problem. | Fix the player's screen reference. |
| `stale_timestamp` | 401 | The device clock is more than `SCREENS_SIGNATURE_LEEWAY` (300 s) out. | Enable NTP on the device. This is the single most common field failure. |
| `invalid_signature` | 401 | The canonical message does not match. Almost always an HTTP client serialising an empty GET body as `{}` or `[]` — which hashes differently from `""` — or an unsorted query string. | Fix the player's signing code. |
| `replayed_request` | 401 | The nonce was already used. | Never retry a stored request verbatim: re-sign with a **fresh** nonce and a current timestamp. |
| `429` | — | Rate limited. 10/min per IP on the handshake, 120/min per credential after. | Pace the work; see *Limitations*. |

Recovery paths that need no intervention:

- **A screen that was marked offline comes back on its own.** The first valid heartbeat
  transitions it back to online and records the recovery in the log stream. Nobody has to
  clear anything in the admin panel, and there is deliberately no "back online" email —
  recovery is visible in Monitoring.
- **A device that loses network keeps playing its cached playlist** and resumes
  heartbeating when the network returns.

Failures that do need intervention:

- **A paired screen that never appears online.** Check `last_used_at` on its credential
  (see the queries above). Null means the server has never seen a signed request from it —
  suspect the device's stored credentials, its clock, or its network, not the pairing.
- **Repeated `invalid_signature` from one device only.** A player build problem. Compare
  against the worked example in [`android-device-api.md`](android-device-api.md).
- **Every device failing at once after a deploy.** Check `APP_KEY` first. If it changed,
  every `hmac_secret` is undecryptable and the whole fleet needs re-pairing.

### Limitations

- **No bulk pairing.** Codes are issued per screen through the admin panel. A
  CLI bulk-issue command would have to write codes somewhere readable, which
  re-creates a shared-secret file in all but name. If a large fleet makes the
  manual flow impractical, treat adding a command as a scoped piece of work with
  an explicit decision about where the codes go.
- **The handshake limiter is keyed by IP** at 10/min. A site re-pairing many
  devices behind one NAT address will hit it. Pace the work, or re-pair from
  different networks.
- **Credentials do not expire on their own.** `expires_at` is enforced when set,
  but pairing leaves it null. Retirement is by reset, not by timeout.
