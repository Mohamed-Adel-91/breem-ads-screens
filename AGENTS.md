# Agent instructions — Breem

Breem is one Laravel 12 monolith: Blade public site + Blade admin dashboard +
device API. No Node build in the runtime. Start with [`CLAUDE.md`](CLAUDE.md).

## Always read before writing code

- [`docs/ai/architecture.md`](docs/ai/architecture.md) — layering, where logic belongs
- [`docs/ai/database-performance.md`](docs/ai/database-performance.md) — N+1, eager loading, transactions
- [`docs/ai/security.md`](docs/ai/security.md) — validation, authorization, uploads, escaping
- [`docs/ai/testing.md`](docs/ai/testing.md) — real commands, honest reporting
- [`docs/ai/workflow.md`](docs/ai/workflow.md) — the order of operations

## Read additionally, by task

| If the task touches… | Also read |
|---|---|
| Controllers, models, migrations, jobs, requests | [`docs/ai/backend-laravel.md`](docs/ai/backend-laravel.md) |
| Any Blade view, admin UI, layout, component, CSS/JS | [`docs/ai/frontend-blade.md`](docs/ai/frontend-blade.md) |
| Pages, sections, items, website content, CMS media | [`docs/ai/cms.md`](docs/ai/cms.md) |
| Screens, Ads, Schedules, Playlist, Playback, Heartbeat, Monitoring, Device API | [`docs/ai/digital-signage.md`](docs/ai/digital-signage.md) — **mandatory** |
| Environment keys, config defaults, anything an operator must set | [`docs/production-env.md`](docs/production-env.md) |
| Deployment, migrations against live data, backups, queue/scheduler supervision | [`docs/production-deployment.md`](docs/production-deployment.md) |

## Hard limits

- Do not wire Node, Vite, Tailwind or Alpine into the runtime. The scaffold files
  for them stay tracked in the repository — do not delete them either.
- Do not delete standard Laravel scaffold, framework-convention, developer-tooling
  or repository-standard configuration files. For those, zero runtime consumers
  means **retain**, not remove.
- Do not run `migrate:fresh`, `db:wipe` or `TRUNCATE`, and do not re-seed real content.
- Do not delete or weaken a test to make the suite pass. A test may only be
  removed when the feature it covers has been removed.
- Do not change route names, URLs, API envelopes, request field names, permission
  names or stored enum values unless that is the task.
- Do not redesign device authentication, pairing, heartbeat, scheduling policy or
  playlist eligibility without explicit approval. Document defects instead.

## Before reporting done

Run what applies and quote the real output:

```
composer validate
php artisan route:list
php artisan view:cache && php artisan view:clear
php artisan test
git diff --check
```

State the pass/fail counts, name any failure you did not fix, and say why.
