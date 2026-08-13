# Breem — Ads & Screens

Digital-signage platform: a **single Laravel 12 monolith** serving a public Blade
website and a Blade admin dashboard, plus a device-facing API for screens.

There is no Node build step in the runtime. There is no SPA. There is no second
application.

---

## Non-negotiable rules

1. **Inspect before modifying.** Read the routes, controller, request, model,
   service and view involved before changing any of them.
2. **Reuse before creating.** Breem already has components, partials, Form
   Requests, Rules, Services and Support helpers. Find them first.
3. **Preserve scope.** Do what was asked. Do not opportunistically refactor
   adjacent code.
4. **Preserve unrelated behaviour.** A UI change must not alter business rules;
   a business change must not silently alter the UI.
5. **Code is the source of truth.** Not this file, not a previous report, not a
   comment. Verify against the repository.
6. **Never run destructive database commands** — `migrate:fresh`, `db:wipe`,
   `TRUNCATE`, re-seeding real content — unless explicitly instructed.
7. **Never hide a failing test.** Do not delete, skip or weaken a test to make
   the suite green. Deleting a test is only valid when the *feature* is gone.
8. **Never claim completion without verification.** Run the checks and report the
   real numbers, including failures.
9. **No speculative abstractions.** Do not introduce Actions/Managers/Handlers/
   Repositories for a single call site.
10. **Preserve contracts** — route names, URLs, API response envelopes, request
    field names, permission names, stored enum values — unless the task is
    explicitly about changing them.
11. **Digital-signage work requires reading [`docs/ai/digital-signage.md`](docs/ai/digital-signage.md) first.**
12. **Preserve standard Laravel repository structure** unless the project owner
    explicitly requests structural removal. Laravel scaffold, framework
    convention, developer tooling and repository-standard configuration files are
    retained even with **zero runtime consumers** — "unused" is not grounds for
    deletion. Application dead code is a separate category and may still be
    removed after proper tracing. See
    [`docs/ai/architecture.md`](docs/ai/architecture.md#preserving-repository-structure).

---

## Where to look

| Topic | Document |
|---|---|
| System shape, layering, when to add a Service | [`docs/ai/architecture.md`](docs/ai/architecture.md) |
| Laravel conventions, models, migrations, jobs | [`docs/ai/backend-laravel.md`](docs/ai/backend-laravel.md) |
| Blade, admin UI, static assets, RTL | [`docs/ai/frontend-blade.md`](docs/ai/frontend-blade.md) |
| Pages, sections, items, translated JSON, media | [`docs/ai/cms.md`](docs/ai/cms.md) |
| Places → Screens → Ads → Schedules → Playlist → Playback | [`docs/ai/digital-signage.md`](docs/ai/digital-signage.md) |
| N+1, eager loading, pagination, indexes, transactions | [`docs/ai/database-performance.md`](docs/ai/database-performance.md) |
| Validation, authorization, uploads, escaping, secrets | [`docs/ai/security.md`](docs/ai/security.md) |
| What to run and how to report it | [`docs/ai/testing.md`](docs/ai/testing.md) |
| The order of operations for any task | [`docs/ai/workflow.md`](docs/ai/workflow.md) |
| Scheduler, queue worker, retention, alert recipients, pruning | [`docs/operations-runbook.md`](docs/operations-runbook.md) |
| Every production env key, and what breaks without it | [`docs/production-env.md`](docs/production-env.md) |
| Deploy sequence, worker/scheduler supervision, backup, restore, rollback | [`docs/production-deployment.md`](docs/production-deployment.md) |
| Pre-launch sign-off gate | [`docs/production-launch-checklist.md`](docs/production-launch-checklist.md) |
| The device contract as implemented | [`docs/android-device-api.md`](docs/android-device-api.md) |
| Pairing and re-pairing a physical screen | [`docs/device-repairing-runbook.md`](docs/device-repairing-runbook.md) |

Agent entry point: [`AGENTS.md`](AGENTS.md).

---

## Quick facts

- **PHP** 8.2+ (developed on 8.3) · **Laravel** 12
- **Admin** — Blade + Bootstrap 4, assets served statically from
  `public/admin-assets/`. Canonical layout:
  `resources/views/admin/layouts/master.blade.php`. Typeface is **Thmanyah Sans**,
  self-hosted from `public/admin-assets/fonts/thmanyah/` — never from a CDN.
- **Public site** — Blade, assets in `public/frontend/`. That directory name is a
  runtime contract, not a preference: it is the layout's `<base href>` and the default
  prefix `media_path()` applies to stored media paths. Typeface is **Thmanyah Sans**,
  self-hosted from `public/frontend/fonts/thmanyah/` — the same family as the admin, in
  both Arabic and English, but its own runtime copies.
- **Persistent media** — `public/cms/` (CMS uploads) and `public/upload/` (ad
  creatives, served to devices). Database-addressed: never rename, never move.
- **Locales** — `en` and `ar`, carried in the URL as `{lang?}`; Arabic renders RTL.
- **Auth** — custom admin guard (`auth:admin`) with OTP; roles/permissions via
  spatie/laravel-permission.
- **The runtime uses no Node, Vite, Tailwind or Alpine.** Wiring any of them into
  the application is an architecture decision that needs explicit approval.
  The standard Laravel scaffold files (`package.json`, `vite.config.js`,
  `tailwind.config.js`, `postcss.config.js`, `resources/js/`, `resources/css/`)
  nonetheless stay in the repository — see rule 12.
