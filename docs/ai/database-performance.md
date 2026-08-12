# Database & performance

## N+1

The dominant risk in Breem is a relation accessed inside a Blade loop that the
controller did not load.

Check both sides before shipping a list view:

```php
// controller
$ads = Ad::query()->with(['screens.place', 'creator'])->withCount('schedules')->paginate(20);
```
```blade
{{-- view --}}
<td>{{ $ad->creator?->name }}</td>      {{-- eager-loaded ✔ --}}
<td>{{ $ad->schedules_count }}</td>     {{-- aggregate ✔ --}}
<td>{{ $ad->schedules->count() }}</td>  {{-- one query per row ✘ --}}
```

Use the cheapest tool that answers the question:

| Need | Use |
|---|---|
| the related records | `with()` |
| only how many | `withCount()` |
| only whether any | `withExists()` / `has()` |
| a derived value | aggregate subquery / `withSum()` |

Do not over-eager-load. Loading `screens` to render `screens_count` hydrates every
row for nothing — `withCount()` is the correct call.

**Verify with real numbers.** `DB::listen()` in a focused test is the honest way
to prove an N+1 is gone.

## Queries inside loops

Never issue a query per iteration. Load once before the loop and index in memory,
or express the whole thing as one query. `foreach` over a locale array or a config
list is fine; `foreach` over rows that each hit the database is not.

## Database vs memory

Filter, sort, group and aggregate **in SQL**. Pulling a table into a collection to
`->filter()` it does not scale.

Known offender, deliberately unchanged: `ReportController` loads every matching log
with `->get()` and groups in PHP.

## Pagination

Anything that grows with usage paginates — ads, screens, places, schedules, logs,
playbacks, reports, monitoring. Small fixed config lists (locales, statuses, place
types) do not.

When one page shows two paginated tables, give each its own page name and keep it:

```php
$screen->logs()->paginate(20, ['*'], 'logs_page');
$screen->playbacks()->paginate(20, ['*'], 'playbacks_page');
```

`admin.partials.pagination` appends `request()->except($paginator->getPageName())`
so the paginators stay independent. Never collapse a custom name to `page`.

## Large datasets

Streaming exports use `chunkById` / `chunkByIdDesc` — see `LogController`. Use
`chunk`, `chunkById`, `lazy`, `lazyById` or `cursor` whenever the row count is
unbounded. Do not `->get()` an unbounded query.

## Indexes

Existing indexes match the real query patterns:

| Table | Index |
|---|---|
| `screens` | `(place_id, status)`, unique `code`, unique `device_uid` |
| `ads` | `(status, start_date, end_date)` |
| `ad_schedules` | `(screen_id, start_time, end_time)` |
| `ad_screen` | unique `(ad_id, screen_id)` |
| `screen_logs` | `(screen_id, reported_at)` |
| `playback_logs` | `(screen_id, played_at)` |
| `reports` | `type`, `generated_by` |

**Do not add indexes speculatively.** Add one only when a real query pattern
clearly justifies it, in an additive migration, with the justification recorded.
Otherwise report it as a recommendation.

## Transactions

Wrap multi-step writes. Keep remote HTTP, mail and heavy file processing outside
the transaction.

The CMS media lifecycle is the reference implementation: content is committed
first, superseded files are deleted afterwards, and a failed save discards the new
upload instead of the old one.

## Race conditions

Let the database enforce integrity — `screens.code` and `screens.device_uid` are
unique, `ad_screen` has a composite unique key. Rely on those constraints rather
than a read-then-write check.

Areas worth care when they are next touched: device pairing claiming a
`device_uid`, concurrent schedule writes for the same screen, and concurrent
report generation. Do not redesign the product rules to "fix" a race — report it.
