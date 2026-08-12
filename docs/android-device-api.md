# Device API Reference (v1)

The contract between a screen player and the Laravel backend, as implemented.
Base path is `/api/v1`. Route names are frozen — see
[docs/ai/digital-signage.md](ai/digital-signage.md) for the internals.

## The short version

1. An administrator generates a **pairing code** for a screen in the admin panel.
2. The device posts that code once to `POST /api/v1/screens/handshake` and
   receives an **access token** and an **HMAC secret**. This is the only response
   that ever contains them.
3. Every later request carries the token *and* a signature computed with that
   secret, plus a timestamp and a nonce.

A device that loses its credentials cannot recover them. An administrator resets
the screen and issues a new pairing code.

## Pairing

```http
POST /api/v1/screens/handshake
Content-Type: application/json

{
  "code": "LOBBY-01",
  "pairing_code": "K7M2-9QRT-4XPW",
  "device": { "uid": "a1b2c3d4", "model": "BX-1", "os_version": "13" }
}
```

`device.uid` may instead be sent as an `X-Screen-Uid` header. It is an inventory
identifier: it records which hardware occupies the screen and grants nothing.

`201 Created`:

```json
{
  "data": {
    "screen": { "id": 12, "code": "LOBBY-01", "status": "online", "last_heartbeat_at": "..." },
    "config": { "heartbeat_interval": 60, "playlist_ttl": 300, "timezone": "UTC" },
    "auth": {
      "token_type": "Bearer",
      "access_token": "<64 hex chars>",
      "hmac_secret": "<64 hex chars>",
      "signature_algorithm": "HMAC-SHA256",
      "signature_headers": {
        "timestamp": "X-Screen-Timestamp",
        "nonce": "X-Screen-Nonce",
        "signature": "X-Screen-Signature"
      }
    },
    "meta": { "paired_at": "..." }
  }
}
```

Store both values in the device keystore. `access_token` proves identity;
`hmac_secret` signs requests. They are different values and neither is derivable
from the other.

Pairing failures:

| Status | `error` | Meaning |
|---|---|---|
| 401 | `invalid_pairing` | Unknown screen code, wrong pairing code, or an expired one. These share a response so the endpoint cannot be used to enumerate screens. |
| 409 | `already_paired` | The screen has a live credential. An administrator must reset it first. |

A pairing code is single-use, expires after `SCREENS_PAIRING_CODE_TTL` (default
900 s), and is consumed atomically — two devices racing on the same code produce
exactly one winner.

## Signing a request

Every endpoint except the handshake requires four headers:

```http
Authorization: Bearer <access_token>
X-Screen-Timestamp: 1786530930
X-Screen-Nonce: <unique per request, e.g. 32 hex chars>
X-Screen-Signature: <hex HMAC-SHA256>
```

The signature is taken over this message — six lines, joined with `\n`:

```text
<HTTP METHOD, uppercase>
<request path, leading slash, no host>
<canonical query string>
<timestamp>
<nonce>
<lowercase hex sha256 of the raw request body>
```

Rules that matter:

- **Canonical query string**: parameters sorted by name, RFC 3986 encoded,
  joined with `&`. Empty when there is no query.
- **Body hash**: `sha256("")` for a request with no body — which is what a GET
  must send. Do not let an HTTP client serialise an empty object or array into
  the body; `{}` and `[]` hash differently from `""` and the signature will fail.
- **Timestamp**: seconds since epoch, and it must be within
  `SCREENS_SIGNATURE_LEEWAY` (default 300 s) in either direction. Keep the device
  clock synchronised.
- **Nonce**: unique per credential, forever. A random 16-byte hex value is fine.
  Reusing one is rejected even if everything else is valid, and the check is
  backed by a database unique constraint, not a read-then-write.

Worked example — `GET /api/v1/screens/12/playlist` with no query and no body:

```text
GET
/api/v1/screens/12/playlist

1786530930
1b08a3f2c9d4e5a6b7c8d9e0f1a2b3c4
e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
```

(The third line is empty. The last line is `sha256` of the empty string.)

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| POST | `/api/v1/screens/handshake` | Pair once, receive credentials. |
| POST | `/api/v1/screens/heartbeat` | Report status; returns `next_heartbeat_at`. |
| GET | `/api/v1/screens/{screen}/playlist` | Fetch the playlist. Sends an `ETag`. |
| POST | `/api/v1/playbacks` | Submit a proof-of-play batch. |
| GET | `/api/v1/config` | Fetch device configuration. Sends an `ETag`. |

`{screen}` accepts the numeric id or the screen code, but a credential may only
address **its own** screen — anything else is `403 screen_mismatch`.

### Heartbeat

```json
{ "status": "online", "current_ad_code": "AD-9", "reported_at": "2026-08-12T10:00:00Z" }
```

`status` is one of `online`, `offline`, `maintenance`. Both other fields are
optional. The response carries the updated screen, the log row that was written,
and `next_heartbeat_at`.

### Playlist

Send `If-None-Match: "<etag>"` to get `304 Not Modified` when nothing changed.
The ETag covers the playlist items the device actually receives — media, timing
and ordering — so an edit to an ad's title does not invalidate a cached playlist.

Each item carries `ad_id`, `file_url`, `file_type`, `duration_seconds`,
`play_order` and the schedule window. The device plays what it is given; it never
computes its own eligibility.

### Playbacks

```json
{
  "entries": [
    { "ad_id": 5, "played_at": "2026-08-12T10:01:00Z", "duration": 15 }
  ]
}
```

Returns `202 Accepted` with `data.ingested`. Every `ad_id` is checked against the
screen's assignments — reporting an unassigned ad is `422` on `entries`, and the
whole batch is rejected. The batch is always attributed to the authenticated
screen; a `device_uid` or screen reference in the body is ignored.

### Config

Returns an allow-listed subset of settings
(`DeviceConfigService::ALLOWED_SETTING_KEYS`) plus device timings. Adding a
setting to the response is a deliberate change to that constant, not a
side effect of adding a row to the `settings` table.

## Error handling

`401` and `403` bodies carry a machine-readable `error` field. Branch on it:

| `error` | Status | What the device should do |
|---|---|---|
| `missing_token` | 401 | Bug in the client — the `Authorization` header was not sent. |
| `invalid_token` | 401 | Credentials are not recognised. Stop retrying; needs re-pairing. |
| `revoked_token` | 401 | An administrator reset the screen. Wait for a new pairing code. |
| `expired_token` | 401 | Same — re-pair. |
| `screen_mismatch` | 403 | The client is addressing the wrong screen. Bug in the client. |
| `stale_timestamp` | 401 | Clock drift. Resynchronise time, then retry. |
| `missing_nonce` | 401 | Bug in the client. |
| `invalid_signature` | 401 | Signing bug — most often an empty body serialised as `{}`/`[]`, or an unsorted query string. |
| `replayed_request` | 401 | The nonce was already used. Generate a fresh one; never retry a request verbatim. |

`429 Too Many Requests` means back off. Authenticated traffic is limited to
120/min per credential; the handshake is limited to 10/min per IP.

Retries must re-sign with a **new** nonce and a current timestamp. Replaying a
stored request will always fail.
