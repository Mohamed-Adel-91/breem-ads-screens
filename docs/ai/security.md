# Security

## Baseline expectations

| Area | Expectation |
|---|---|
| Validation | Every write goes through a Form Request or explicit `validate()` |
| Authorization | Enforced by route middleware, not only by hiding a button |
| CSRF | Every state-changing form has `@csrf`; every non-POST has `@method` |
| Escaping | `{{ }}` everywhere; `{!! !!}` only for CMS-trusted HTML |
| Uploads | Server-side MIME validation; generated filenames; fixed destination |
| Secrets | `.env` only — never in code, views, logs or client payloads |
| Mass assignment | Audited per model; `$guarded = []` requires trusted callers |
| Debug | `APP_DEBUG=false` in production |

## Authorization

Two layers must agree:

1. **Route middleware** — `permission:ads.edit`, `role:super-admin`,
   `role_or_permission:...`. This is the real boundary.
2. **UI visibility** — `@can(...)` around buttons, and `config/admin_menu.php` for
   sidebar entries.

Hiding a control without gating its route is not authorization. Gating a route
without hiding the control is only a UX problem — fix the middleware first.

Permission names are a contract; use the ones that already exist
(`places.*`, `screens.*`, `ads.*`, `monitoring.*`, `reports.*`, `logs.*`,
`cms.manage`, `settings.*`, `users.view`, `admins.*`, `permissions.*`,
`contact_submissions.*`). **Never invent a permission.**

`Gate::before` grants everything to the `super-admin` role — remember this when
testing that a restriction works.

## Blade escaping

`{!! !!}` is currently used in exactly these places, all deliberate:

- the CMS-managed footer map iframe — admin-authored trusted HTML
- `nl2br(e($value))` — escaped *before* the raw echo
- validation messages, now passed through `array_map('e', ...)`

Never pass user-submitted data to `{!! !!}` unescaped, and never interpolate
unescaped data into a JavaScript template literal.

## Uploads

All managed uploads go through `FileService`:

- MIME validated server-side by the Form Request (`mimetypes:` for ad creatives,
  `image` for CMS images)
- filename generated as `time() . Str::random(20) . '.' . extension` — the client
  filename is never used as the stored name, so path traversal is not reachable
- destination is a fixed folder resolved by `App\Support\UploadPath`
- replacement is transactional: commit deletes the old file, failure discards the
  new one
- files land under `public/`, so they are intentionally publicly readable — do not
  store anything sensitive there

### Ad creatives

`App\Support\CreativeMedia` is the single authority for what a creative may be —
accepted MIME types, the category each maps to, the stored extension, and the size
ceiling per category. That list used to be duplicated across two Form Requests, the
controller and the Blade form, which is how they came to disagree.

Two rules, both server-side:

1. **The file's contents decide what it is.** `mimetypes:` validates the detected MIME
   type, and `CreativeMedia::categoryOf()` classifies from the same value. The
   controller's old `determineFileType()` read the *client filename extension*
   instead, so an MP4 named `holiday.jpg` was accepted as a video and recorded as an
   image.
2. **The stored extension is derived, never copied.** `CreativeMedia::extensionOf()`
   supplies it from the detected MIME type, so a creative cannot land in a web-served
   directory carrying an executable suffix chosen by the uploader. `FileUploadTrait`
   accepts the override; passing nothing keeps the old client-extension behaviour for
   callers with no trusted alternative, so **always pass one where the type can be
   detected**.

Laravel's own `mimetypes` rule independently refuses php-family client extensions.
That is a second layer, not the primary defence — do not rely on it alone, and do not
remove it.

Size ceilings come from `config('ads.upload')` (`ADS_IMAGE_MAX_KB`, `ADS_GIF_MAX_KB`,
`ADS_VIDEO_MAX_KB`). A blanket `max:` rule rejects anything above the largest limit,
then `ValidatesCreativeUpload` narrows it to the limit for the detected category — so
a 150 MB video allowance never becomes a 150 MB allowance for a JPEG. These are an
application ceiling on top of PHP's `upload_max_filesize` / `post_max_size` and any
web-server body limit, not a replacement for them.

## Rate limiting

`RateLimitServiceProvider` defines two device limiters: `api.v1` (120/min) for
authenticated traffic, and `api.v1.handshake` (10/min) for the unauthenticated
pairing endpoint. Keep them separate — a shared bucket lets pairing-code guessing
burn a real device's budget, and lets a busy device relax the guessing limit.
Admin login is throttled with `throttle:10,1`.

## Operations endpoints

`routes/artisan.php` exposes cache-clear, migrate and seed over HTTP. These were
publicly reachable with no authentication — `clear-cache` ran `migrate --force`
for anyone, and the `day{NN}` check is guessable in at most 31 attempts. They are
now behind `auth:admin` + `role:super-admin`.

`run-seeder` still re-seeds real CMS content when invoked. Treat it as destructive
and consider removing it in favour of a CLI-only workflow.

## Mass assignment

`AdSchedule`, `Place`, `PlaybackLog`, `Report`, `Screen` and `ScreenLog` use
`$guarded = []`. Every current caller passes an explicit, validated array from a
Form Request, so this is contained but fragile.

Do **not** flip these to `$fillable` blindly — audit every `create()`/`update()`
call site first, including seeders, factories and services. A partial change
silently drops columns.

`Ad` is the exception: Phase 13 converted it to an explicit `$fillable` after
tracing every call site, because the ads module was in scope. Note that being
fillable is not the same as being form-settable — `status`, `approved_by_admin_id`
and `approved_at` are fillable (the lifecycle action and factories write them) but
are absent from `StoreAdRequest` and `UpdateAdRequest` entirely, so no request field
can reach them. Adding a column to `ads` means adding it to `$fillable` too, or
writes will silently drop it.

## API authentication

Device requests pass through `screen.auth` (`EnsureScreenAuthentication`), which
since Phase 10 requires **all four** of: a per-device bearer token, an HMAC-SHA256
signature, a timestamp within `services.screens.signature_leeway`, and a nonce
that credential has not used. It fails closed at every step. See
[digital-signage.md](digital-signage.md) for the canonical message, the error-code
order and the three supporting tables.

Credential storage rules — these are the invariants, not implementation detail:

- the bearer token is stored **only** as `hash('sha256', $token)`; the plaintext
  is returned once, at pairing, and never again
- the HMAC secret is stored **encrypted** (`'encrypted'` cast) and is distinct
  from the token
- `ScreenDeviceCredential::$hidden` covers `token_hash` and `hmac_secret`, and no
  API Resource reads either
- pairing codes are hashed, single-use and time-limited; consumption is atomic
- there is **no fleet-wide signing secret** — `SCREENS_HMAC_SECRET` was retired

**Deferred functional security work** — do not redesign these outside an approved
phase:

- credential expiry and rotation policy (`expires_at` is enforced but unset at
  pairing; recovery is an administrator reset plus re-pair)
- scheduled nonce pruning (currently opportunistic)
- per-fleet handshake throttling (the pairing limiter is keyed by IP)
- heartbeat truth, uptime-by-elapsed-time and offline-scheduler redesign

## Safe remediation policy

You may fix a security defect when the intent is unambiguous, an existing
permission or rule already covers it, product semantics are preserved, and a test
can verify it. Missing middleware, an exposed debug endpoint, unsafe Blade output
and obvious upload validation gaps qualify.

Everything protocol-shaped — authentication design, pairing, tokens, signatures —
gets documented, not redesigned.
