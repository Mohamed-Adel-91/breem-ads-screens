# Public site → CMS binding audit

**Audited:** 14 August 2026 · branch `main` · commit `f54bfaf`
**Revised:** 14 August 2026, after the Site Settings UX pass (see §12)
**Test baseline at first audit:** 837 passed, 4 606 assertions, 0 failures

**Scope of the changes made alongside this document:** the footer's contact, location and
social data; then branding logos, the full social channel set and the Settings screen's
information architecture. Everything else here is *reported*, not changed.

> ### Two corrections to the first version of this document
>
> 1. **`site.lang_switch` is NOT orphaned.** It is listed in
>    `DeviceConfigService::ALLOWED_SETTING_KEYS`, so it is published to every paired screen
>    through `GET /api/v1/config` — a documented, tested part of the device contract. The
>    first pass checked the website for consumers and concluded there were none. It is an
>    ACTIVE setting and now has a labelled control under **Screen devices**.
> 2. **`site.phone` is also device-facing** for the same reason. Its shape is a contract,
>    not just a website detail.
>
> Both are the reason Part 1 of any settings task should grep the whole application rather
> than the public views.

---

## How to read this

Every visible thing on the public website is classified into one of six sources:

| Class | Meaning |
|---|---|
| **CMS** | A `pages` → `page_sections` → `section_items` record, edited under Website CMS |
| **SETTINGS** | A `settings` key/value row, edited under Site Settings |
| **DOMAIN** | A business table (`places`, `screens`, `ads`) |
| **TRANSLATION** | A `resources/lang/*` UI string — correctly not CMS |
| **DESIGN** | A colour, icon, spacing or decorative asset — correctly in code |
| **STATIC** | Business/editorial content still hardcoded — a CMS candidate |

Priorities: **P1** operators reasonably need to edit this · **P2** editorial content that
should probably become CMS-controlled · **P3** safe to keep in code.

A **BROKEN BINDING** is the important category: a control that already exists in the
admin, whose value the public site ignores. It is a bug, not a missing feature.

---

## 1. Executive summary

The public site is far more CMS-driven than expected. All three pages — Home, Who We Are,
Contact Us — render **entirely** from `page_sections` / `section_items`, including the
hero video, partner logos, statistics, location cards, brochure, CTA, and even the contact
form **field labels**. There is no hardcoded marketing copy left in any page template.

The gap was concentrated in the **layout**: the footer held the company's address, phone
number and email as literal text, and the map was stored as raw HTML that the template
echoed unescaped. That is what this task fixed.

What remains static after the change is a short, specific list — dominated by **two
orphaned logo settings** and a handful of SEO and translation defects.

### Headline findings

| # | Finding | Severity |
|---|---|---|
| 1 | Footer address / phone / email were hardcoded, Arabic-only | **Fixed** |
| 2 | `map.iframe` stored raw HTML rendered with `{!! !!}` — stored-XSS path | **Fixed** |
| 3 | `LayoutService` cached translatable values under **one** key — Arabic text leaked onto the English site | **Fixed** |
| 4 | Settings routes had **no permission gate** despite `settings.*` permissions existing and being granted | **Fixed** |
| 5 | Settings had **no sidebar entry** — reachable only by typing the URL | **Fixed** |
| 6 | The Settings edit form never rendered the typed fields its controller and Form Request already handled | **Fixed** |
| 7 | `social.links.twitter` was rendered by the footer and seeded, but not editable | **Fixed** |
| 8 | `header.logo` and `footer.logo` settings exist and are **ignored** by navbar and footer | **Fixed** (§12) |
| 9 | `sidebar.icons` holds four real social URLs; the floating sidebar renders four `href="#"` dead links | **Fixed** (§12) |
| 10 | Every page emits a second `<meta name="description" content="description">` | **Fixed** (§13) |
| 11 | `<meta name="author" content="NextSolve">` — stale, contradicts the footer credit | **Fixed** by the owner in `c5a65a7` |
| 12 | `site.lang_switch` — **first assessed as orphaned; that was wrong.** It is on the Device API allow-list | **Fixed** (§12) |
| 13 | 5 runtime CDN dependencies, 3 without SRI, 1 floating version | **P2** |
| 14 | Footer phone/email rendered in Bootstrap's default link blue on the dark panel | **Fixed** (§12) |
| 15 | Instagram was configurable and configured, but the footer had no slot for it | **Fixed** (§12) |
| 16 | TikTok, Snapchat and WhatsApp were not supported at all | **Fixed** (§12) |
| 17 | Clearing a social field did not clear it — `setTranslations()` merges, so the old URL survived | **Fixed** (§12) |
| 18 | Sidebar brand hover colours were keyed to `:nth-child()`, i.e. to icon position | **Fixed** (§12) |
| 19 | Navbar phone is still `href="#"` — not dialable | **Fixed** (§13) |
| 20 | The "Download brochure" button renders `href="#"` when no brochure file is configured | **P2** — newly found |
| 21 | No seeder creates `seo_metas`, so a fresh install serves the layout's hardcoded fallback description on every page | **P2** — newly found |

---

## 2. Footer binding matrix — before and after

| Field | Before source | Final source | Admin location | AR | EN | Validation |
|---|---|---|---|---|---|---|
| Address | Hardcoded Arabic literal in Blade | `settings.address` (translated JSON) | Site Settings → Business information | ✅ independent | ✅ independent | `string|max:255` |
| Email | Hardcoded `info@breem.com` | `settings.email` | Site Settings → Business information | ✅ | ✅ | `email`, re-checked on render |
| Phone | Hardcoded `۹۹٦٥٤۳۳٤+` (mixed Persian/Arabic digits, no link) | `settings.site.phone` | Site Settings → Business information | ✅ Arabic-Indic visible | ✅ Western visible | dial charset regex; `tel:` normalised to ASCII |
| Map / location | `settings.map.iframe` → echoed as raw HTML | `settings.map.iframe` → **URL extracted, re-validated, own element built** | Site Settings → Business information | ✅ | ✅ | `url:https` + `App\Rules\MapEmbedUrl` (host + `/maps/embed` path) |
| Facebook | `settings.social.links` (no empty-handling) | same, empty values dropped | Site Settings → Social media | ✅ | ✅ | `url:https|max:255` |
| X (Twitter) | `settings.social.links` — **not editable** | same, now editable | Site Settings → Social media | ✅ | ✅ | `url:https|max:255` |
| YouTube | `settings.social.links` | same | Site Settings → Social media | ✅ | ✅ | `url:https|max:255` |
| LinkedIn | `settings.social.links` | same | Site Settings → Social media | ✅ | ✅ | `url:https|max:255` |
| Instagram | not editable | editable, **not yet rendered** — no icon asset | Site Settings → Social media | ✅ | ✅ | `url:https|max:255` |
| TikTok | not supported | **not added** — no icon, no design slot | — | — | — | — |
| WhatsApp | not supported in footer | **not added** — `whats.png` exists but is used by the Contact page CMS section, not the footer | — | — | — | — |
| Footer navigation | `menus` / `menu_items` (location `footer`) | unchanged — already CMS | Not yet exposed in admin UI | ✅ | ✅ | — |
| Footer logo | hardcoded `img/whitelogo.png` | **unchanged** — see Finding 8 | — | — | — | — |
| Copyright / developer credit | hardcoded | **unchanged** — see §7 | — | — | — | — |

### Behaviour rules now enforced by tests

- An **empty** social URL is omitted entirely. The footer can no longer emit `href="#"`.
- An **unset** address, email, phone or map renders nothing. There is no fallback to the
  previously hardcoded values — a stale contact detail is worse than a missing one.
- A **malformed** email is not rendered as a dead `mailto:`.
- The visible Arabic phone uses Arabic-Indic digits; `tel:` is always ASCII with a leading
  `+`. The stored Arabic value is `99654334+` — a leading plus as an RTL editor lays it
  out — so the sign is re-emitted at the front rather than read positionally.
- Stored HTML in `map.iframe` can never reach the page as markup.

---

## 3. Dashboard CMS coverage matrix

| Public content area | Dashboard page | Editable today? | Missing capability |
|---|---|---|---|
| Footer address | Site Settings → Business information | ✅ **YES** | — |
| Footer email | Site Settings → Business information | ✅ **YES** | — |
| Footer phone (and navbar phone) | Site Settings → Business information | ✅ **YES** | — |
| Footer map | Site Settings → Business information | ✅ **YES** | — |
| Footer social links | Site Settings → Social media | ✅ **YES** | Instagram saves but has no footer icon |
| Footer navigation links | — | ⚠️ **DB only** | `menus`/`menu_items` have models, observers and a seeder but **no admin CRUD** |
| Footer logo | — | ❌ **NO** | `footer.logo` exists; footer ignores it |
| Navbar logo | — | ❌ **NO** | `header.logo` exists; navbar ignores it |
| Floating social sidebar | — | ❌ **NO** | `sidebar.icons` exists; sidebar renders `href="#"` |
| Copyright / developer credit | — | ❌ **NO** | Hardcoded; arguably should stay (see §7) |
| Home hero video | Website CMS → Home page | ✅ **YES** | — |
| Partner logos | Website CMS → Home page | ✅ **YES** | — |
| Know Breem title / desc / CTA | Website CMS → Home page | ✅ **YES** | — |
| Statistics numbers + labels + icons | Website CMS → Home page | ✅ **YES** | — |
| Locations heading + cards | Website CMS → Home page | ✅ **YES** | Cards are CMS images, **not** `places` — see §6 |
| Brochure button + file | Website CMS → Home page | ✅ **YES** | — |
| CTA heading / text / images / link | Website CMS → Home page | ✅ **YES** | — |
| Who We Are banner / heading / body / bullets / portfolio image | Website CMS → Who We Are | ✅ **YES** | — |
| Contact heading + subtitle | Website CMS → Contact Us | ✅ **YES** | — |
| Contact cards (4) incl. images and text | Website CMS → Contact Us | ✅ **YES** | — |
| Contact modal titles, **field labels**, submit text | Website CMS → Contact Us | ✅ **YES** | — |
| Contact map panel (address, phone, WhatsApp labels) | Website CMS → Contact Us | ✅ **YES** | Duplicates Settings — see §5 |
| Page title / description / keywords / OG | Website CMS → SEO Metas | ✅ **YES** | No canonical set; no hreflang; duplicate description tag |
| `<meta name="author">` | — | ❌ **NO** | Hardcoded `NextSolve` |
| Favicon | — | ❌ **NO** | Static `public/favicon.ico` — correctly static |

---

## 4. Remaining static content inventory

| Surface | Content | Current source | CMS managed | Recommended owner | Priority |
|---|---|---|---|---|---|
| **HEADER** | Logo image | `img/logo.png` in Blade | ❌ (setting exists, ignored) | Settings `header.logo` | **P1** |
| HEADER | Nav links | `menus`/`menu_items` | ✅ DB | Menu CMS (no admin UI yet) | P2 |
| HEADER | Phone | `settings.site.phone` | ✅ | Settings | — |
| HEADER | Phone `href` | `href="#"` — not dialable | ❌ | Should be `tel:` like the footer | **P1** |
| HEADER | Language switch label / flags | Blade + local SVG | ❌ by design | TRANSLATION + DESIGN | P3 |
| **SIDEBAR** | 4 social icons | Inline SVG, `href="#"` | ❌ (setting exists, ignored) | Settings `sidebar.icons` | **P1** |
| **HOME HERO** | Video + playback flags | CMS `banner` section | ✅ | — | — |
| **PARTNERS** | Logo images + alt | CMS `partners` items | ✅ | — | — |
| **KNOW BREEM** | Title, description, CTA text + link | CMS `about` section | ✅ | — | — |
| **STATISTICS** | Numbers, labels, icons | CMS `stats` items | ✅ | — | — |
| STATISTICS | Digit localisation | `localized_digits()` | ✅ presentation | — | — |
| **LOCATIONS** | Heading, card images, overlay text | CMS `where_us` section | ✅ | — | — |
| LOCATIONS | Relationship to `places` table | none — independent | ⚠️ | Decide intended contract | P2 |
| **BROCHURE** | Label, icon, file path | CMS `where_us.brochure` | ✅ | — | — |
| **CTA** | Heading, text, link, 2 images | CMS `cta` section | ✅ | — | — |
| **FOOTER** | Address, email, phone, map, social | Settings | ✅ **(this task)** | — | — |
| FOOTER | Logo | `img/whitelogo.png` in Blade | ❌ (setting exists, ignored) | Settings `footer.logo` | **P1** |
| FOOTER | Nav links | `menus`/`menu_items` | ✅ DB | Menu CMS (no admin UI) | P2 |
| FOOTER | Social icon artwork | `public/frontend/img/*-Icon.png` | ❌ by design | DESIGN | P3 |
| FOOTER | Map `title` attribute | `translate.layout.map_title` | ❌ by design | TRANSLATION | P3 |
| **COPYRIGHT** | "Designed and Developed by Angle Quotes" + link | Blade literal | ❌ | See §7 | P3 |
| COPYRIGHT | Year | **not rendered at all** | ❌ | See §7 | P2 |
| **WHO WE ARE** | Banner, heading, body, per-item title/text/bullets, portfolio image | CMS | ✅ | — | — |
| WHO WE ARE | Bullet icon `img/Vector.png` | Blade literal | ❌ by design | DESIGN | P3 |
| **CONTACT US** | Heading, subtitle | CMS `contact_us` | ✅ | — | — |
| CONTACT US | 4 cards: images, text | CMS `contact_form_*` | ✅ | — | — |
| CONTACT US | Modal titles, field labels, submit text | CMS `labels.*` | ✅ | — | — |
| CONTACT US | Map panel address / phone / WhatsApp labels | CMS `map` section | ✅ but duplicated | Reconcile with Settings — see §5 | P2 |
| CONTACT US | Icons `loc.png`, `phone.png`, `whats.png` | Blade literals | ❌ by design | DESIGN | P3 |
| CONTACT US | Close button glyph `X` | Blade literal | ❌ by design | DESIGN | P3 |
| CONTACT US | Inline `style="background:#41A8A6"` on submit buttons | Blade literal | ❌ | Move to `master.css` | P3 |
| CONTACT US | `console.error('bootstrap-multiselect لم يتم تحميله.')` | Blade literal, Arabic-only | ❌ | Developer message — fine, but hardcodes one language | P3 |
| **SEO** | Title, description, keywords, OG | `seo_metas` | ✅ | — | — |
| SEO | Duplicate `<meta name="description" content="description">` | `@push('meta')` in all 3 page views | ❌ **defect** | Delete the push | **P1** |
| SEO | `<meta name="author" content="NextSolve">` | Blade literal | ❌ | Settings or delete | P2 |
| SEO | `theme-color #627E90` | Blade literal | ❌ by design | DESIGN | P3 |
| SEO | Fallback `<title>بريم</title>` | Blade literal, Arabic-only | ❌ | Should fall back per locale | P3 |
| SEO | `canonical` | column exists, **NULL** on all 3 rows | ⚠️ | Populate or accept `url()->current()` | P3 |
| SEO | `hreflang` alternates | **absent** | ❌ | Add `<link rel="alternate">` for a bilingual site | P2 |
| **PAGINATION** | `web/layouts/partials/pagination.blade.php` — Arabic-only labels | Blade literals | ❌ | **Unused by any view** — dead partial | P3 |
| **JS** | SweetAlert titles via inline `locale === 'ar' ? … : …` | Blade ternaries | ❌ | Move to `translate.php` | P2 |

### Static image / video classification

| Asset | Class | Note |
|---|---|---|
| `img/logo.png`, `img/whitelogo.png` | **CONTENT** | Settings rows exist and are ignored — P1 |
| `img/*-Icon.png` (Facebook, LinkedIn, Twitter, Youtube) | **DESIGN** | Icon artwork keyed by platform name |
| `img/loc.png`, `img/phone.png`, `img/whats.png` | **DESIGN** | Contact panel glyphs |
| `img/Vector.png` | **DESIGN** | Who We Are bullet marker |
| `img/footer.png`, `img/background.png` | **DESIGN** | CSS background images |
| `img/flags/sa.svg`, `img/flags/us.svg` | **DESIGN** | Language switch, locally served — pinned by tests |
| `img/pc.png`, `img/pc2.png` | **FALLBACK** | Contact card defaults when CMS image is unset |
| `img/partener*.png`, `img/where3.png`, `img/screen*.png`, `img/tv*.png`, … | **LEGACY** | Not referenced by any active Blade — superseded by CMS uploads |
| `public/frontend/index.html`, `contact-us.html`, `whoweare.html` | **LEGACY** | Original static templates, not routed |
| Hero video | **CMS MEDIA** | `banner.video_path`, served through `media_path()` |

> **`public/frontend/js/main.js` is a 0-byte file** and is loaded on every public page.
> Harmless, but it is a wasted request. P3.

---

## 5. Duplicate contact data — a decision the owner needs to make

Contact details now exist in **two** places, and both are legitimately editable:

| Value | Settings (global) | Contact Us CMS `map` section |
|---|---|---|
| Address | `settings.address` → **footer** | `map.address` → **contact page panel** |
| Phone | `settings.site.phone` → **navbar + footer** | `map.phone_label` → contact page panel |
| WhatsApp | not in Settings | `map.whatsapp_label` → contact page panel |

This was **not** introduced by this task — the CMS `map` section predates it. It was left
alone deliberately: the contact page panel is editorial copy with its own layout and
labels, and collapsing it into Settings would change what that page says.

**Recommendation (P2):** decide whether the contact page panel should read from Settings.
If yes, that is a separate, small task. If no, document that the two are intentionally
independent so nobody "fixes" the divergence later.

---

## 6. Locations vs. the Places module

Homepage location cards come from CMS `where_us` items (an image plus overlay text). The
`places` table is a **separate domain module** driving screens and ads, with 2 rows.

They are unrelated today. Making the public cards read from `places` would change business
semantics — a place becomes publicly visible the moment it is created for operations,
which may not be intended.

**Reported, not changed.** The owner should confirm the intended public-location contract
before anyone wires these together. **P2.**

---

## 7. Copyright and developer credit

`resources/views/web/layouts/components/copyright.blade.php` renders:

> Designed and Developed by [Angle Quotes](https://www.anglequotes.com/)

Classification:

- **Developer credit** — agency attribution, not business content. Normal to keep in code.
  Reported separately as requested. **P3.**
- **Year** — there is **no year rendered at all**, so there is nothing stale to fix and
  nothing to CMS-ify. If a year is wanted, use `date('Y')`; do not make it a CMS field.
- **Note:** the credit says *Angle Quotes* while `<meta name="author">` says *NextSolve*.
  One of the two is wrong. **P2.**

---

## 8. External CDN runtime dependencies

| Dependency | CDN | Local copy exists? | SRI | Runtime critical? | Risk | Recommendation |
|---|---|---|---|---|---|---|
| Bootstrap 5.2.3 CSS | jsdelivr | ✅ `frontend/css/bootstrap.min.css` — but **v5.3.0**, and unused | ✅ | **YES** | **HIGH** | Self-host; reconcile the version drift |
| Bootstrap 5.2.3 JS bundle | jsdelivr | ❌ | ✅ | **YES** | **HIGH** | Modals and the navbar collapse stop working |
| jQuery 3.6.0 | cdnjs | ✅ `assets/js/jquery.min.js` (admin surface) | ❌ | **YES** | **HIGH** | Required by multiselect and the contact page script |
| bootstrap-multiselect 0.9.15 CSS + JS | cdnjs | ✅ `assets/vendor/bootstrap-multiselect/` | ❌ | Medium | **MEDIUM** | Local copy already exists — point at it |
| Swiper 11 CSS + JS | jsdelivr | ❌ | ❌ | **YES** | **HIGH** | Partners and Locations carousels stop rendering |
| SweetAlert2 `@11` | jsdelivr | ✅ `assets/vendor/sweetalert2/` | ❌ | Low | **MEDIUM** | **Floating major version** — a breaking release ships straight to production |

**What happens if the CDN is unavailable:** Bootstrap CSS is the worst case. Without it,
the four contact modals lose `display:none` and their markup renders inline on the page —
the failure mode observed in earlier QA. Swiper failing leaves the carousels as
unstyled stacked lists. jQuery failing breaks the multiselect on the contact page.

**Not changed in this task** (explicitly out of scope). Three local copies already exist,
so self-hosting is mostly a matter of changing the `href`/`src`. **P2.**

---

## 9. Query and N+1 audit

Measured on the development database, home page:

| State | Total queries | Settings queries |
|---|---|---|
| Cold cache (AR) | 25 | **1** |
| Cold cache (EN) | 23 | **1** |
| Warm cache (either) | 9 | **0** |

`LayoutService::getSettings()` previously issued **3** separate `Setting::key(...)->first()`
queries. It now issues **1** `whereIn` for all five keys while returning **more** fields —
a net reduction. A test pins this at exactly one query.

The 9 warm-cache queries are 1 session read, 7 cache reads and 1 session write. There are
**zero** content queries when warm. The 7 cache reads are because this environment uses
`CACHE_STORE=database`; on Redis or file they are not queries at all.

**Minor, pre-existing, not fixed:** the layout view composer is registered for three views,
so `getHeaderMenu()`, `getFooterMenu()` and `getSettings()` each run twice per request
(once for the header, once for the footer) — 6 cache reads where 3 would do. It is a
bounded constant, not an N+1, and it behaved identically before this task. It could be
memoised in the container exactly as the SEO composer already is. **P3.**

**Blade purity:** no public template contains `DB::`, `Model::` or `query()`. Pinned by
test for the footer.

---

## 10. Recommended future CMS work, in order

Items 1, 2 and the logo/sidebar bindings have since been done — see §12. The navbar phone
and the duplicate description are done too — see §13. **There are no P1 items left.** What
remains:

1. **P2** — Hide the "Download brochure" button when no brochure file is configured. It
   currently renders `href="#"` — the same dead-link pattern fixed for the social icons and
   the phone, but with a different cause: the CMS field is empty and the view falls back to
   `'#'`. Either hide the button or make the brochure required.
2. **P2** — Seed or configure `seo_metas`. No seeder creates the table, so a fresh
   installation serves the layout's hardcoded `Default Description` / `بريم` fallbacks on
   every page. The production database has three rows; a new environment has none.
3. **P2** — Decide the Settings-vs-CMS contact duplication (§5).
4. **P2** — Decide the Locations-vs-Places contract (§6).
5. **P2** — Self-host the CDN dependencies; pin SweetAlert2 to an exact version.
6. **P2** — Add `hreflang` alternates; fix or remove `<meta name="author">`.
7. **P2** — Build admin CRUD for `menus`/`menu_items`, which are DB-driven with no UI.
8. **P3** — Move SweetAlert titles into `translate.php`; delete the unused public
   pagination partial; drop the empty `main.js`.
9. **P3** — Per-surface social visibility toggles, if the owner ever wants the footer and
   the rail to differ by configuration rather than by the code-level subset they use now.
10. **P3** — An Instagram/Snapchat/WhatsApp slot in the floating rail, if the four-icon
    design is ever widened. The URLs are already configured; only
    `SocialPlatforms::SIDEBAR_PLATFORMS` and four CSS hover colours would change.

---

## 11. What was deliberately NOT done

No missing CMS module was built. Specifically **not** implemented: a Menu CMS admin UI, a
Partners module, a Brochure manager, a Statistics editor, a Hero-media manager, a
Locations/Places bridge, or any CDN self-hosting. Each is reported above for the owner to
approve individually.

---

## 12. Site Settings UX pass — final state of the four raw keys

### The keys

| Key | Before | Runtime consumer | Final status | Visible in admin? | Reason |
|---|---|---|---|---|---|
| `header.logo` | Raw text box holding `img/logo.png`; navbar ignored it and hardcoded the same path | **Public navbar** | **ACTIVE** | **Yes** — Branding → Header logo, file picker with preview | A real setting whose binding was broken. Operators change a logo by uploading one, never by typing a path. |
| `footer.logo` | Same, for `img/whitelogo.png` | **Public footer** | **ACTIVE** | **Yes** — Branding → Footer logo, file picker with preview | As above. |
| `sidebar.icons` | Raw text box holding a JSON array of social URLs + colours; the rail ignored it and rendered `href="#"` | **None** — the rail reads `social.links` now | **LEGACY / SUPERSEDED** | **No** | It was a second copy of the social URLs. One source is the point; a hidden duplicate is worse than none. The row is **not deleted** and is no longer seeded into fresh installs. |
| `site.lang_switch` | Raw text box showing `EN`, with no hint of what it did | **Device API** — `DeviceConfigService::ALLOWED_SETTING_KEYS`, published to every paired screen | **ACTIVE** | **Yes** — Screen devices → Language button label (AR/EN) | The first pass called this orphaned. It is not: it is part of the device contract. Its stored shape is unchanged for that reason. |

### The social channels

One store, `social.links`, read by both public surfaces through
`App\Support\SocialPlatforms`.

| Platform | Key | Admin editable | Footer | Floating rail | Empty hidden | Validation |
|---|---|---:|---:|---:|---:|---|
| Facebook | `facebook` | ✅ | ✅ | ✅ | ✅ | `url:https` |
| Instagram | `instagram` | ✅ | ✅ | — | ✅ | `url:https` |
| X | `x` (legacy `twitter` read as `x`) | ✅ | ✅ | ✅ | ✅ | `url:https` |
| LinkedIn | `linkedin` | ✅ | ✅ | — | ✅ | `url:https` |
| YouTube | `youtube` | ✅ | ✅ | ✅ | ✅ | `url:https` |
| TikTok | `tiktok` | ✅ | ✅ | ✅ | ✅ | `url:https` |
| Snapchat | `snapchat` | ✅ | ✅ | — | ✅ | `url:https` |
| WhatsApp | `whatsapp` | ✅ | ✅ | — | ✅ | `App\Rules\WhatsAppLink` — `wa.me/<number>`, `api.whatsapp.com`, `chat.whatsapp.com` |

**The canonical key for X is `x`.** Databases seeded before the rename hold `twitter`;
`SocialPlatforms::normalise()` maps it onto `x` on read, so nothing breaks and no URL is
lost. The Settings form rewrites it under `x` and drops the stale key the first time it is
saved. The canonical key always wins if both are present.

**Footer order is fixed by the registry**, not by JSON insertion order: Facebook,
Instagram, X, LinkedIn, YouTube, TikTok, Snapchat, WhatsApp.

**The rail draws a four-icon subset** (`SIDEBAR_PLATFORMS`) because it is a fixed design
element. That is a shape decision in code; the URLs are shared. The rail hides itself
entirely when none of its four are configured.

### The Settings screen

| Section | Field | Setting key | Control | Public consumer |
|---|---|---|---|---|
| Business information | Contact email | `email` | email input | Footer `mailto:` |
| Business information | Phone number | `site.phone` | text input, `dir="ltr"` | Footer `tel:` + navbar (**and screen devices**) |
| Business information | Address (Arabic) | `address.ar` | text input, RTL | Footer, `ar` |
| Business information | Address (English) | `address.en` | text input, LTR | Footer, `en` |
| Business information | Map location | `map.iframe` | URL input, validated embed link | Footer `<iframe src>` |
| Branding | Header logo | `header.logo` | image uploader + preview | Navbar `<img>` |
| Branding | Footer logo | `footer.logo` | image uploader + preview | Footer `<img>` |
| Social media | 8 channels | `social.links.*` | one URL input each | Footer + floating rail |
| Screen devices | Language button label AR/EN | `site.lang_switch` | text inputs | Device API `GET /api/v1/config` |
| Other settings | *(anything unrecognised)* | — | raw text/JSON, clearly labelled as such | — |

**No raw setting key is rendered anywhere on the page.** The "Other settings" card exists
for keys with no purpose-built control and does not render at all on a standard
installation, because every current key has one.

### Icons

All eight glyphs are **inline SVG**, single-path, `fill="currentColor"`, served from
`resources/views/components/web/social-icon.blade.php`. No icon font (the public site
deliberately loads none in its own markup — `WebTypographyAndAssetsTest` pins that), no
external icon CDN, no new binary assets. One `color` declaration themes them: white in the
footer, teal in the rail.

The four footer PNGs (`Facebook-Icon.png`, `LinkedIn-Icon.png`, `Twitter-Icon.png`,
`Youtube-Icon.png`) are now **unreferenced**. They are left in place rather than deleted.

### Two defects found while doing this

1. **Clearing a social field did not clear it.** The controller wrote settings with spatie's
   `setTranslations()`, which merges key by key — so a key absent from the new array
   survived from the old one. Emptying a social box left the URL in storage and the icon on
   the website. Now `replaceTranslations()`.
2. **The rail's brand hover colours were positional.** `master.css` keyed them with
   `:nth-child(1..4)`, which encodes "Facebook is always first". Driven by Settings that is
   no longer true — leaving Facebook blank handed its blue to whichever platform moved up.
   Now keyed by `.sidebar__item--<platform>`; the colours themselves are unchanged.

### One thing deliberately preserved

The rail's YouTube hover fills the glyph teal on a red background while the other three go
white. That looks like a copy-paste slip in the original CSS, but it is the current design
and changing it is a design decision, not a refactor. Preserved as-is and reported here.

### Deliberately not memoised

Resolving `$layoutSettings` once per request in the container was tried and reverted. It is
safe for the SEO composer because a route name cannot change within a request; it is not
safe here, because these values are locale-resolved (a shared container serves one
language's address to the other) and mutable (an admin save followed by a read returns the
stale copy). Both failure modes were caught by the existing tests. The cost of correctness
is three extra reads of an already-warm cache.

---

## 13. Final P1 cleanup — navbar phone and SEO description

### The navbar phone

| Locale | Stored | Visible navbar | `href` | Visible footer | Machine safe |
|---|---|---|---|---|---|
| EN | `+99654334` | `+99654334` | `tel:+99654334` | `+99654334` | ✅ ASCII |
| AR | `+99654334` | `+٩٩٦٥٤٣٣٤` | `tel:+99654334` | `+٩٩٦٥٤٣٣٤` | ✅ ASCII |
| AR, `+` typed at the visual end (`99654334+`) | `99654334+` | `٩٩٦٥٤٣٣٤+` | `tel:+99654334` | same | ✅ sign moved to the front |
| AR, Arabic-Indic input (`٩٦٦٥٠٠١١٢٢٣٣+`) | as typed | as typed | `tel:+966500112233` | same | ✅ mapped back to ASCII |
| Empty / unset | — | **element absent** | **no anchor at all** | absent | n/a |

**Root cause:** the value was never the problem. The navbar already received
`$layoutSettings` from the layout composer and already ran `localized_digits()` — only the
`href` was hardcoded to `#`, and the `<li>` rendered unconditionally. So the number in the
header was the one place on the site a visitor could read it and not call it.

**Fix:** consume `$layoutSettings['phone_link']`, the ASCII value
`LayoutService::telHref()` already produces for the footer. No second phone source, no
second normalisation, no new setting key. The whole `<li>` is conditional, and
`.site-navbar__meta`'s flex/gap rules already handle the language switch being the only
child at every width.

### The SEO description

| Route | Locale | Titles | Descriptions | Source | Placeholder |
|---|---|---:|---:|---|---|
| `/ar` | ar | 1 | **1** | `seo_metas.web.home` | no |
| `/en` | en | 1 | **1** | `seo_metas.web.home` | no |
| `/ar/whoweare` | ar | 1 | **1** | `seo_metas.web.whoweare` | no |
| `/en/whoweare` | en | 1 | **1** | `seo_metas.web.whoweare` | no |
| `/ar/contact-us` | ar | 1 | **1** | `seo_metas.web.contactUs` | no |
| `/en/contact-us` | en | 1 | **1** | `seo_metas.web.contactUs` | no |

Every route also renders exactly one `<title>` and one `<link rel="canonical">`.

**Root cause:** all three page views carried
`@push('meta')<meta name="description" content="description">@endpush` — the literal word
as the description — which the layout's `@stack('meta')` appended *after* the real tag from
`seo_metas`. Two descriptions per page, the second meaningless.

**Fix:** the three pushes are deleted. `@stack('meta')` stays as the extension point for a
page that genuinely needs an extra tag; a description is not that, because the layout owns
it.

**SEO caching is safe.** `AppServiceProvider` memoises the SEO lookup in the container
keyed by route name, but it stores the **row**, not resolved text — the description is
resolved per locale at render time. That is why it does not have the locale-leak the layout
settings cache once had, and it is now pinned by a test.

**Fallback, unchanged:** with no `seo_metas` row the layout's `@else` branch emits
`Default Description` and the title `بريم`. Still exactly one tag, still not the
placeholder. That the fallback is a single hardcoded pair for both languages is reported as
SEO debt (§10 item 2), not changed — choosing what an unconfigured page should say is a
content decision.

### A stale test that would have gone quiet

`WebResponsiveLayoutTest::test_arabic_numerals_are_still_localised` located the phone by
matching `<a class="nav-link" href="#">` and called `markTestSkipped()` when it did not
match. Fixing the href therefore turned six data sets **green-but-skipped** rather than
red — the numeral contract would have stopped being tested with nothing to show for it.
This was observed live: a suite run that overlapped the first edit reported
`926 passed, 6 skipped` instead of `932 passed`.

Both that test and `LocalizedNumeralsTest::test_the_arabic_phone_label_is_localized_but_never_a_dial_target`
now assert the real contract. The second was also **renamed** — its old name recorded the
defect as though it were the specification.

### hreflang

**ABSENT.** No `<link rel="alternate" hreflang="…">` is emitted; the single `hreflang`
attribute on the page belongs to the language-switch anchor, which is a different thing.
Informational only — not added, since nothing in the existing SEO architecture suggests it
was intended. Still listed as P2 in §10.
