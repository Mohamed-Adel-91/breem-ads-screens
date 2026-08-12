# Workflow

The order below is not optional. Most defects in this repository came from
skipping step 4.

## 1. Inspect

Read the actual code for the feature: route → middleware → Form Request →
controller → service → model → view/Resource. Do not work from a summary, a
previous report or a comment.

## 2. Search

Before writing anything, grep for what already exists: a component, a partial, a
Form Request, a Rule, a Support helper, a Service method, a translation key.
Breem has a lot of shared machinery — see
[`frontend-blade.md`](frontend-blade.md) and [`architecture.md`](architecture.md).

## 3. Understand the current architecture

One Laravel monolith. Blade everywhere. Static admin assets. No build step.
If your plan requires a bundler, a framework or a new abstraction layer, stop and
ask.

## 4. Trace the source of truth

Find the one place that owns the rule you are about to touch — see the source-of-
truth table in [`architecture.md`](architecture.md). If your change would create a
second owner, redesign it.

## 5. Define scope

Write down what is in and what is out. Adjacent problems get **documented**, not
fixed, unless the task says otherwise.

## 6. Reuse before creating

Extend the existing partial before adding a new one. Add a method to the existing
service before creating a new class.

## 7. Implement the minimum safe change

Smallest diff that fully does the job. No opportunistic refactors, no drive-by
renames, no reformatting untouched lines.

## 8. Review the diff

Read every hunk. Look for accidental behaviour changes, deleted lines you did not
intend, stray debug code, changed contracts.

## 9. Review database and performance

New query in a loop? Relation accessed in a Blade loop without eager loading?
Unbounded `->get()`? Missing pagination? See
[`database-performance.md`](database-performance.md).

## 10. Review security

Validation present? Route middleware matches the UI gate? Output escaped? Upload
validated? Secret leaked? See [`security.md`](security.md).

## 11. Verify

Run the commands in [`testing.md`](testing.md) that apply.

## 12. Report exact results

Real numbers, named failures, explicit statements about what you did **not** do.
Never claim completion you have not verified.

---

## Digital-signage tasks

Read [`digital-signage.md`](digital-signage.md) first, then trace every layer that
your change reaches:

```
Admin UI → Form Request → DB column/enum → Service → cache → Device API envelope
        → Device → Heartbeat / Playback → Monitoring/Reports → tests
```

Changing one layer alone is how `screen_logs.status` ended up one enum case behind
`ScreenStatus` and produced a live 500.
