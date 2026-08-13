# Frontend — Blade

Breem's UI is **server-rendered Blade**. There is no build step and no JavaScript
framework.

## Admin dashboard — final architecture

| Aspect | Value |
|---|---|
| Layout | `resources/views/admin/layouts/master.blade.php` |
| CSS/JS | static files under `public/admin-assets/` |
| Framework | Bootstrap 4 (+ jQuery, Popper, SimpleBar) |
| Project styles | `public/admin-assets/css/breem-admin.css` |
| Project scripts | `public/admin-assets/js/breem-admin.js`, `cms-admin.js` |
| Typeface | **Thmanyah Sans**, self-hosted — `public/admin-assets/fonts/thmanyah/` |
| Icon font | Feather — `public/admin-assets/fonts/feather.*` via `css/feather.css` |
| Images / favicon | `public/admin-assets/images/` |
| Build step | **none** |

Every admin view starts:

```blade
@extends('admin.layouts.master')
@section('title', $pageName)
@section('content') ... @endsection
```

`master` already renders the header, sidebar, flash alerts and footer. **Do not
include an alerts partial again inside a page.**

## Public website

Blade under `resources/views/web/**`, assets under `public/frontend/`. Content
comes from the CMS — see [`cms.md`](cms.md).

## Static assets — who owns what under `public/`

Four roots, four different rules. **The distinction that matters is static-vs-persistent,
not tidy-vs-untidy**: two of these directory names are runtime contracts and cannot be
renamed for cosmetic reasons.

| Root | Class | Rule |
|---|---|---|
| `public/admin-assets/` | static admin assets | **Canonical.** Never rename. `css/`, `js/`, `fonts/`, `images/` |
| `public/frontend/` | static public-website assets | **Runtime contract — never rename.** See below |
| `public/cms/` | persistent CMS media | Database-addressed. Never rename, never move files |
| `public/upload/` | persistent application media | Ad creatives; the Device API serves these URLs. Never rename |

`public/frontend/` is not just a folder of files. The web layout sets
`<base href="{{ asset('frontend') }}/">`, so **every relative URL on the public site
resolves through it**, and `media_path()` prefixes any bare stored path with `frontend/`
— so a CMS row holding `img/logo.png` means `public/frontend/img/logo.png`. Renaming it
breaks the whole public site and every CMS media row in one move.

Also retained, deliberately:

- `public/assets/` — a legacy theme tree still consumed by `404.blade.php` (particles JS).
  Heavily coupled by relative `url()` references. Left in place; not a new home for anything.
- `public/images/` — holds the ads fallback creative that `config('ads.fallback.image')`
  points at and devices fetch.

### Naming

First-party static files: **lowercase kebab-case**, named for purpose —
`breem-admin.css`, `breem-logo.png`, `thmanyah-sans-regular.woff2`. No `custom2.css`,
no `final`, no `copy`, no bare numeric suffixes. Vendor filenames stay as the vendor
shipped them. Uploaded media keeps whatever the upload service assigns — never
mass-rename it.

Reference assets with `asset('admin-assets/…')`. Never build a URL from `APP_URL`,
`public_path()` or a hostname: a filesystem path and a browser URL are different things.

### Caching

No build pipeline means no content hashes, so static admin CSS/JS is cached by the
browser under the web server's default headers and a changed file can be served stale
until that expires. A deploy that changes a stylesheet may need a hard reload. This is
accepted: the admin is a small internal audience. **Do not add a manifest, a build step
or Node to solve it** — if it ever becomes a real problem, a query-string version on the
few first-party files is the whole fix.

## Typography — the Thmanyah type system

**Three families, three jobs.** Both surfaces use all three, in Arabic and English alike.
Semantic purpose decides the family, never the HTML tag:

| Token | Family | Job |
|---|---|---|
| `--breem-font-display` | Thmanyah **Serif Display** | hero and section headings, admin page titles |
| `--breem-font-text` | Thmanyah **Serif Text** | paragraphs, prose, CMS body copy, admin descriptive copy |
| `--breem-font-ui` | Thmanyah **Sans** | navigation, controls, labels, metadata, **and every numeral** |

Selectors reference the **token**, never a family name. The mapping lives in three
declarations under `:root` in each surface's main stylesheet and nowhere else.

| Concern | Admin | Public website |
|---|---|---|
| Font files (WOFF2) | `public/admin-assets/fonts/thmanyah/` | `public/frontend/fonts/thmanyah/` |
| `@font-face` — the only place | `public/admin-assets/css/fonts.css` | `public/frontend/css/fonts.css` |
| Tokens applied | `css/breem-admin.css` | `css/master.css` |
| Body default | **Sans** — it is an operations console | **Serif Text** — prose is most of the page |
| Faces shipped | Sans 300/400/500/700 · Display 700 · Text 400 (6 files, 460 KB) | Display 400/700 · Text 400/500/700 · Sans 400/500/700 (8 files, 626 KB) |
| Preloaded | Sans 400, Sans 500, Display 700 | Text 400, Display 700, Sans 700 |

**The two surfaces differ on purpose.** The public site is editorial, so its body is the
reading face and headings are display. The admin is a data console: Sans is the default
and serif appears in exactly two places — `.page-title` / `.dashboard-welcome h2`
(Display) and `.admin-page-header p` / `.admin-prose` (Text). **Do not make admin tables,
filters, badges, pagination, the sidebar or `.card-title` serif** — a test asserts serif
never reaches them.

The complete supplied package — all three families, OTF and WOFF2, both licences and the
design guide — lives outside the web root at `resources/fonts/thmanyah/`. Take any new
weight or family from there; never download another copy.

Shared faces are byte-identical across the two surfaces, and that duplication is
deliberate: ownership is explicit, the public site never acquires a semantic dependency
on an admin directory. A test asserts the bytes match so they cannot silently diverge.

Rules:

1. **Local only.** Never `font.thmanyah.com`, never Google Fonts, never a CDN. The files
   are here; a remote font adds an external dependency, a privacy dependency, latency and
   an outage risk for nothing.
2. **One declaration per surface.** The admin sets the family on `body.breem-admin`; the
   public site sets it on `html, body` in `master.css`. Everything else inherits, form
   controls included. Do not restate the family on components. The only exceptions are
   the vendor selectors that hardcode a family and therefore cannot inherit — in the
   admin, `.tooltip`, `.popover` and the gauge label.
3. **Never use `* { font-family: … }`,** and never override an icon-font selector. The
   admin's Feather declares `font-family: "feather" !important`; the public site loads
   Font Awesome. Catching either turns every icon into a tofu box. `code`/`pre`/`kbd`/
   `samp` keep monospace.
4. **Weights 300/400/500/700 only** — those are the faces shipped. Rules asking for 600
   resolve upward to the real 700 file, which is why no synthetic 600 face is declared.
   Adding a weight means adding both the file and its `@font-face`.
5. **Arabic and English use the same families, on both surfaces.** There is no
   per-direction font stack and there must not be one; the fallbacks exist only for a
   glyph Thmanyah cannot render. Do not add Latin-oriented `letter-spacing` to shared
   text rules — the public stylesheet currently declares none, and Arabic should not
   inherit tracking designed for Latin.
6. **Classify by job, not by tag.** `h1`–`h3` are Display; `h4`–`h6` are deliberately
   not, because on this site they are compact labels (`.who_we h4` is an icon-prefixed
   feature label). A statistic figure is Sans because it is a metric, and its caption is
   Sans because it belongs to the metric — **never Serif Text on a counter.** Use
   `.font-display` / `.font-text` / `.font-ui` for the exceptions rather than restating a
   family.
7. **Changing a stylesheet does not invalidate a visitor's cache.** There is no build
   pipeline, so no content hashes: after deploying a typography change, a returning
   visitor can keep the previous CSS and font until their cache expires. Expect to hard
   refresh when checking, and purge the CDN/web-server cache for
   `frontend/css/*` and `admin-assets/css/*` as a deploy step. Do **not** add a manifest,
   a build step or Node for this; if it ever needs solving in the application, a
   config-driven version query on the few first-party stylesheets is the whole fix
   (`filemtime()` in Blade is not an option — rule 1 below forbids filesystem calls in
   views).

## Numerals — Arabic-Indic in Arabic, Western in English

Arabic presentation shows `٠١٢٣٤٥٦٧٨٩`; English shows `0123456789`. A font does not do
this — U+0031 is "1" in every typeface — so it is an explicit presentation transform.

**One formatter: `App\Support\LocalizedDigits`,** with `localized_digits($value)` as the
Blade shorthand. Locale comes from `App::getLocale()` (set by `SetLocaleFromRequest`) and
there is no second locale source. Do not scatter `strtr()` maps through views.

| Use it for | Never use it on |
|---|---|
| visible counters and statistics | route parameters, query strings (`?page=2`) |
| result totals, pagination **labels** | `href` / `tel:` values |
| stat cards, displayed durations | API or JSON output |
| a date already formatted for a human | screen codes (`SCR-001`), device UIDs, UUIDs, tokens |
| the visible part of a phone number | filenames, media paths, CSS classes, HTML ids, `data-*` |

The rule of thumb: **if a computer will read it again, leave it alone.** Digits are
substituted; separators, `+`, `-`, `%` and `/` are not — Arabic has its own separator
marks but choosing them is an unmade product decision, and guessing would change what
every decimal means.

Never localize by walking the DOM and replacing text nodes. That corrupts codes, copied
values and structured data. Localize server-side at the rendering boundary.

Where it is applied today: the public statistics figures, the header phone label, admin
`.admin-stat-value` cards, the shared results-summary and pagination partials, and the
published `resources/views/vendor/pagination/bootstrap-4.blade.php` — where the **label**
is localized and `$url` is untouched.

**Known limit:** numerals an editor typed inside rich CMS HTML are left as authored.
Localizing them safely would need HTML parsing, and a blind replacement would corrupt
URLs, iframe sources and element ids. Application-generated numbers are covered.

## Rules

1. **Blade is presentation only.** No `DB::`, no `Model::query()`, no
   `->where()`, no filesystem calls, no business rules.
2. **Blade receives prepared data.** If a view needs something, the controller
   loads it.
3. **Watch loops for N+1.** Accessing `$row->relation` inside `@foreach` requires
   the controller to have eager-loaded it. Prefer `withCount()` for counts.
4. **Escape by default.** Use `{{ }}`. `{!! !!}` is only for content the CMS
   contract intentionally stores as trusted HTML (e.g. the footer map iframe), and
   never for user-submitted data.
5. **Preserve form mechanics** — `@csrf`, `@method`, exact field names, `old()`
   fallbacks, `@error` + `is-invalid` + `invalid-feedback`.
6. **Checkbox booleans need a hidden companion**, otherwise unchecking submits
   nothing:
   ```blade
   <input type="hidden" name="is_active" value="0">
   <input type="checkbox" name="is_active" value="1" @checked($model->is_active)>
   ```
7. **Page JS goes in `@push('scripts')`**, after the layout's own scripts. No
   inline framework code, no npm imports, no CDN.
8. **Bootstrap 4 syntax.** Use `data-toggle` / `data-target`, `form-group`,
   `custom-control custom-checkbox`, `badge badge-*`. Bootstrap 5 attributes
   (`data-bs-*`, `form-label`, `me-*`/`ms-*`, `g-*`) do not work here.

## Shared building blocks — reuse, do not reinvent

**Components** (`resources/views/components/admin/`)

`x-admin.btn` · `x-admin.badge` · `x-admin.group-btn` · `x-admin.translatable-field` ·
`x-admin.media-preview` · `x-admin.file-uploader` · `x-admin.image-uploader` ·
`x-admin.checkbox-group` · `x-admin.cms-section-card` · `x-admin.menu`

**Partials** (`resources/views/admin/`)

`layouts.page-header` · `partials.breadcrumbs` · `partials.filter-form` ·
`partials.results-summary` · `partials.pagination` · `partials.empty-state`

**Domain partials**

`admin/screens/partials/status-badge` (also used by Monitoring and Reports) ·
`admin/screens/partials/heartbeat` · `admin/ads/partials/status-badge` ·
`admin/ads/partials/creative-preview`

Status → variant mapping lives in those partials. Do not re-map a status inline.

## Pagination

Use `@include('admin.partials.pagination', ['data' => $paginator, 'variant' => 'static'])`.

When a page shows two paginated tables, each paginator keeps its own page name
(`logs_page`, `playbacks_page`). The partial appends
`request()->except($paginator->getPageName())`, so paging one table does not reset
the other. **Never replace a custom page name with `page`.**

## Localization and direction

- The dashboard direction follows the URL locale: `/ar/...` renders RTL.
- **Content direction is independent of dashboard direction.** English inputs are
  always `dir="ltr"`, Arabic inputs always `dir="rtl"` — `x-admin.translatable-field`
  handles this.
- Timestamps, codes, device UIDs and datetime inputs carry `dir="ltr"` so they read
  correctly inside an RTL page.
- Translation keys live in `resources/lang/{en,ar}/admin.php`. **Add every key to
  both files.** Use `\App\Support\Lang::t($key, $fallback)` when a value may be
  missing so an unknown enum still renders.

## Responsive

Wrap tables in `.table-responsive`. Use `col-md-*`/`col-lg-*` for filter fields and
cards. Long identifiers use `.admin-wrap-anywhere`. The shared breakpoints already
stack the page header, action groups and pagination on small screens.

## Prohibited

Do not wire **Vite, Tailwind, Alpine or any npm build dependency** into the
runtime. No `@vite(...)` in any layout or view, no `x-app-layout`/`x-guest-layout`,
no Alpine directives, no CDN script tags. Admin assets are served statically from
`public/admin-assets/`, and the application installs and runs with Composer alone
— production deployment requires no npm step.

## Scaffold files are not dead code

`package.json`, `vite.config.js`, `tailwind.config.js`, `postcss.config.js`,
`resources/js/` and `resources/css/` **are tracked in the repository and must stay
there.** They are standard Laravel project scaffold, kept as developer tooling
structure.

They have zero runtime consumers by design. That is not grounds for deleting them.
A previous cleanup pass removed them on exactly that reasoning and the project
owner reversed it — see [architecture.md](architecture.md#preserving-repository-structure).

Restoring these files does **not** mean the admin moves back to a build step. The
static architecture above remains authoritative; the scaffold simply sits unused.

Adding a build step to the runtime is an architecture decision requiring explicit
approval. So is removing the scaffold.
