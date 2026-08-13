# Thmanyah type system — Breem admin runtime fonts

These are the **runtime** font files the admin actually loads. They are served publicly,
so only what a browser needs lives here.

The admin is Sans-first: it is an operations console read by scanning, so serif appears in
exactly two places — `.page-title` / `.dashboard-welcome h2` (Display) and
`.admin-page-header p` / `.admin-prose` (Text). Tables, filters, badges, pagination, the
sidebar and `.card-title` stay Sans.

| File | Family | CSS `font-weight` | Used by |
|---|---|---:|---|
| `thmanyah-sans-light.woff2` | Sans | 300 | vendor theme rules |
| `thmanyah-sans-regular.woff2` | Sans | 400 | the admin body default |
| `thmanyah-sans-medium.woff2` | Sans | 500 | sidebar, table headers, labels |
| `thmanyah-sans-bold.woff2` | Sans | 700 | emphasis, and 600 resolves here |
| `thmanyah-serif-display-bold.woff2` | Serif Display | 700 | page titles |
| `thmanyah-serif-text-regular.woff2` | Serif Text | 400 | descriptive copy |

Display ships Bold alone because nothing else uses the family. Text ships Regular alone
because admin prose is escaped Blade output built from translation strings — it cannot
contain `<strong>` or `<em>`, so no bold or italic face is ever requested.

The admin stylesheets declare weights 300, 400, 500, 600 and 700. There is **no 600
file**, and that is deliberate rather than an omission: CSS font matching resolves a
requested 600 upward to the real 700 face, so those rules render in genuine Bold instead
of a browser-synthesised fake. Adding a synthetic 600 `@font-face` would be worse, not
better.

Nothing in the admin asks for 800 or 900, so the **Black** weight is not shipped here —
it is preserved with the rest of the package outside the web root.

Declared in [`../../css/fonts.css`](../../css/fonts.css) as the family
**`Thmanyah Sans`**, and applied to the admin in
[`../../css/breem-admin.css`](../../css/breem-admin.css).

## Rules

- **Loaded locally. Never from a CDN** — not `font.thmanyah.com`, not Google Fonts. The
  application owns these files, and a remote font would add an external dependency, a
  privacy dependency, latency, a CSP allowance and an outage risk for nothing.
- **WOFF2 only.** The package also ships OTF; WOFF2 is roughly 3× smaller and is
  supported by every browser that can run this admin. Adding an OTF fallback would
  double the payload for no reachable user.
- **Do not modify the font binaries.**
- Do not add a weight here without a matching `@font-face` in `fonts.css` — an
  unreferenced file is dead weight in a public directory.

## Where the rest of the package went

The supplied archive also contained two serif families
(`thmanyahserifdisplay`, `thmanyahseriftext`), OTF versions of all three, and an 18.7 MB
Arabic design/aesthetics guide. None of that is loaded at runtime, and a design guide
should not be publicly downloadable, so the **complete original package is preserved
outside the web root** at:

```
resources/fonts/thmanyah/
```

Nothing was discarded except macOS archive artifacts (`__MACOSX/`, `.DS_Store`). If a
serif family or another weight is ever needed, take it from there, convert/copy the
WOFF2 in, and add the `@font-face`.

## Licence

`LICENSE.pdf` and `LICENSE-ar.pdf` are kept alongside the fonts deliberately — the
licence travels with the files it covers. Do not remove them as cleanup.
