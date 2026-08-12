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

**Known gaps** (documented, unfixed): no `max:` size rule on ad creatives, and
`determineFileType()` derives the media type from the client filename extension.

## Rate limiting

`RateLimitServiceProvider` defines the `api.v1` limiter used by the device API.
Admin login is throttled with `throttle:10,1`.

## Operations endpoints

`routes/artisan.php` exposes cache-clear, migrate and seed over HTTP. These were
publicly reachable with no authentication — `clear-cache` ran `migrate --force`
for anyone, and the `day{NN}` check is guessable in at most 31 attempts. They are
now behind `auth:admin` + `role:super-admin`.

`run-seeder` still re-seeds real CMS content when invoked. Treat it as destructive
and consider removing it in favour of a CLI-only workflow.

## Mass assignment

`Ad`, `AdSchedule`, `Place`, `PlaybackLog`, `Report`, `Screen` and `ScreenLog` use
`$guarded = []`. Every current caller passes an explicit, validated array from a
Form Request, so this is contained but fragile.

Do **not** flip these to `$fillable` blindly — audit every `create()`/`update()`
call site first, including seeders, factories and services. A partial change
silently drops columns.

## API authentication

Device requests pass through the `screen.auth` middleware
(`EnsureScreenAuthentication`) using `services.screens.hmac_secret` and
`signature_leeway`.

**Deferred functional security work** — do not redesign these outside an approved
phase:

- `routes/api.php` is not registered, so no `/api/v1/*` route currently exists
- HMAC protocol strength and key distribution
- device pairing and `device_uid` claim lifecycle
- token lifecycle and rotation
- replay protection
- proof-of-play trust (playback reports are not validated against assignment)

## Safe remediation policy

You may fix a security defect when the intent is unambiguous, an existing
permission or rule already covers it, product semantics are preserved, and a test
can verify it. Missing middleware, an exposed debug endpoint, unsafe Blade output
and obvious upload validation gaps qualify.

Everything protocol-shaped — authentication design, pairing, tokens, signatures —
gets documented, not redesigned.
