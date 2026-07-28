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

    @include('admin.layouts.next.scripts.css')
    @stack('styles')
</head>
<body class="vertical light breem-admin {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <a class="sr-only sr-only-focusable" href="#main-content">
        {{ __('admin.pages.dashboard.title') }}
    </a>

    <div class="wrapper">
        @include('admin.layouts.next.header')
        @include('admin.layouts.next.sidebar')

        <button type="button"
                class="sidebar-backdrop collapseSidebar"
                aria-label="{{ __('admin.pages.dashboard.title') }}"></button>

        <main id="main-content" role="main" class="main-content">
            @php
                $flashTypes = [
                    'success' => 'success',
                    'error' => 'danger',
                    'status' => 'info',
                ];
            @endphp

            @if (session()->has('success') || session()->has('error') || session()->has('status') || $errors->any())
                <div class="container-fluid breem-alerts" aria-live="polite">
                    @foreach ($flashTypes as $flashKey => $alertType)
                        @if (session()->has($flashKey))
                            @foreach ((array) session($flashKey) as $message)
                                <div class="alert alert-{{ $alertType }} alert-dismissible fade show"
                                     role="alert"
                                     data-auto-dismiss="{{ $flashKey === 'success' ? 'true' : 'false' }}">
                                    {{ $message }}
                                    <button type="button"
                                            class="close"
                                            data-dismiss="alert"
                                            aria-label="{{ __('admin.sweet_alert.cancel_button') }}">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endforeach
                        @endif
                    @endforeach

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button"
                                    class="close"
                                    data-dismiss="alert"
                                    aria-label="{{ __('admin.sweet_alert.cancel_button') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
            @endif

            @yield('content')
        </main>

        @include('admin.layouts.next.footer')
    </div>

    @include('admin.layouts.next.scripts.js')
    @stack('scripts')
</body>
</html>
