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

Worked example, fixed in Phase 14: report generation used to run
`PlaybackLog::with(['ad','screen'])->get()` and `ScreenLog::with(['screen.place'])->get()`
and group the results in PHP — every log row in the period hydrated as a model, with its
relations, to produce a handful of totals. A week of a 50-screen fleet is ~500k
`screen_logs` rows.

`ReportGenerationService` now aggregates playback in SQL (`COUNT`, `SUM`, `GROUP BY`),
and `ReportGenerationTest` asserts the query count for 2000 log rows equals the count
for 20 — the property that matters, and one that stays stable across environments in a
way a memory figure would not.

The screen-uptime report is the exception that proves the rule: availability is a
**timeline** calculation over segments of online/offline/maintenance/unknown time, and
no `COUNT(*)` is equivalent to it. It therefore still reads log rows — but per screen,
chunked 100 screens at a time, delegating to the one authoritative
`ScreenAvailabilityService`. "Aggregate in SQL when equivalent" does not mean
"reimplement a duration measurement as a count".

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
| `screen_logs` | `(screen_id, reported_at)`, `reported_at` |
| `playback_logs` | `(screen_id, played_at)`, `played_at`, `(ad_id, played_at)` |
| `reports` | `type`, `generated_by`, `created_at` |

**Do not add indexes speculatively.** Add one only when a real query pattern
clearly justifies it, in an additive migration, with the justification recorded.
Otherwise report it as a recommendation.

The four single-column entries above were added in Phase 14, each for a query with no
usable index. The existing composites lead with `screen_id`, so a query that does not
filter by screen cannot use them at all:

| Index | Query it supports |
|---|---|
| `screen_logs.reported_at` | `ScreenLog::prunable()` — fleet-wide `reported_at < cutoff` |
| `playback_logs.played_at` | `PlaybackLog::prunable()` — fleet-wide `played_at < cutoff` |
| `playback_logs.(ad_id, played_at)` | the playback report's `where ad_id = ?` plus period filter, and its `group by ad_id`. `ad_id` had no index at all |
| `reports.created_at` | the reports index `latest('created_at')` with pagination, and `Report::prunable()` |

A scheduled fleet-wide prune with no supporting index is a nightly full table scan of
the two largest tables in the schema.

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
