# 1. Executive Summary

The repository contains substantial Laravel code for the public website, CMS, administrator management, places, screens, advertisements, schedules, monitoring, logs, and a proposed device API. However, the current repository is not launch-ready.

The most important conclusion is:

> Considerable code exists, but several critical functional chains are broken at routing, authentication, asset-building, scheduling, notification, or deployment level. No production functionality was verified.

Audit scope note:

- Requested path: `E:\breem-ads-screens`
- That path does not exist in the connected environment.
- Audited source of truth: `C:\Users\EXPRESS\Documents\Mohamed-Nouh\AQ\Breem\breem-ads-screens`
- Git HEAD: `46bd41f2f453337d607e626a2718d78b7124da2d`
- Commit date: 2026-01-09
- Branch/worktree: `main`, clean before and after audit
- Tracked files: 1,159
- No files were changed.

Critical findings:

1. The device API is written in [routes/api.php](C:/Users/EXPRESS/Documents/Mohamed-Nouh/AQ/Breem/breem-ads-screens/routes/api.php), but runtime inspection showed zero `/api/*` routes. The custom routing callback in [bootstrap/app.php](C:/Users/EXPRESS/Documents/Mohamed-Nouh/AQ/Breem/breem-ads-screens/bootstrap/app.php:14) prevents normal API and health-route registration.
2. Four operational web endpoints are publicly registered, including unauthenticated `/clear-cache`, which also runs `migrate --force`. This is a severe production security/integrity risk.
3. The current `.env` has no `APP_KEY`. Laravel session/encryption-dependent functionality cannot operate correctly in this state.
4. Major dashboard views require Vite through `@vite`, but `public/build/manifest.json` is absent. The deployment procedure excludes build assets and performs no frontend build.
5. Device authentication is not secure enough to expose: the returned bearer token is never validated, the shared HMAC has no persisted replay protection, and handshake can overwrite a screen’s device UID.
6. Background monitoring and expiry jobs are not effectively scheduled. The active Laravel 12 schedule references a nonexistent command.
7. Notification delivery is broken or unconfigured. Notification classes request a nonexistent `log` notification channel, mail templates are missing, and notification recipients are absent.
8. Authorization is inconsistent. CMS, SEO, settings, and lead-management operations are accessible to any authenticated administrator despite seeded permissions.
9. Media upload and replacement contain orphan-file and conditional data-loss risks.
10. Documentation substantially overstates the implementation.

Verification performed:

- Laravel booted safely under temporary process-only audit overrides.
- 102 non-vendor routes were registered.
- Registered API routes: 0.
- Registered health `/up` route: 0.
- Public operational routes: 4.
- PHP 8.3.30.
- Laravel 12.28.1.
- 256 PHP files passed `php -l`.
- 46 automated tests were discovered.
- Tests were not executed because several write files into repository directories and audit-only restrictions prohibit that.

# 2. Current Technical Architecture

## Backend

- Framework: Laravel 12.28.1
- Required PHP: `^8.2`
- Installed PHP used for audit: 8.3.30
- ORM: Eloquent
- Templates: Blade
- Database authorization: Spatie Laravel Permission 6.21
- Translated model fields: Spatie Translatable 6.11
- Activity audit: Spatie Activity Log 4.10
- API authentication dependency present: Laravel Sanctum 4.2, but not used for screen authentication
- Spreadsheet exports: Laravel Excel 3.1 / PhpSpreadsheet 1.30

Primary dependency evidence is in [composer.json](C:/Users/EXPRESS/Documents/Mohamed-Nouh/AQ/Breem/breem-ads-screens/composer.json).

## Frontend

The repository has two overlapping frontend approaches:

- Public site:
  - Blade
  - Bootstrap 5.2
  - jQuery
  - Swiper
  - SweetAlert
  - Bootstrap Multiselect
  - CSS/JS stored directly in `public/frontend` and `public/assets`
- Newer administrator screens:
  - Vite 7
  - Alpine.js 3
  - Tailwind configuration
  - Laravel Breeze-style `<x-app-layout>`

README references Vue.js, but Vue is not installed and no meaningful Vue application was found.

The mixed administrator architecture is operationally important: older pages use the bundled administrator theme, while ads, screens, schedules, monitoring, reports, and parts of places use the Vite-based layout. The latter cannot render from this checkout because the expected public Vite manifest is missing.

## Authentication

Three partially overlapping mechanisms exist:

1. Admin session guard:
   - Email/password login
   - `auth:admin`
   - Login throttle of 10 requests/minute
   - This is the principal administrator authentication path.
2. Public `web` user authentication:
   - Breeze dependencies, controllers, models, and stale tests exist.
   - All normal user-authentication routes are commented out.
   - Functionally unavailable.
3. Screen/device authentication:
   - Proposed HMAC request validation
   - Device UID identification
   - A bearer token is returned by handshake
   - The bearer token is not subsequently verified
   - API routes are not registered at runtime

Admin OTP login code is commented out, but the OTP verification route remains.

## Localization

Localization is one of the more developed areas:

- Locale-prefixed public/admin routes use optional `{lang?}` constrained to `en|ar`.
- `SetLocaleFromRequest` selects route, session, or configured locale.
- Arabic/English PHP translation resources are present.
- Carbon and number formatting are localized.
- RTL/LTR public layouts exist.
- CMS content uses JSON translated fields through Spatie Translatable.

Risks:

- Some public content remains hard-coded in Arabic or English.
- Page activation is not consistently enforced.
- Some translation JSON is manipulated incorrectly by the section-item toggle code.

## Admin Architecture

Administrator functionality is split between:

- `resources/views/admin/layouts/master.blade.php` and its existing compiled assets
- `resources/views/admin/layouts/app.blade.php`, which invokes `@vite`

There is no unified dashboard/component architecture. The dashboard landing page contains little operational information; monitoring is a separate module.

## Public Site Architecture

The public site uses three main page slugs:

- `home`
- `whoweare`
- `contact-us`

`PagesService` loads pages, active page sections, section items, menus, settings, and SEO information. Content is assembled through section-type-specific Blade partials.

Page and layout data are cached indefinitely and invalidated through model observers.

## API Architecture

API controllers, requests, resources, services, throttling, and HMAC validation exist in code. However, the API route file is not registered at runtime.

The intended prefix is `/api/v1`, with screen handshake, heartbeat, playlist, playback reporting, and configuration endpoints.

## Database

Configuration supports SQLite, MySQL/MariaDB, PostgreSQL, and SQL Server. The current local environment assumes:

- MySQL
- Database-backed sessions
- Database-backed queue
- Database-backed cache

That combination requires all support tables and a running database before normal web operation.

## Queue, Cache, and Scheduler

Current environment assumptions:

- Queue: database
- Cache: database
- Session: database
- Mail: log

The screen-health and expiring-ad jobs implement queued behavior, but scheduler registration is ineffective. A production queue worker would also be required.

## Media Storage

There are two inconsistent storage approaches:

- Ads and specialized CMS uploads move files directly into public directories such as `public/upload/ads` and `public/cms`.
- Generic section upload logic uses Laravel’s public storage disk.

No unified media library exists. `section_items.media_id` exists without a corresponding media table or foreign key.

# 3. Current Modules Inventory

| Module | Status | Evidence | Notes |
|---|---|---|---|
| Arabic/English localization | ✅ COMPLETE | Locale middleware, translated JSON, AR/EN language files, RTL/LTR layouts | Code-level completion; not production verified |
| Public Home page | 🟡 PARTIAL | Page/section service and Blade sections | Depends on seeded DB; some content hard-coded; page activation ignored |
| Who We Are page | 🟡 PARTIAL | Specialized sections and CMS editor | No generic page composition |
| Contact page | 🟡 PARTIAL | CMS sections and four form types | No lead workflow or email |
| Admin authentication | ⚠️ BROKEN / HIGH RISK | Admin login routes/controller exist | Current `APP_KEY` missing; OTP flows incomplete |
| Public user authentication | 🔴 MISSING | Breeze code/tests remain, routes commented | Not functionally available |
| Admin dashboard | 🟡 PARTIAL | Authenticated landing page exists | No meaningful operational summary |
| Roles and permissions | 🟡 PARTIAL | Spatie schema, middleware, seed roles | Enforcement gaps and broken generated routes |
| Administrator management | 🟡 PARTIAL | List/create/edit/delete | No show/search; delete permission is wrong |
| User management | ⚠️ BROKEN / HIGH RISK | List/create views and routes | Store reads nonexistent `full_name`; viewer can reach create/store |
| CMS page content | 🟡 PARTIAL | Specialized editors for three pages | No generic page/section/item creation or menu editor |
| SEO management | 🟡 PARTIAL | CRUD and public view composer | No permission enforcement; duplicate fallback metadata |
| Settings | 🟡 PARTIAL | Edit/update | Any authenticated admin can update |
| CRM/contact submissions | 🟡 PARTIAL | Validation, storage, administrator list | No status, assignment, follow-up, email, search, or workflow |
| Places | ⚠️ BROKEN / HIGH RISK | Backend CRUD, search/filter/pagination | Main UI depends on missing Vite manifest |
| Screens | ⚠️ BROKEN / HIGH RISK | Backend CRUD and details | Main UI assets missing; device lifecycle incomplete |
| Ads | ⚠️ BROKEN / HIGH RISK | CRUD/upload/assignment code | Main UI assets missing; approval workflow is not real |
| Scheduling | ⚠️ BROKEN / HIGH RISK | Schedule CRUD and playlist query | Conflict and fallback semantics are unsafe/ambiguous |
| Device API | ⚠️ BROKEN / HIGH RISK | Controllers/requests/resources exist | Zero API routes registered at runtime |
| Heartbeat | ⚠️ BROKEN / HIGH RISK | Heartbeat service and offline job | Endpoint unreachable; offline job not scheduled |
| Monitoring | ⚠️ BROKEN / HIGH RISK | List/details/acknowledgement | UI manifest absent; uptime calculation is misleading |
| Logs/export | 🟡 PARTIAL | Screen-log listing and export | No retention or full device diagnostic flow |
| Reports | ⚠️ BROKEN / HIGH RISK | Generate/show/download code | UI manifest absent; seeded report types conflict with controller |
| Emails/notifications | ⚠️ BROKEN / HIGH RISK | Classes/jobs exist | Missing templates, invalid channel, no configured recipients |
| Media library | 🔴 MISSING | Only direct upload helpers | No media entity, catalog, lifecycle, processing, or ownership |
| Deployment automation | ⚠️ BROKEN / HIGH RISK | Workflow-like file exists | Wrong location, missing asset build, risky web root and public migrations |

# 4. Database Inventory

No domain model uses soft deletes.

## Authentication and framework tables

| Table | Purpose and key structure |
|---|---|
| `admins` | Administrator identities. Unique `email`; name, mobile, password, profile picture, verified timestamp, remember token, timestamps. No status/soft delete. |
| `users` | Non-admin users/ad owners. Unique `email`; name, nickname, mobile, password, verified timestamp, remember token, timestamps. Public user auth is unavailable. |
| `password_reset_tokens` | Email-keyed reset tokens. |
| `sessions` | Database sessions. Optional indexed `user_id` and `admin_id`, IP, user agent, payload, last activity. No FK enforcement. |
| `admin_otps` | Admin OTP code and expiry. FK to admin with cascade. Multiple simultaneous OTPs are possible; expiry is not enforced by verification code. |
| `roles`, `permissions` | Spatie role/permission definitions, unique per name/guard. |
| `model_has_roles`, `model_has_permissions`, `role_has_permissions` | Spatie authorization pivots. |
| `cache`, `cache_locks` | Database cache support. |
| `jobs`, `job_batches`, `failed_jobs` | Database queue support. |

## Domain tables

| Table | Purpose | Important fields and relationships |
|---|---|---|
| `places` | Physical screen locations | Translated JSON `name`, `address`; type `club/cafe/mall/other`; timestamps. Has many screens. |
| `screens` | Managed display screens | FK `place_id`; unique `code`; nullable unique `device_uid`; status `online/offline/maintenance`; `last_heartbeat`; timestamps; indexed place/status. |
| `ads` | Advertisement creative records | Translated title/description; `file_path`; type `video/image/gif`; duration; status `pending/approved/rejected/active/expired`; creator/approver FKs to `users`; optional global start/end; timestamps. |
| `ad_screen` | Direct ad-to-screen assignment | FKs to ad/screen, `play_order`, timestamps, unique ad/screen pair. |
| `ad_schedules` | Scheduled time windows | FK ad and screen; start/end timestamps; `is_active`; indexed screen/time fields. |
| `screen_logs` | Screen state reports | FK screen; current ad code; status enum only `online/offline`; reported time and timestamps. |
| `playback_logs` | Device playback reporting | FK screen; nullable ad FK; played timestamp; duration; JSON extra; indexes. |
| `reports` | Generated report snapshots | Name, type, filter JSON, data JSON, optional generating admin, timestamps/indexes. |
| `brands` | Intended brand records | Translated name, logo, type. No model, controller, route, relationship, or active feature was found. |
| `activity_log` | Spatie activity audit | Subject/causer/event/properties/batch tracking. |
| `pages` | Public CMS pages | Unique slug, name, active flag, timestamps. The public query does not enforce `pages.is_active`. |
| `page_sections` | Ordered page content blocks | FK page cascade; type, order, active flag, translated `section_data`, timestamps. |
| `section_items` | Repeated content within sections | FK section cascade; order, translated `data`; nullable `media_id` without media table/FK; timestamps. No active column. |
| `settings` | Key/value site settings | Unique key and JSON value. |
| `seo_metas` | Route-name-based SEO metadata | Unique page/route name; translated title, description, keywords, OpenGraph fields; canonical URL. |
| `contact_submissions` | All public lead forms | Type, name, phone, email, payload, timestamps. No status, owner, notes, source lifecycle, or soft delete. |
| `menus` | Menu containers | Location and active flag. |
| `menu_items` | Hierarchical menu links | FK menu; self-referencing parent; order; translated label; URL, target, active flag. |

## Migration inconsistencies and legacy elements

- `brands` appears abandoned.
- `section_items.media_id` refers to no actual media entity.
- `Page::menu()` expects a `menu_id` that does not exist on `pages`.
- `screen_logs.status` excludes `maintenance`, while screen code, heartbeat validation, model enums, and factory data allow maintenance.
- MySQL can reject maintenance screen logs while SQLite tests may hide the issue.
- Seeder report types `performance` and `availability` differ from administrator report validation, which accepts `playback` and `screen-uptime`.
- No schema evolution addresses these inconsistencies.
- No soft-delete or archival strategy exists for business records.

# 5. Public Website Status

Public routes and presentation code exist for:

- Home
- Who We Are
- Contact Us
- Arabic and English
- Header/menu
- Footer/settings
- SEO lookup
- Four lead forms

The public pages are built from CMS sections:

- Home: banner, partners, about, statistics, where-we-are, CTA
- Who We Are: secondary banner, who-we-are content, portfolio/image content
- Contact: secondary banner, contact information, map, bottom banner, form sections

Problems:

- `pages.is_active` is not enforced by public queries.
- If a page has no active sections, it returns 404.
- Header/footer logos are hard-coded file paths.
- Footer address, phone, email, copyright, and designer text include hard-coded values.
- Sidebar social links use `href="#"` rather than CMS social settings.
- Some SEO fallback metadata is hard-coded.
- Pages add a generic description metadata entry in addition to SEO-managed metadata.
- Public operation was not verified against a seeded database.

Status: 🟡 PARTIAL.

# 6. CMS Status

## What exists

- Specialized editors for Home, Who We Are, and Contact.
- Translated section data for Arabic and English.
- Repeated section items.
- Image, video, PDF/brochure inputs in specialized editors.
- Page-section update, toggle, and delete operations.
- Section-item update, toggle, and delete operations.
- SEO metadata CRUD.
- Settings editing.
- Menu and menu-item models, seed data, public retrieval, and caching.
- Cache invalidation observers for pages, sections, items, menus, and settings.

## Missing or broken

- No generic page creation/deletion interface.
- No generic section creation interface.
- No section-item creation route.
- No menu or menu-item administration interface.
- The generic `PageController` is not routed as an operational CMS.
- `SectionItem` has no `is_active` database column.
- Its toggle implementation writes an `is_active` value into translated JSON incorrectly.
- Public item retrieval does not use that JSON value, so the toggle does not reliably change visibility.
- Deleting/replacing CMS items does not consistently remove media files.
- CMS routes do not enforce `cms.manage`.
- SEO routes require only an authenticated administrator.
- Settings likewise have no `settings.*` middleware.
- Much of the footer/sidebar remains hard-coded.

Status: 🟡 PARTIAL, with a broken section-item activation feature.

# 7. CRM / Leads Status

Four public lead types exist:

| Type | Intended scenario | Public validation/storage | Admin | Email/status |
|---|---|---|---|---|
| `ads` | Advertiser request | Yes | Paginated list/payload view | No |
| `screens` | Screen/location partner | Yes | Paginated list/payload view | No |
| `create` | Creative/video production | Yes | Paginated list/payload view | No |
| `faq` | General inquiry | Yes | Paginated list/payload view | No |

All types are stored in `contact_submissions`.

Missing:

- Lead status
- Assignment/owner
- Notes
- Follow-up history
- Search/filtering
- Dedicated show screen
- Reply handling
- Export
- Email acknowledgement
- Internal notification
- Spam/rate-limit workflow beyond normal web protections
- Soft deletion or archival

A delete route exists, but the delete form is commented out in the list view. The routes require only an authenticated administrator despite contact-submission permissions existing in the role seeder.

Status: 🟡 PARTIAL.

# 8. Screen Management Status

## Administrator lifecycle

Code exists to:

- Create a place.
- Create a screen under a place.
- Assign a unique screen code.
- Optionally set a unique device UID.
- Set status to online, offline, or maintenance.
- Edit and delete screens.
- Search/filter by status/place.
- View logs and playback information.
- Assign advertisements.

The screen CRUD backend is substantial, but its main views use the missing Vite manifest.

## Device lifecycle trace

| Lifecycle step | Current state |
|---|---|
| Admin creates screen | Code exists; UI currently at risk from missing Vite build |
| Device submits screen code/device UID | Handshake code exists |
| Device authenticates | Incomplete: global HMAC only; returned bearer token not validated |
| Device is permanently paired | Incomplete: handshake can overwrite `device_uid` |
| Device fetches ads | Playlist code exists, but route is unregistered |
| Device sends heartbeat | Service exists, but route is unregistered |
| Device reports playback | Service/table exist, but route is unregistered |
| Offline detection | Job exists but is not effectively scheduled |
| Monitoring dashboard | Code exists; Vite dependency missing |

No persisted device metadata exists for model, firmware, timezone, app version, capabilities, token issuance, token rotation, or revocation.

Status: ⚠️ BROKEN / HIGH RISK.

# 9. Advertisement Management Status

## Existing functionality

- Advertisement list, create, show, edit, update, and delete.
- Search/status/screen/date filtering and pagination.
- Arabic/English title and description.
- Image, GIF, and video records.
- Direct public upload.
- Optional duration.
- Optional global start/end dates.
- Status values:
  - pending
  - approved
  - rejected
  - active
  - expired
- Screen assignment through `ad_screen`.
- Per-screen `play_order`.
- Schedule management.
- Activity logging.
- Playlist serialization with public media URLs.

## Important limitations

- Status is directly editable; there is no dedicated approval command or transition policy.
- `ads.approve` permission is seeded but unused.
- An active ad does not require an approver.
- Creator and approver relate to normal `users`, not administrator accounts.
- Public user registration is disabled, and administrator user creation is broken.
- There is no advertiser/company/campaign domain model.
- No direct location targeting exists.
- No media transcoding, thumbnail generation, malware scanning, checksum, or resolution validation.
- No explicit upload size limit exists for ad media.
- File type is partly inferred from client filename extension.
- Main management views rely on the absent Vite build.
- Current demo media path points to a file that is not present.

## Advertisement lifecycle

Administrator creation → direct public upload → optional screen pivot assignment → optional schedule → active status → playlist service → API response.

This chain breaks at:

- Dashboard Vite assets
- Device API routing
- Weak device authentication
- Scheduling semantics

Status: ⚠️ BROKEN / HIGH RISK.

# 10. Scheduling Status

## Supported

- Ad-to-screen assignment
- Global ad start/end dates
- Per-screen schedule start/end timestamps
- Active/inactive schedule flag
- Screen pivot order
- Playlist caching
- Status/date eligibility
- Conflict lookup

## Not supported

- Direct ad-to-location assignment
- Campaign entity
- Daily time windows
- Recurrence
- Day-of-week rules
- Priority separate from play order
- Rotation quotas
- Playlist entities
- Frequency capping
- Robust overlapping-schedule resolution
- Timezone-specific scheduling
- Proof-of-play reconciliation

## Exact “what should this screen play now?” code

The primary decision implementation is `App\Services\Screen\AdSchedulerService`.

It:

1. Loads the screen and assigned ads.
2. Filters ads to `active`.
3. Applies global ad start/end dates.
4. Loads active schedules for that screen.
5. Sorts results by `ad_screen.play_order`.
6. Caches the generated playlist.
7. Uses configured fallback media if nothing is eligible.

Critical semantic issue:

An assigned active ad without a currently active schedule is retained as an unscheduled fallback item and can still play. Therefore a schedule does not necessarily restrict playback.

Conflict issue:

Creating or updating a schedule deactivates overlapping active schedules. This runs even when the newly submitted schedule is inactive, allowing an inactive record to switch off live schedules.

Cache timing:

A schedule boundary can remain stale until the configured playlist TTL expires—currently five minutes—unless another event explicitly invalidates it.

Status: ⚠️ BROKEN / HIGH RISK.

# 11. Screen Device API Status

The following endpoints are intended by code, but none is registered at runtime.

| Method | Intended URL | Authentication | Input | Output/purpose |
|---|---|---|---|---|
| POST | `/api/v1/screens/handshake` | HMAC, no `screen.auth` middleware | Timestamp, screen code, nested device UID; optional model/firmware/timezone | Pair screen, mark online, return screen/config and bearer token |
| POST | `/api/v1/screens/heartbeat` | HMAC plus screen middleware | Timestamp, device UID or code, status/current ad | Update last heartbeat and create screen log |
| GET | `/api/v1/screens/{screen}/playlist` | HMAC plus screen middleware | Route screen ID/code, signed request | ETag-aware active playlist and media URLs |
| POST | `/api/v1/playbacks` | HMAC plus screen middleware | Device identity and playback entries | Store proof-of-play rows |
| GET | `/api/v1/config` | HMAC plus screen middleware | Optional device UID/code | Return screen/global device configuration |

Runtime result: 0 `/api/*` routes.

Security issues if registration is repaired without further changes:

- Bearer tokens are accepted but not compared to a screen identity.
- Handshake returns the device UID itself as the bearer token.
- A bound playlist screen can be accepted without verifying token ownership.
- Handshake can replace an existing device UID.
- Global HMAC secret compromise would affect every device.
- Timestamp validation has no persisted nonce, so replay inside the accepted window is possible.
- Playback entries are not proven to belong to an ad assigned to that screen.
- A missing HMAC secret effectively disables signature assurance.

Documentation/header incompatibilities:

- Code expects `X-Screen-Signature`.
- CORS/documentation reference `X-Screens-Signature`.
- Generated Postman requests use `X-Device-UID`, while code uses `X-Screen-Uid`.
- Postman request bodies do not match current request validation.

A complete production screen flow does not currently exist.

# 12. Heartbeat / Monitoring Status

## Online behavior

A heartbeat handled by `HeartbeatService`:

- Finds a screen using device UID or screen code.
- Sets `last_heartbeat` using server time.
- Updates screen status, normally online.
- Creates a `screen_logs` row using the client-reported timestamp.

## Offline behavior

`CheckScreenHealthJob` selects currently online screens whose last heartbeat is older than twice the configured heartbeat interval. With the present configuration, that threshold is 120 seconds. It marks them offline and creates a log.

However:

- The job is scheduled only in the legacy `app/Console/Kernel.php`.
- That kernel is not the active Laravel 12 scheduling source.
- Active [routes/console.php](C:/Users/EXPRESS/Documents/Mohamed-Nouh/AQ/Breem/breem-ads-screens/routes/console.php:11) schedules `screens:check-status`.
- No such Artisan command exists.
- Therefore automatic offline transitions are not currently operational.

The documented `HEARTBEAT_OFFLINE_THRESHOLD` is not used by the job.

## Dashboard monitoring

Code exists for:

- Search/filter/pagination
- Screen details
- State logs
- Playback history
- Acknowledge action
- Uptime display

Problems:

- Views use missing Vite assets.
- Acknowledgement sets `last_heartbeat` to now even without an actual device heartbeat.
- Uptime is computed as online event count divided by total event count, not time online divided by monitored time.
- No retention/pruning.
- No scheduled operational alert chain.
- Maintenance logs can conflict with the database enum.

Status: ⚠️ BROKEN / HIGH RISK.

# 13. Notifications / Emails Status

## Mailables

| Class | Intended purpose | Current status |
|---|---|---|
| `OTPMail` | Administrator OTP/password change | Referenced, but template missing |
| `AccountApprovedMail` | Account approval | No active reference found |
| `AccountApprovedWithoutSystemsMail` | Approval variation | No active reference found |
| `PasswordResetMail` | Password reset | No active reference found |
| `ThanksMail` | Contact acknowledgement | No active reference; also expects nonexistent `Setting.email` |

No `resources/views/emails` templates exist for these mailables.

## Notifications

| Notification | Trigger code | Status |
|---|---|---|
| `ScreenOfflineNotification` | Screen-health job | Job unscheduled; invalid channel/config |
| `AdExpiringNotification` | Expiring-ad job | Job unscheduled; invalid channel/config |

Both notifications include `'log'` in `via()`. Laravel has no built-in notification `log` channel, and no custom channel was registered. Slack handling is attempted indirectly in `toArray()`, but the notification is not routed through a Slack channel.

No email or notification is sent for contact submissions.

Status: ⚠️ BROKEN / HIGH RISK.

# 14. Authentication / Roles / Permissions Status

## Guards and roles

- `admin` guard: administrator sessions
- `web` guard: users, but public auth routes disabled
- Screen middleware: custom device identification/HMAC
- Sanctum: installed but unused for the device flow

Seeded roles:

- `super-admin`: all permissions
- `admin`: most operational permissions except role/permission management
- `viewer`: permissions whose names end in `.view`
- A CMS-focused administrator receives direct CMS/contact permissions

No policies exist.

`AuthServiceProvider` contains a super-admin gate override, but it is not registered in `bootstrap/providers.php`. Its behavior therefore cannot be relied on.

## Authorization inconsistencies

- CMS routes require only `auth:admin`; `cms.manage` is not enforced.
- SEO CRUD requires only `auth:admin`.
- Settings edit/update require only `auth:admin`.
- Contact-submission list/delete require only `auth:admin`.
- User list/create/store are all guarded by `users.view`.
- A viewer with `users.view` can reach create/store routes.
- `UserController::store()` validates `name` but reads `full_name`; creation fails.
- Administrator destroy authorization uses `admins.edit` instead of `admins.delete`.
- Generated role and permission `show` routes point to controller actions that do not exist.
- The profile delete route points to a nonexistent `ProfileController::destroy()`.
- `Admin::isRole()` reads a nonexistent legacy `role` field rather than Spatie roles.
- OTP expiry is stored but not enforced.
- Pending new administrator passwords can be placed in an unencrypted database session.
- Current `APP_KEY` is empty.
- Administrator/CMS seed credentials are absent from `.env.example`; seeders can attempt to create administrators with null credentials.
- A fallback seeder can double-hash a password.

Status: ⚠️ BROKEN / HIGH RISK.

# 15. Media / Storage Status

## Media types found

- Public site images and videos
- Administrator profile images
- CMS images
- CMS video
- PDF brochure
- Advertisement images/GIF/video
- Logos
- Fallback image

The configured fallback image exists at `public/images/fallback.png`.

## Storage strategies

- Ads: direct move to `public/upload/ads`
- Specialized CMS: direct move to `public/cms/...`
- Generic CMS sections: Laravel public storage disk
- Existing theme assets: tracked directly in `public/assets` and `public/frontend`

## Validation

- Specialized CMS forms apply image/PDF/video size and MIME rules.
- Generic section upload allows a broad file input up to approximately 30 MB.
- Ad upload accepts enumerated image/video MIME types but has no explicit maximum size.
- Ad type inference uses the submitted filename extension in places.
- Optional FFprobe duration validation exists but is disabled in the current environment.

## Risks

- `public/storage` symlink does not exist.
- `public/upload` and `public/cms` are absent in the checkout and are expected to be created at runtime.
- Changing `FILESYSTEM_DISK` to S3 does not move direct public uploads to S3.
- Media URL generation can therefore disagree with actual upload location.
- During ad replacement, the new file is moved and the old file can be deleted before later FFprobe validation completes. A later validation failure can leave the DB pointing to a deleted old file and the new file orphaned.
- Failed creates can leave orphan media.
- CMS item removal does not consistently delete media.
- Generic section replacement does not clean up previous files.
- No ownership/reference counting exists.
- No private or signed advertisement delivery.
- No processing status, thumbnails, transcodes, checksum, or malware scanning.
- No central media table despite `media_id`.

Status: ⚠️ HIGH RISK.

# 16. Tests Status

## Static inventory

- Test files: 19
- Discoverable tests: 46
- PHP syntax failures: 0 across 256 checked PHP files
- Passing: not measured
- Failing: not measured
- Skipped: not measured

The suite was deliberately not executed because:

- CMS tests upload into `public/cms`.
- Laravel feature execution may compile/write views and runtime files.
- The user explicitly prohibited file creation/modification.
- Runtime database safety must remain guaranteed.

`php artisan test --list-tests` was used for discovery only with SQLite in-memory/process-level environment overrides.

## Coverage present

- CMS Home/Who/Contact edit/update
- Screen log export
- Playlist ETag invalidation
- API rate limiter
- Heartbeat service
- Playlist service
- Screen API service
- CORS behavior
- Translation Blade directive
- Legacy Breeze authentication/profile flows

## Stale tests

Many tests target routes that are commented out or no longer registered:

- `/login`
- registration
- email verification
- normal user password reset
- normal user profile
- account deletion

The root example test expects HTTP 200, while `/` currently redirects.

API tests target endpoints that are not runtime-registered.

## Major coverage gaps

- Administrator authorization boundaries
- Administrator CRUD
- User creation failure
- Places CRUD
- Screens CRUD
- Ad upload and deletion failures
- Schedule overlap behavior
- Inactive schedule conflict behavior
- Device handshake security
- Device token ownership
- Playback authorization
- Offline job scheduling
- Notifications/mail rendering
- Contact flows and permissions
- Media orphan/data-loss scenarios
- Reports end-to-end
- MySQL enum behavior

SQLite unit/feature tests will not reveal MySQL enum inconsistencies.

# 17. Documentation Status

Documentation found:

- README
- Android device API document
- API v1 document
- Media pipeline document
- QA checklist
- Postman collection and local/production environments
- English and Arabic Word/PDF “full story” documents
- Sequence diagrams/images

No current Swagger/OpenAPI specification was found.

Major mismatches:

- README says Vue.js; no Vue application exists.
- README describes a functional device API; no API routes are registered.
- Android documentation uses old endpoint paths.
- `api-v1.md` describes OAuth2, campaigns, partner integrations, analytics, and statuses not present in code.
  **Resolved in Phase 15:** moved to `docs/future/partner-api-v1-DRAFT.md` with a NOT IMPLEMENTED
  banner, so it can no longer be mistaken for the current API reference. The real one is
  `docs/android-device-api.md`.
- Media documentation claims filesystem-disk abstraction, S3 replication, transcode jobs, signed URLs, and purge commands that do not exist.
- QA checklist refers to media-library and processing capabilities that are absent.
- Postman signatures, headers, and payload formats do not match request validation.
- Production Postman URL is a placeholder.
- README scheduler instructions do not match the active scheduler.
- Deployment and storage assumptions do not match the repository.

Status: ⚠️ OUTDATED / UNRELIABLE.

# 18. Deployment / Production Readiness

Not ready for deployment.

## Environment blockers

- Current `APP_KEY` is empty.
- Current environment is `local`.
- `APP_DEBUG=true`.
- `APP_URL=http://localhost`.
- Admin seed credentials are absent.
- Queue/cache/session expect database services.
- Mail uses log transport.
- Notification recipients/Slack credentials are absent.
- App timezone is UTC; business scheduling timezone is undecided.

## Frontend blocker

- `public/build/manifest.json`: missing
- Root `build/manifest.json`: present, but not where Laravel Vite expects it
- `public/hot`: missing
- Deployment excludes both `build` and `public/build`
- Deployment does not run `npm install` or `npm run build`

## Workflow problems

The workflow file is at:

[github/workflows/main.yml](C:/Users/EXPRESS/Documents/Mohamed-Nouh/AQ/Breem/breem-ads-screens/github/workflows/main.yml)

GitHub Actions requires `.github/workflows/...`, so this workflow is not discoverable in its current location.

If moved unchanged, it would:

- FTP the framework into `/public_html`
- Exclude build assets
- Run `composer install`
- Run `migrate --force`
- Cache Laravel configuration/routes/views
- Perform no frontend build
- Configure no queue worker
- Configure no scheduler/cron
- Create no storage link

It also deploys the Laravel repository root as the likely web root. Although a root `index.php` and `.htaccess` are present, serving the framework root is a potential source/configuration exposure and needs server-level verification. A proper production document root should normally be the application’s `public` directory.

Failure after `artisan down` can leave the site in maintenance mode.

FTP is configured rather than a secured artifact/deployment strategy.

## Public operational endpoints

Registered without authentication:

- `GET /run-optimize/day{day_number}`
- `GET /run-migrate/day{day_number}`
- `GET /run-seeder/day{day_number}`
- `GET /clear-cache`

`/clear-cache` clears caches and runs a forced migration. This is a launch-blocking security/integrity issue.

## Other production assumptions

- No effective queue-worker process definition.
- No effective screen-health cron.
- No log rotation/retention design.
- No storage-link provisioning.
- No HTTPS enforcement.
- CORS origin is a placeholder.
- CORS header list does not match actual screen headers.
- Trusted proxy handling is not clearly registered.
- Normal `/up` health route is absent because of custom route loading.

# 19. Bugs / Technical Debt Found

## P0 — Launch blocker, security, or data-loss risk

1. Public unauthenticated cache/migration/seeder/optimization web routes.
2. Device API routes are not registered.
3. Current `APP_KEY` is empty.
4. Core administrator modules require a missing Vite manifest.
5. Deployment excludes frontend build artifacts and does not build them.
6. Device bearer token is returned but never authenticated.
7. Handshake can replace a device’s UID without secure re-pairing.
8. Shared HMAC request signing has no persisted nonce/replay prevention.
9. Framework root is intended to be served from `public_html`, creating potential source/config exposure.
10. Ad media replacement can delete the previous file before all validation succeeds.
11. `APP_DEBUG=true` would expose internals if the current environment were deployed.

## P1 — Required functional issue

1. Offline detection and ad-expiry jobs are not effectively scheduled.
2. Active schedule references nonexistent `screens:check-status`.
3. Device monitoring/playlist/playback flow is unreachable.
4. Notifications use a nonexistent `log` notification channel.
5. All Mailable templates are missing.
6. No notification recipient configuration is present.
7. CMS/SEO/settings/contact authorization is not enforced.
8. `users.view` grants access to user creation routes.
9. User creation reads undefined `full_name`.
10. Administrator deletion checks the edit permission.
11. Role/permission `show` routes call missing methods.
12. Profile destroy route calls a missing method.
13. `screen_logs` cannot reliably store maintenance status.
14. Section-item toggling corrupts or misuses translated JSON and does not control public visibility.
15. An inactive schedule can deactivate active overlapping schedules.
16. Attached active ads can play outside their schedule.
17. OTP expiry is not checked.
18. Pending password may be stored in an unencrypted database session.
19. Seeders require undocumented administrator environment variables and can fail.
20. Reports seeder and report validation use conflicting type names.
21. No real advertisement approval transition exists.

## P2 — Important improvement

1. No generic CMS page/section/item creation.
2. No menu administration.
3. Page-level activation ignored publicly.
4. Contact requests have no workflow/status.
5. Footer/sidebar/branding values remain hard-coded.
6. No media library or consistent storage abstraction.
7. No media cleanup/reference tracking.
8. No ad upload size limit.
9. No direct location targeting or campaign entity.
10. Playlist cache may remain stale at schedule boundaries.
11. Uptime is event-count-based rather than time-based.
12. No device metadata, token rotation, or revocation.
13. Playback reports are not checked against screen assignments.
14. No screen-log retention.
15. App scheduling timezone is not product-defined.
16. Normal user model/auth code remains despite disabled user authentication.
17. Admin layout architecture is split across two systems.

## P3 — Cleanup

Confirmed dead/legacy code:

- `brands` migration/table with no operational model/module.
- `ActivityLogController` without a route.
- Generic CMS `PageController` without an operational page route.
- Legacy `app/Console/Kernel.php` scheduling path.
- `config/oldcors.php`.
- `app/Http/Middleware/oldSetLocale.php`.
- Old CDN layout scripts.
- `public/frontend/assets/showreel_old.mp4`.
- Unreferenced account-approval, password-reset, and thanks mailables.

Suspicious or incomplete:

- `section_items.media_id`.
- `Page::menu()` relationship.
- `Admin::isRole()`.
- Installed Sanctum with no active token flow.
- Root `build` output rather than `public/build`.
- Extensive commented user-authentication logic.

Marker scan found only one project TODO: removal of the nonexistent scheduled screen command. The absence of TODO comments should not be interpreted as completion.

# 20. Historical Scope vs Current Implementation Matrix

| Feature | Exists? | Complete? | Evidence | Missing Work |
|---|---:|---:|---|---|
| Public Home | Yes | No | Routes, page service, Blade sections | Runtime/env verification, hard-coded content cleanup |
| About/Who We Are | Yes | No | Specialized CMS and public sections | Generic CMS and production verification |
| Contact | Yes | No | Page and forms | CRM workflow, email |
| Arabic/English | Yes | Mostly code-complete | Locale middleware, language files, translated JSON | Hard-coded copy and runtime QA |
| CMS-controlled content | Yes | Partial | Pages/sections/items/settings/SEO | Create flows, menu editor, correct toggles |
| Header/footer/menu/settings | Yes | Partial | Models/view composers | Several hard-coded values; no menu admin |
| SEO | Yes | Partial | SEO model/admin/public composer | Authorization and output cleanup |
| General inquiries | Yes | Partial | `faq` form | Status, notification, follow-up |
| Advertiser requests | Yes | Partial | `ads` form | Status, assignment, email |
| Screen/location partner requests | Yes | Partial | `screens` form | Status, assignment, email |
| Video-production requests | Yes | Partial | `create` form | Status, assignment, email |
| Admin authentication | Yes | Partial/broken current env | Admin login guard | APP_KEY, OTP/security completion |
| Roles/permissions | Yes | Partial | Spatie schema/seeder | Consistent enforcement |
| Locations | Yes | Partial | Places CRUD | Asset build/runtime validation |
| Screens | Yes | Partial | Screen CRUD/model | Secure registration and active API |
| Unique screen identifiers | Yes | Partial | Unique code/device UID | Secure binding/re-pairing |
| Screen activation/status | Yes | Partial | Status field/admin actions | Reliable device-derived state |
| Advertisement upload | Yes | Partial | Ad CRUD/FileService | Safe storage, size limits, processing |
| Images/videos/GIF | Yes | Partial | Type validation/playlist | Production media pipeline |
| Ad approval | Superficial | No | Status enum/approver field | Real transitions and permission action |
| Ad-to-screen assignment | Yes | Partial | Pivot/play order | Runtime API and semantics |
| Ad-to-location assignment | No | No | No relationship | Design and implement |
| Start/end scheduling | Yes | Partial | Global dates and schedules | Correct conflict/boundary behavior |
| Daily/recurring schedules | No | No | No schema/service | Design and implement if required |
| Playlist ordering | Yes | Partial | `play_order` | Playlist management and QA |
| Device registration | Code only | No | Handshake controller | Route registration and secure pairing |
| Device authentication | Code only | No | HMAC/middleware | Token ownership/rotation/replay protection |
| Fetch current ads | Code only | No | Scheduler/resource | Registered secure API |
| Media URLs | Yes in service | Partial | `MediaUrl`/playlist resource | Consistent storage and access strategy |
| Heartbeat | Code only | No | Heartbeat service/job | Registered endpoint and scheduler |
| Online/offline | Code only | No | Last heartbeat/offline job | Operational scheduling |
| Logs/history | Yes | Partial | Screen/playback logs | Reliable API, retention, diagnostics |
| Contact emails | No | No | No form-triggered mail | Templates, triggers, recipients |
| Operational notifications | Code only | No | Two notification classes | Valid channels, schedule, recipients |
| Production deployment | Attempted | No | Workflow-like file | Correct workflow, assets, web root, services |

# 21. EXACTLY WHAT IS COMPLETED

The following are complete only within the narrowly stated code boundary; none was production verified:

1. Laravel migration definitions exist for the identified framework, CMS, screen, ad, scheduling, logging, report, role, and lead tables.
2. Arabic/English route-locale selection, RTL/LTR selection, language files, and translated JSON model support are implemented.
3. Public controller/service/view mappings exist for the three fixed page slugs: Home, Who We Are, and Contact.
4. Four public contact form types have request validation and database persistence code.
5. Admin email/password login has guard authentication, throttling, and session regeneration code.
6. Spatie role/permission schema, seed definitions, and middleware aliases are present.
7. Backend place CRUD, validation, search, filter, and pagination code exists.
8. Backend screen CRUD, validation, unique code/device UID constraints, search, filter, and pagination code exists.
9. Backend advertisement CRUD, translated fields, direct upload, screen assignment, filtering, and pagination code exists.
10. Backend schedule create/update/delete code exists.
11. The playlist service can assemble assigned eligible ads, order them, cache them, and return configured fallback content.
12. The heartbeat service can update a screen and create a screen-log row when called directly.
13. Playback-log persistence code exists.
14. Screen log listing/export code exists.
15. CMS cache invalidation observers exist for page/layout content changes.
16. PHP syntax validation passed for all 256 checked PHP files.

# 22. EXACTLY WHAT IS PARTIALLY COMPLETED

1. Public website: pages and layouts exist, but DB seeding, environment, assets, and full CMS control were not verified; content remains hard-coded.
2. CMS: three specialized page editors exist, but there is no general page builder, section/item creation, or menu management.
3. CRM: forms store records, but no lead workflow, notifications, assignment, or status management exists.
4. Admin auth: password login exists, but the current key is missing and OTP/password-change behavior is unsafe/incomplete.
5. Roles/permissions: definitions exist, but multiple administrator operations ignore them.
6. Admin/user management: administrator CRUD mostly exists; user creation is broken and overly permissive.
7. Places: backend chain exists, but principal views depend on missing Vite output.
8. Screens: backend administration exists, but pairing, authentication, heartbeat, and device consumption do not form an operational chain.
9. Ads: upload/assignment/status fields exist, but approval, ownership, storage safety, and device delivery are incomplete.
10. Scheduling: date windows exist, but overlap handling, unscheduled fallback behavior, timezones, and cache boundaries are unresolved.
11. Device API: controllers, requests, resources, middleware, and services exist, but routes are not registered and security is inadequate.
12. Monitoring: views and calculations exist, but the UI assets, automatic offline processing, and uptime calculation are unreliable.
13. Reports: generation/download code exists, but UI assets and seeded report types are inconsistent.
14. Notifications: jobs and classes exist, but they cannot reliably run or deliver.
15. SEO/settings: CRUD exists, but authorization and public output consistency are incomplete.
16. Media: upload helpers exist, but storage, cleanup, processing, and access control are fragmented.
17. Deployment: a deployment script was attempted, but its location and behavior do not produce a working secure release.
18. Tests: 46 tests exist, but many are stale and coverage excludes critical functional chains.

# 23. EXACTLY WHAT IS NOT IMPLEMENTED

Within the historical intended scope, the following were not found as functioning features:

1. Registered production screen/device API.
2. Secure per-device token authentication, rotation, revocation, and re-pairing.
3. Persisted device model, firmware, version, timezone, and capability data.
4. Campaign management.
5. Direct advertisement-to-location targeting.
6. Daily or recurring scheduling rules.
7. Priority/frequency-cap/rotation campaign logic beyond simple play order.
8. Proper advertisement approval workflow.
9. Generic CMS page creation and deletion.
10. CMS section/item creation workflow.
11. Menu and menu-item administration.
12. Media library/database entity.
13. Media transcoding, thumbnail generation, S3 replication, signed delivery, or purge command.
14. CRM status, assignment, notes, follow-up, reply, or export workflow.
15. Automated emails for public contact/request submissions.
16. Operationally functioning offline and ad-expiry notifications.
17. Public user registration/login/profile/password flow.
18. Brand management despite the brands migration.
19. Production-ready deployment pipeline.
20. Production queue-worker/scheduler provisioning.
21. Reliable time-based screen uptime reporting.
22. Screen log retention/pruning.

# 24. Client Decisions Required

1. Is the audited C: repository the authoritative copy, or is a separate E: repository expected?
2. Will advertisers have user accounts, or will all ads be created by administrators?
3. Should `users` represent advertisers, customers, or internal operators?
4. What administrator roles are required in practice?
5. Which CMS capabilities should clients receive: fixed page editors or a flexible page builder?
6. Should menus, footer, contact information, social links, and branding be fully CMS-managed?
7. What lead statuses and ownership workflow are required?
8. Which contact-request types must trigger client/internal emails?
9. How should a physical screen initially pair and later re-pair?
10. Is one shared HMAC secret acceptable, or is a per-device credential required?
11. Should a screen code be reusable after pairing?
12. Should schedules strictly prohibit playback outside their windows?
13. Can an ad play without a schedule?
14. How should overlapping schedules be resolved: reject, prioritize, rotate, or replace?
15. Is direct location targeting required, or only individual-screen targeting?
16. What timezone governs campaign scheduling?
17. Are recurring/day-of-week schedules required?
18. Which media formats, maximum sizes, resolutions, and durations are permitted?
19. Must media be stored locally, on S3-compatible storage, or both?
20. Is video transcoding or Android compatibility normalization required?
21. What constitutes online/offline, and what heartbeat timeout is acceptable?
22. Who receives offline and expiring-ad alerts, and through email, Slack, SMS, or another channel?
23. What proof-of-play and operational reports are contractually required?
24. What is the intended hosting model: shared Hostinger, VPS, container, or managed cloud?
25. Must historical screen/playback logs be retained, and for how long?

# 25. Recommended Remaining Roadmap

## Phase 1 — Production Blockers

Objective: remove immediate security and launch failures.

Work:

- Remove/protect operational web routes.
- Restore normal Laravel route registration.
- Register API and health routes correctly.
- Provide a valid environment key and secure production defaults.
- Correct the Vite build/output/deployment path.
- Put the document root on `public`.
- Correct deployment workflow location and asset build.
- Fix permission bypasses and broken route actions.
- Fix conditional media replacement data loss.

Dependencies: confirmation of authoritative repository and hosting model.

Expected outcome: application can boot and administrator pages can render without exposing operational endpoints.

## Phase 2 — Core Missing Functionality

Objective: make admin, CMS, CRM, ads, and scheduling coherent.

Work:

- Repair user/admin management.
- Define advertiser ownership.
- Implement real ad approval transitions.
- Correct schedule semantics and overlap policy.
- Complete CMS/menu administration at the agreed flexibility level.
- Add CRM statuses, assignment, notes, and notifications.
- Align reports and seed data.

Dependencies: client decisions on users, approval, CRM, and schedules.

Expected outcome: administrator business workflows operate end-to-end.

## Phase 3 — Screen/Device Reliability

Objective: produce a secure production device flow.

Work:

- Design per-device credentials.
- Implement pairing/re-pairing and token rotation.
- Add replay protection.
- Validate screen ownership on all endpoints.
- Register/test handshake, config, playlist, heartbeat, and playback routes.
- Persist device metadata.
- Correct heartbeat/offline scheduling.
- Add device diagnostics and reliable uptime calculation.
- Define playlist cache-boundary invalidation.

Dependencies: device application contract and heartbeat/offline requirements.

Expected outcome: authenticated screens reliably receive ads and report state/playback.

## Phase 4 — Media, CMS, and CRM Completion

Objective: eliminate fragmented file/content handling.

Work:

- Select local or object-storage strategy.
- Introduce a media entity and reference tracking.
- Add limits, MIME inspection, dimensions/duration checks, and safe replacement transactions.
- Add cleanup and orphan management.
- Finish CMS-controlled layout content.
- Add required CRM email templates and triggers.

Dependencies: storage provider, allowed formats, CMS scope, notification wording.

Expected outcome: media and content can be managed safely without repository/public-directory drift.

## Phase 5 — Production Hardening

Objective: establish stable operations.

Work:

- Configure queue workers and scheduler.
- Add notification recipients/channels.
- Configure HTTPS/proxies/CORS.
- Add cache/session/queue database readiness checks.
- Add backups and log retention.
- Remove legacy/dead code.
- Update documentation and Postman/OpenAPI contract.
- Add monitoring for queue, scheduler, disk, and API health.

Dependencies: production infrastructure and operations contacts.

Expected outcome: reproducible and observable deployment.

## Phase 6 — QA and Launch

Objective: prove functional and production readiness.

Work:

- Reconcile stale tests.
- Add permission/security tests.
- Add MySQL integration tests.
- Test complete admin workflows.
- Test Android/device flows under clock drift, retries, offline periods, and cache boundaries.
- Test media failure/replacement cases.
- Test backup/restore and deployment rollback.
- Conduct Arabic/English content and responsive UI QA.
- Perform staging acceptance with real screens.

Dependencies: stable staging environment and representative device hardware.

Expected outcome: evidence-backed launch approval rather than code-presence assumptions.

# 26. Client-Friendly Progress Summary

Breem already contains a meaningful foundation: the Arabic/English website, fixed CMS editors, contact forms, administrator roles, locations, screens, ads, schedules, monitoring records, and reporting code have all been started.

The system is not yet ready to launch. The most important screen API is currently not active, key administrator pages are missing their compiled frontend assets, automated screen monitoring is not running, notification emails are incomplete, and several security and deployment risks must be resolved.

Before launch, the team must first secure and stabilize the application, then complete the physical-screen registration and playback flow, correct scheduling behavior, finish notification and CRM workflows, consolidate media storage, and perform full staging tests with real display devices.

The main client decisions needed concern advertiser accounts, screen-pairing security, schedule behavior, CMS flexibility, media/storage requirements, lead workflow, notification recipients, reporting requirements, and the final hosting platform.

No feature in this audit should be treated as production verified merely because its model, migration, controller, or view exists.

AUDIT COMPLETE — NO FILES MODIFIED.
