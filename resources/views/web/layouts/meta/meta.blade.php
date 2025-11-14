<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Basic meta -->
<meta charset="UTF-8" />
<!-- Mobile Specific Meta -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<!-- IE Browser Support -->
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<!-- author -->
<meta name="author" content="NextSolve" />
<!-- upper bar color for mobile -->
<meta content="#627E90" name="theme-color" />
<!-- Favicon -->
<link rel="shortcut icon" href="{{ asset('/favicon.ico') }}" type="image/x-icon" />
<!-- stack meta -->
@if (isset($meta) && $meta)
    <!-- Standard Meta -->
    <meta id="meta-description" name="description"
        content="{{ $meta->getTranslation('description', $currentLocale) }}" />
    <meta id="meta-keywords" name="keywords" content="{{ $meta->getTranslation('keywords', $currentLocale) }}" />

    <!-- Open Graph Meta -->
    <meta property="og:title" content="{{ $meta->getTranslation('og_title', $currentLocale) }}" id="meta-og-title" />
    <meta property="og:description" content="{{ $meta->getTranslation('og_description', $currentLocale) }}"
        id="meta-og-description" />

    <!-- Title & Canonical -->
    <title id="page-title">{{ $meta->getTranslation('title', $currentLocale) }}</title>
    <link rel="canonical" href="{{ $meta->canonical ?? url()->current() }}">
@else
    <!-- Fallback Meta Tags -->
    <meta id="meta-description" name="description" content="Default Description" />
    <meta id="meta-keywords" name="keywords" content="Default Keywords" />
    <meta property="og:title" content="Default OG Title" id="meta-og-title" />
    <meta property="og:description" content="Default OG Description" id="meta-og-description" />
    <title id="page-title">بريم</title>
    <link rel="canonical" href="{{ url()->current() }}">
@endif

@stack('meta')
