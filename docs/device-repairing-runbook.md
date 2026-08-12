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

Expected — two entries:

| Frequency | Task |
|---|---|
| `* * * * *` | Mark screens offline after 120s without a heartbeat |
| `0 9 * * *` | Notify administrators about ads nearing their end date |

The offline sweep is idempotent, so a missed tick costs only detection latency,
never correctness. If the scheduler has been down, screens that died during the
outage are transitioned on the next successful run.

**Also set `ADMIN_EMAIL`.** Offline notifications are built from it; with no
recipient configured the sweep still transitions screens correctly but tells
nobody.

Relevant settings:

| Key | Default | Meaning |
|---|---|---|
| `SCREENS_HEARTBEAT_INTERVAL` | 60 | Cadence advertised to devices, in seconds. |
| `SCREENS_OFFLINE_AFTER` | `interval × 2` (120) | Silence tolerated before a screen is called offline. Floored at `interval + 1`. |
| `ADMIN_EMAIL` | — | Recipient for offline alerts. |

There is one queue dependency: `CheckScreenHealthJob` and the notifications it
sends are queued, so `php artisan queue:work` must also be running.

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

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate            # additive only; screens is not altered
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

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

Application rollback works: revert the code, restore the previous `.env`
including `SCREENS_HMAC_SECRET`, and the old scheme resumes — devices still hold
their `device_uid`, which is untouched.

Leave the three new tables in place during a rollback. Dropping them destroys any
credentials already issued to screens you re-paired, forcing those screens
through the whole process again when you roll forward.

### Routine operations after migration

| Situation | Action |
|---|---|
| Device replaced or reimaged | Reset the device on the screen, generate a code, pair the new hardware. |
| Device lost its credentials | Same — they cannot be recovered or re-sent. |
| Suspected compromise | Reset the device. This revokes the credential immediately; the old one returns `revoked_token` on its next request. |
| Screen decommissioned | Reset the device so no live credential remains. |

Resetting one screen affects only that screen. There is no fleet-wide secret, so
there is nothing fleet-wide to rotate.

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
