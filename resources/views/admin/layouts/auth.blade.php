<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', __('admin.login.login_button')) | Breem</title>

    @include('admin.layouts.scripts.css')
    @stack('styles')
</head>
<body class="light breem-admin breem-auth {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <main class="auth-shell">
        @yield('content')
    </main>

    @include('admin.layouts.scripts.js')
    @stack('scripts')
</body>
</html>
