<link rel="icon" href="{{ asset('admin-assets/images/favicon.ico') }}" type="image/x-icon">

{{--
    Locally hosted Thmanyah Sans. First, so the @font-face declarations are registered
    before any rule that asks for the family, and preloaded so the two weights every page
    uses are already in flight while the 300 KB theme stylesheet downloads.

    No CDN. These files are in this repository — see admin-assets/fonts/thmanyah/README.md.
--}}
<link rel="preload" href="{{ asset('admin-assets/fonts/thmanyah/thmanyah-sans-regular.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('admin-assets/fonts/thmanyah/thmanyah-sans-medium.woff2') }}"
      as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="{{ asset('admin-assets/css/fonts.css') }}">

<link rel="stylesheet" href="{{ asset('admin-assets/css/simplebar.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/css/feather.css') }}">
<link rel="stylesheet" href="{{ asset('admin-assets/css/app-light.css') }}" id="lightTheme">

@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('admin-assets/css/app-rtl.css') }}">
@endif

<link rel="stylesheet" href="{{ asset('admin-assets/css/breem-admin.css') }}">
