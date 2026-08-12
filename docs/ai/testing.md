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

**There is no npm build and no asset compilation step in this project's workflow.**
Never tell anyone to run one, and never make a test or a deployment depend on one
— the application installs and runs with Composer alone.

`package.json` and the other Laravel scaffold files *are* tracked in the
repository (see [frontend-blade.md](frontend-blade.md#scaffold-files-are-not-dead-code)).
Their presence does not imply a build step, and their being unused is not a
reason to delete them. `node_modules` is not installed and is not needed.

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
6. **Assert the implementation property, not an environment-dependent number.**
   Peak memory and wall-clock timings vary by machine and PHP build, so a threshold on
   them is a flaky test dressed up as a performance guarantee. Assert the thing that
   actually holds: that the query count for 2000 rows equals the count for 20, that a
   chunked path was taken, that the stored payload is bounded by entity count rather
   than row count. See `ReportGenerationTest` and `PlaylistEligibilityTest`.

## Destructive operations

Retention pruning deletes rows irreversibly. Test it against the isolated test
database only — **never** run `model:prune` without `--pretend` against the development
database, and never against anything production-like unless explicitly asked.

`--pretend` reports counts and deletes nothing; use it to verify a retention change.

Managed uploads already land in a per-test temporary root (`Tests\TestCase` overrides
`media.upload_root`), so a full suite run must leave `git status --short` free of
generated media. Check it.

## Verification checklist

| You changed… | Also run |
|---|---|
| routes | `php artisan route:list` |
| any Blade view | `php artisan view:cache` then `view:clear` |
| a migration | `php artisan test` (migrations run per test) + a rollback/re-apply probe |
| an upload path | confirm `git status` shows no new files under `public/` |
| anything | `php artisan test` and `git diff --check` |
