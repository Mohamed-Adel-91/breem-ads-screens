# Testing

## Real commands

```bash
php artisan test                                   # full suite
php artisan test tests/Feature/Admin/AdsAdminViewsTest.php   # one file
php artisan test --filter=schedules                # by name

composer test            # config:clear + artisan test
composer validate        # composer.json sanity
./vendor/bin/pint        # code style (laravel/pint is installed)

php artisan route:list
php artisan view:cache && php artisan view:clear
git diff --check
```

**There is no npm, no Vite build and no asset compilation step.** Never tell
anyone to run one.

There is no static analyser and no CI workflow wired to the suite — do not
reference tools that are not installed.

## Environment

`phpunit.xml` runs against **SQLite in memory** (`DB_CONNECTION=sqlite`,
`DB_DATABASE=:memory:`), array cache, array session, sync queue. Production is
MySQL — a migration must work on both.

`Tests\TestCase` redirects `media.upload_root` to a temporary directory per test
and deletes it in `tearDown()`, so the suite never writes into `public/`. Assert
uploads with `$this->uploadPath($relative)`, never `public_path($relative)`.

## Writing tests

- Cover both locales (`en`, `ar`) for anything user-facing.
- Assert **exact contracts**: field names, route names, HTTP verbs, `_method`,
  `enctype`, query-parameter names, paginator page names, stored enum values.
- Admin view tests assert the canonical layout and that no `@vite`, `/build/`,
  `x-data`, `x-show`, `x-cloak`, `x-transition`, `alpinejs` or `x-app-layout`
  marker appears in the output. Keep those guards.
- Use isolated fixtures. Never touch real screens, device UIDs, heartbeats or
  media.
- When a known defect is deliberately left unfixed, **pin it with a test** named
  `test_known_defect_...` so a future fix is a visible, intentional change.

## Honesty rules

1. **Never delete a test to make the suite green.** A test may only be removed
   when the feature it covers has been removed — say so explicitly and name it.
2. **Never weaken an assertion to make it pass.** Correcting a test that
   contradicts itself is fine; loosening one that catches a real defect is not.
3. **Always report exact counts** — passed, failed, assertions.
4. **Distinguish baseline from regression.** Measure the suite before you start,
   compare failure *sets* afterwards, and state that the set is unchanged.
5. **Name every failure you did not fix, with its reason.**

## Verification checklist

| You changed… | Also run |
|---|---|
| routes | `php artisan route:list` |
| any Blade view | `php artisan view:cache` then `view:clear` |
| a migration | `php artisan test` (migrations run per test) + a rollback/re-apply probe |
| an upload path | confirm `git status` shows no new files under `public/` |
| anything | `php artisan test` and `git diff --check` |
