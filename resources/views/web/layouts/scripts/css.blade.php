<!-- Favicon-->
{{--
    asset(), not a relative href. The layout sets <base href=".../frontend/">, so the
    previous relative "img/global/logo.ico" resolved to /frontend/img/global/logo.ico —
    a directory that does not exist, so the public site served a 404 for its favicon and
    overrode the working absolute one that meta.blade.php had already declared.
--}}
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />

{{--
    Thmanyah Sans — the public site's typeface, served from this repository.

    asset(), not a relative href. Every other stylesheet below resolves through the
    layout's <base href=".../frontend/">, but the typeface is not something to make
    depend on that: if the base tag ever moves or changes, a broken component stylesheet
    is obvious and a silently un-registered @font-face is not.

    Loaded FIRST so the @font-face rules are registered before any stylesheet that asks
    for the family, and preloaded so the two weights this site actually uses — Regular
    for body copy and Bold for navigation and headings — are already in flight rather
    than being discovered after master.css parses.

    No CDN. See frontend/fonts/thmanyah/ and docs/ai/frontend-blade.md.
--}}
<link rel="preload" href="{{ asset('frontend/fonts/thmanyah/thmanyah-sans-regular.woff2') }}"
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
