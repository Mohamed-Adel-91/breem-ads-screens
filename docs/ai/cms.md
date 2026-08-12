# CMS

The CMS drives the **public website's** editable content. The database is the
source of truth: if a string or image is editable, it lives in the CMS, not
hardcoded in a Blade view.

## Data model

```
pages           id, slug (unique), name, is_active, timestamps
  └── page_sections   id, page_id, type, order, is_active, section_data (json), timestamps
        └── section_items   id, section_id, order, is_active, data (json), media_id, timestamps
```

- `page_sections.type` identifies the section within its page — `banner`,
  `partners`, `about`, `stats`, `where_us`, `cta`, `second_banner`, `map`,
  `contact_form_ads`, … A **type is only unique within a page**: `second_banner`
  exists on both *Who We Are* and *Contact Us*. Always scope by page:
  `PageSection::whereRelation('page', 'slug', 'contact-us')->where('type', ...)`.
- `section_data` and `data` are translated JSON: `{"en": {...}, "ar": {...}}`,
  cast through spatie/laravel-translatable.
- `order` controls render order for both sections and items.
- `is_active` exists on all three levels. `pages.is_active = false` makes the
  public route return a real **404**.

## Editors

| Editor | Route | Controller |
|---|---|---|
| Home | `admin.cms.home.edit/update` | `Cms\HomePageContentController` |
| Who We Are | `admin.cms.whoweare.edit/update` | `Cms\WhoWeArePageContentController` |
| Contact Us | `admin.cms.contact.edit/update` | `Cms\ContactUsPageContentController` |
| Generic sections | `admin.cms.pages.sections` | `Cms\PageController` |
| Section mutations | `admin.cms.sections.*` | `Cms\PageSectionController` |
| Item mutations | `admin.cms.items.*` | `Cms\SectionItemController` |

Shared behaviour — locale loops, translated-JSON merging, upload orchestration,
transaction handling — lives in `Cms\BasePageContentController`. Put new shared
behaviour there, not in a copy.

## Rules

1. **Never hardcode editable content** in a Blade view. Read it from the section.
2. **Never destroy content.** Updates merge into the existing translated JSON;
   they must not blank the other locale. Do not re-seed to "reset" a page.
3. **Preserve both locales.** Writing `en` must leave `ar` intact.
4. **Field direction follows the content language**, not the dashboard locale —
   use `x-admin.translatable-field`.
5. **Preserve `is_active` and `order`.** They are real behaviour, not decoration.
6. **Preserve stored media paths.** Paths are stored relative
   (`cms/home/cta/…`); URLs are produced by `App\Support\MediaUrl`, disk locations
   by `App\Support\UploadPath`. Never write an absolute path into the database.
7. **Cache invalidation is automatic** via `PageObserver`, `PageSectionObserver`
   and `SectionItemObserver`. If you add a content table, add its observer.

## Media replacement lifecycle

`FileService` implements a safe replacement sequence — reuse it rather than
calling `move()` yourself:

1. `uploadSingle()` stores the new file and records the superseded one as *pending*.
2. The database write happens inside a transaction.
3. On success → `commitReplacedFiles()` deletes the superseded file.
4. On failure → `discardUploadedFiles()` removes the new file and keeps the old one.

A replaced file is only deleted when it actually lives in the destination folder,
so seeded/shared assets (`img/…`, `frontend/…`, remote URLs) are never touched.

## Test isolation

`config('media.upload_root')` defaults to `public_path()`. `Tests\TestCase`
overrides it with a temporary directory per test and deletes it afterwards, so the
suite never writes into `public/cms`. Assert with `$this->uploadPath($storedPath)`,
never `public_path($storedPath)`. `UploadIsolationTest` guards this.

## Known gaps — do not claim these exist

- **No Menu CMS admin UI.** `menus` / `menu_items` tables, models and observers
  exist; there is no admin screen to manage them.
- **No generic section creation.** Sections can be edited, toggled, reordered and
  deleted, but not created from the UI — they come from seeders.
- **No generic item creation** outside the curated Home / Who We Are editors.
- **No page activation toggle.** `pages.is_active` is enforced publicly but has no
  admin control.
- **Historical orphan media.** Files superseded before the safe-replacement
  lifecycle existed are still on disk and are not referenced by any record.
