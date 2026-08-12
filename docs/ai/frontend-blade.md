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

Do not reintroduce **Vite, Tailwind, Alpine or any npm build dependency**. They
were removed deliberately in Phase 8. Adding one back is an architecture decision
requiring explicit approval.
