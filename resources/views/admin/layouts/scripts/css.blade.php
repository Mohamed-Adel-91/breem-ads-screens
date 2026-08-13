<link rel="icon" href="{{ asset('admin-assets/images/favicon.ico') }}" type="image/x-icon">

{{--
    The Thmanyah type system, locally hosted. Loaded first so the @font-face declarations
    are registered before any rule that asks for a family, and preloaded so the faces
    every admin page paints immediately are already in flight while the 300 KB theme
    stylesheet downloads.

    PRELOADS — three files, chosen from what is above the fold on every admin screen:

      Sans 400           the body default
      Sans 500           sidebar items, table headers and form labels
      Serif Display 700  the .page-title <h1>, which page-header.blade.php renders on
                         every admin page

    Sans 300 and Serif Text 400 load on discovery — Light is used only by a few vendor
    theme rules, and the reading face only appears where there is descriptive copy.

    No CDN. These files are in this repository — see admin-assets/fonts/thmanyah/README.md.
--}}
<link rel="preload" href="{{ asset('admin-assets/fonts/thmanyah/thmanyah-sans-regular.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('admin-assets/fonts/thmanyah/thmanyah-sans-medium.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('admin-assets/fonts/thmanyah/thmanyah-serif-display-bold.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('admin-assets/css/fonts.css') }}">

<link rel="stylesheet" href="{{ asset('admin-assets/css/simplebar.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/css/feather.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/css/app-light.css') }}" id="lightTheme">

@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/app-rtl.css') }}">
@endif

<link rel="stylesheet" href="{{ asset('admin-assets/css/breem-admin.css') }}">
