<!-- Favicon-->
{{--
    asset(), not a relative href. The layout sets <base href=".../frontend/">, so the
    previous relative "img/global/logo.ico" resolved to /frontend/img/global/logo.ico —
    a directory that does not exist, so the public site served a 404 for its favicon and
    overrode the working absolute one that meta.blade.php had already declared.
--}}
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

{{--
    The Thmanyah type system — Serif Display for headings, Serif Text for prose,
    Sans for interface and numerals. All served from this repository.

    asset(), not a relative href. Every other stylesheet below resolves through the
    layout's <base href=".../frontend/">, but the typeface is not something to make
    depend on that: if the base tag ever moves or changes, a broken component stylesheet
    is obvious and a silently un-registered @font-face is not.

    fonts.css is loaded FIRST so the @font-face rules are registered before master.css
    asks for a family.

    PRELOADS — three files, one per family, chosen from what actually paints first:

      Serif Text 400     the `body` default, so it is the first face on every page
      Sans 700           the navigation links, above the fold on every page
      Serif Display 700  the first heading — above the fold on /whoweare and
                         /contact-us, just below it on the homepage, whose hero is a
                         silent video with no text at all

    Everything else loads on discovery: Display 400, Text 500/700, Sans 400/500. Five of
    eight files are lazy. Do not preload a face because it exists — measure where it
    paints. See public/frontend/css/fonts.css for the weight census this came from.

    No CDN. See docs/ai/frontend-blade.md.
--}}
<link rel="preload" href="{{ asset('frontend/fonts/thmanyah/thmanyah-serif-text-regular.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('frontend/fonts/thmanyah/thmanyah-serif-display-bold.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('frontend/fonts/thmanyah/thmanyah-sans-bold.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('frontend/css/fonts.css') }}" />

<!-- Bootstrap v4 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
<!-- Fontawesome -->
<link rel="stylesheet" href="css/all.min.css" />
<!-- animate -->
<link rel="stylesheet" href="css/animate.css" />
<!-- Main Style -->
<link rel="stylesheet" href="css/master.css" />
<!-- Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Scripts Stack CSS Style -->
@stack('scripts-css')
