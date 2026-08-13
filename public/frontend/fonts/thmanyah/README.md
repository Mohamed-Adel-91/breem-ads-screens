# Thmanyah type system — Breem public website runtime fonts

The **runtime** font files the public site loads. Served publicly, so only what a browser
needs lives here.

Three families, three jobs. `../css/fonts.css` declares them; `../css/master.css` maps
them onto the semantic tokens `--breem-font-display`, `--breem-font-text` and
`--breem-font-ui`. Selectors use the token, never a family name.

| File | Family | `font-weight` | Used by |
|---|---|---:|---|
| `thmanyah-serif-display-regular.woff2` | Serif Display | 400 | family base |
| `thmanyah-serif-display-bold.woff2` | Serif Display | 700 | every `h1`–`h3` section heading |
| `thmanyah-serif-text-regular.woff2` | Serif Text | 400 | **the `body` default** — prose, CMS copy |
| `thmanyah-serif-text-medium.woff2` | Serif Text | 500 | the map block's copy |
| `thmanyah-serif-text-bold.woff2` | Serif Text | 700 | `<strong>` inside CMS prose |
| `thmanyah-sans-regular.woff2` | Sans | 400 | buttons, CTA links |
| `thmanyah-sans-medium.woff2` | Sans | 500 | form labels, statistic figures |
| `thmanyah-sans-bold.woff2` | Sans | 700 | navigation and footer links |

**Preloaded — three, one per family:** Serif Text 400 (the body default), Serif Display 700
(the first heading), Sans 700 (the navigation, above the fold on every page). The other
five load on discovery. The homepage hero is a silent video with no text, which is why
Display is not the first face to paint there.

Not shipped, because nothing asks for them: any **Light (300)** — no public rule requests
weight 300 — and any **Black (900)**.

## Rules

- **Local only.** Never `font.thmanyah.com`, Google Fonts, gstatic or any CDN.
- **This directory belongs to the public site.** Do not point the site at
  `admin-assets/fonts/`, or vice versa. Shared faces are byte-identical, and a test
  asserts that, but each surface serves its own copies.
- **WOFF2 only.** The package also ships OTF; WOFF2 is ~3× smaller and universally
  supported here.
- **Do not modify the font binaries.**
- Do not add a file without adding its `@font-face` in `../css/fonts.css` — an
  unreferenced file is dead weight in a public directory. And do not fake a weight the
  family does not have: `master.css` asks for 600 once, and CSS matching resolves that to
  the real Bold 700.

## Where the rest of the package is

The complete original archive — all three families, OTF and WOFF2, both licence PDFs and
the Arabic design guide — is preserved **outside the web root** at:

```text
resources/fonts/thmanyah/
```

Take any new weight or family from there. `LICENSE.pdf` is kept here deliberately: the
licence travels with the files it covers.
