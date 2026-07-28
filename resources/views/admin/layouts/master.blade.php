<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', $pageName ?? __('admin.pages.dashboard.title')) | Breem</title>

    @include('admin.layouts.scripts.css')
    @stack('styles')
</head>
<body class="vertical light breem-admin {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <a class="sr-only sr-only-focusable" href="#main-content">
        {{ __('admin.pages.dashboard.title') }}
    </a>

    <div class="wrapper">
        @include('admin.layouts.header')
        @include('admin.layouts.sidebar')

        <button type="button"
                class="sidebar-backdrop collapseSidebar"
                aria-label="{{ __('admin.pages.dashboard.title') }}"></button>

        <main id="main-content" role="main" class="main-content">
            @include('admin.layouts.alerts', ['containerClass' => 'container-fluid'])

            @yield('content')
        </main>

        @include('admin.layouts.footer')
    </div>

    @include('admin.layouts.scripts.js')
    @stack('scripts')
</body>
</html>
