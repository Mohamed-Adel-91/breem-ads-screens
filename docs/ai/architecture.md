# Architecture

Breem is **one Laravel 12 monolith**. Three delivery surfaces share one codebase,
one database and one set of models:

| Surface | Entry | Views |
|---|---|---|
| Public website | `routes/web.php` → `App\Http\Controllers\Web\*` | `resources/views/web/**` |
| Admin dashboard | `routes/admin.php` → `App\Http\Controllers\Admin\*` | `resources/views/admin/**` |
| Device API | `routes/api.php` → `App\Http\Controllers\Api\*` | JSON resources |

Operations endpoints live in `routes/artisan.php` (super-admin only).

## Layering

```
Route  →  Middleware  →  Form Request  →  Controller  →  Service / Support  →  Model
                                              ↓
                                    View  or  API Resource
```

Data flows **down**; nothing flows back up. A view never reaches past the
controller, and a model never knows about HTTP.

## Thin controllers

A controller should do HTTP orchestration only:

- resolve the request (via a Form Request when validation is non-trivial)
- call a service or perform straightforward CRUD
- prepare exactly the data the view or Resource needs
- return a view, redirect or Resource

Keep **out** of controllers anything reusable: parsing, normalization, business
rules, complex mapping, locale resolution, filesystem algorithms, upload
algorithms.

**Do not split a controller because it is long.** Length alone is not a defect.
Refactor only when there is genuine duplication, a genuinely mixed
responsibility, a testability problem, or clearly reusable logic. Breem's CMS
controllers are long because each page has many sections; that is inherent, and
their shared behaviour already lives in `Cms\BasePageContentController`.

## `app/Support`

Reusable lightweight logic. Flat, small, no ceremony. Current members:

| Class | Responsibility |
|---|---|
| `Lang` | translate with a fallback string |
| `MediaUrl` | stored relative path → public URL (local or S3) |
| `UploadPath` | stored relative path → absolute filesystem path |
| `VideoProbe` | read a video duration via ffprobe |

Do **not** create Actions, Managers, Handlers, Processors, Factories or
Repositories to hold an extracted method. A static helper or a service method is
enough.

## Services

`app/Services` is for genuine capabilities, not for formatters. Current members:

| Service | Capability |
|---|---|
| `Screen\AdSchedulerService` | resolve and cache a screen's playlist |
| `Screen\HeartbeatService` | apply a device heartbeat and write its log |
| `Screen\ScreenApiService` | device-facing playlist + ETag negotiation |
| `Playback\*` | playback reporting |
| `Config\*` | device configuration payload |
| `Admin\MenuBuilder` | build the admin sidebar from config + permissions |
| `FileService` | upload, replace and delete managed media |

If the thing you are about to write only maps or formats data, it is not a
Service. Put it in `app/Support` or inline it.

## Validation

- Non-trivial validation → Form Request under `app/Http/Requests/**`.
- Reusable constraints → `app/Rules`.
- Never duplicate the same rules in both a controller and a request.
- Trivial single-field actions may validate inline.

## Middleware

For genuine cross-cutting HTTP concerns only. Breem's aliases:

`auth` · `guest` · `role` · `permission` · `role_or_permission` · `screen.auth` ·
`setLocale`

## One source of truth

Never duplicate these; find the existing owner and use it:

| Concern | Owner |
|---|---|
| Locale resolution | `SetLocaleFromRequest` middleware + `{lang?}` route prefix |
| Screen status values | `App\Enums\ScreenStatus` |
| Ad status values | `App\Enums\AdStatus` |
| Place types | `App\Enums\PlaceType` |
| Media path → URL | `App\Support\MediaUrl` |
| Media path → disk | `App\Support\UploadPath` |
| Playlist rules and cache keys | `Screen\AdSchedulerService` |
| CMS translated JSON shape | `Cms\BasePageContentController` + `PageSection` / `SectionItem` casts |
| Admin menu + permission visibility | `config/admin_menu.php` + `Admin\MenuBuilder` |
| Validation rules | the Form Request for that action |

## Preserving repository structure

**Preserve standard Laravel repository structure unless the project owner
explicitly requests structural removal.**

"Zero runtime consumers" is a valid argument for deleting *application* code. It
is **not** a valid argument for deleting a file that belongs to any of these
categories:

- Laravel standard scaffold
- framework convention
- developer tooling structure
- repository-standard configuration

For those categories the rule is **zero consumers ⇒ retain**, and only the
project owner can approve removal.

Currently protected — do not delete, rename, move, empty or replace with a
placeholder:

| Path | Category |
|---|---|
| `package.json` | Laravel scaffold |
| `vite.config.js` | Laravel scaffold |
| `tailwind.config.js` | tooling configuration |
| `postcss.config.js` | tooling configuration |
| `resources/js/` | Laravel scaffold |
| `resources/css/` | Laravel scaffold |
| `public/cms/` | persistent CMS media — database-addressed |
| `public/upload/` | persistent ad creatives — served to devices |
| `public/frontend/` | runtime contract: the public layout's `<base href>` and `media_path()`'s default prefix |
| `resources/fonts/thmanyah/` | licensed font package, incl. licence PDFs |

The last four rows are protected for the opposite reason to the first six: they are
**load-bearing**. `public/cms/` and `public/upload/` are addressed by paths stored in the
database, `public/frontend/` is the public layout's `<base href>` and the prefix
`media_path()` applies to bare stored paths, and `resources/fonts/thmanyah/` holds the
licence for a font the admin ships. Renaming any of them breaks running behaviour
silently — no exception, just missing media. See
[`frontend-blade.md`](frontend-blade.md#static-assets--who-owns-what-under-public).

The **scaffold** rows above carry no runtime dependency: Breem's admin runtime does not
use Vite, Tailwind, Alpine, Node or any frontend build step, and production deploys with
Composer alone. Keeping the files does not change that, and restoring them must
never be read as licence to wire a build step back in — see
[frontend-blade.md](frontend-blade.md#prohibited).

This does **not** extend to confirmed dead *application* code. Obsolete Breeze
auth pages and controllers, dead auth routes, orphaned dashboard views and legacy
admin layouts were removed as application-level dead code, which is a separate
category. Do not restore them without an independent audit proving they are
needed.

## Comments

Applies **only to files you are already modifying**. Never sweep the repository
for comment style.

Remove comments that repeat the next line, are outdated, look generated, or
explain obvious CRUD/loops/conditions. Prefer self-documenting names.

Keep a comment when it explains *why*, records a non-obvious constraint, or warns
about a trap. At most one short responsibility comment above a class.

Keep PHPDoc only where it earns its place: array shapes, generics, static
analysis, framework contracts, IDE inference, non-obvious return contracts.
