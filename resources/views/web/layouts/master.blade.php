<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <base href="{{ asset('frontend') }}/">
    <!-- Meta Data -->
    @include('web.layouts.meta.meta')
    <!-- Style links -->
    @include('web.layouts.scripts.css')
    @php
        $lang = ['lang' => app()->getLocale()];
        $currentLocale = app()->getLocale();
    @endphp
</head>

<body>
    <!-- ==================== Header ==================== -->
    @if (request()->routeIs('web.home'))
        @include('web.layouts.components.transparent-header')
    @else
        @include('web.layouts.components.solid-header')
    @endif
    @include('web.layouts.components.sidebar')
    <!-- ==================== Header ==================== -->

    <!-- ==================== Main ==================== -->
    @yield('content')
    <!-- ==================== Main ==================== -->

    <!-- ==================== Footer ==================== -->
    @include('web.layouts.components.footer')
    @include('web.layouts.components.copyright')
    <!-- ==================== Footer ==================== -->

    <!-- ===================== Start JS Files ===================== -->
    @include('web.layouts.scripts.js')
    <!-- ===================== Start JS Files ===================== -->
</body>


</html>
