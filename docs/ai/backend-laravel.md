# Backend — Laravel

**Laravel 12.x · PHP 8.2+ (developed on 8.3) · MySQL in production · SQLite
in-memory for tests.**

Key packages: `spatie/laravel-permission`, `spatie/laravel-translatable`,
`spatie/laravel-activitylog`, `laravel/sanctum`, `maatwebsite/excel`,
`league/flysystem-aws-s3-v3`.

Bootstrapping is Laravel 11+ style: `bootstrap/app.php` registers routes,
middleware aliases and providers. There is no `Http/Kernel.php`.

## Conventions

- **Framework-native first.** Use Eloquent, Form Requests, Resources, policies/
  permissions, queues and the scheduler before writing custom machinery.
- **Thin controllers** — see [`architecture.md`](architecture.md).
- **Form Requests** for non-trivial validation; **Rules** for reusable constraints.
- **Models** hold persistence concerns: relationships, casts, scopes,
  accessors that do not query.
- **No hidden queries** in accessors or `$appends`. An accessor that lazy-loads a
  relation turns every list render into an N+1.
- **Route names and middleware are a contract.** Do not rename or drop them.
- **Localization is a contract.** Admin URLs are `/{lang}/admin-panel/...`,
  public URLs are `/{lang}/...`. Controllers take `string $lang` as their first
  parameter. Keep it.
- **API envelopes are a contract.** Device clients depend on the exact JSON shape
  produced by `app/Http/Resources/Api/**`.

## Enums and stored values

`ScreenStatus`, `AdStatus` and `PlaceType` are backed string enums cast on their
models. The **stored value** is the contract — `online`, `pending`, `cafe`, and so
on. Translate the *label* in the view; never rewrite the value.

If you add an enum case, check every column that stores it. `screen_logs.status`
once lagged `ScreenStatus` and caused a production 500; the alignment migration is
`2026_08_12_100000_add_maintenance_to_screen_logs_status.php`.

## Migrations

- **Additive and production-safe.** Never edit a migration that has already run —
  add a new one.
- Preserve existing rows. Do not rebuild or truncate a table to change a column.
- Be platform-aware: production is MySQL, tests are SQLite. Branch on
  `Schema::getConnection()->getDriverName()` when a raw `ALTER` is needed, and
  fall back to Laravel's native `change()` for the rest.
- Give `down()` a real, non-destructive path where practical.

### Absolute database safety

Never run against real data unless explicitly instructed:

```
php artisan migrate:fresh
php artisan db:wipe
TRUNCATE ...
php artisan db:seed        # re-seeding overwrites live CMS content
```

## Transactions

Wrap multi-step writes so a partial failure cannot leave inconsistent rows. Keep
slow work **outside** the transaction — HTTP calls, mail, large file processing.

The CMS content controllers already model this: content is written inside a
transaction, and the superseded media files are deleted only after it commits
(`FileService::commitReplacedFiles()` / `discardUploadedFiles()`).

## Observers

Only for meaningful lifecycle behaviour. Breem uses them for cache invalidation:
`AdObserver` and `AdScheduleObserver` flush the affected screens' playlist cache;
the CMS observers flush page caches. Do not add an observer for logic a caller
should own.

## Jobs and the scheduler

`app/Jobs` holds `CheckScreenHealthJob` and `CheckExpiringAdsJob`.
`routes/console.php` schedules `screens:check-status`. Add a job only when the
async lifecycle genuinely helps; a synchronous call in a controller is usually
correct.

## Activity log

Admin write paths record an activity entry (`activity()->performedOn(...)`).
Preserve those calls when refactoring a controller action.
