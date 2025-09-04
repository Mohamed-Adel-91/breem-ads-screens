<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="ltr">

<head>
    <base href="{{ asset('frontend') }}/">
    <!-- Meta Data -->
    @include('web.layouts.meta.meta')
    <!-- Style links -->
    @include('web.layouts.scripts.css')
</head>

<body class="bg-white text-gray-900">
    <!-- ==================== Header ==================== -->
    @include('web.layouts.components.desktop-header')
    @include('web.layouts.components.mobile-header')
    @include('web.layouts.components.desktop-search-navbar')
    @include('web.layouts.components.desktop-left-sidebar')
    <!-- ==================== Header ==================== -->

    <!-- ==================== Main ==================== -->
    @yield('content')
    <!-- ==================== Main ==================== -->

    <!-- ==================== Footer ==================== -->
    @include('web.layouts.components.mobile-navbar')
    @include('web.layouts.components.mobile-left-sidebar')
    @include('web.layouts.components.footer')
    @include('web.layouts.components.copyright')
    <!-- ==================== Footer ==================== -->

    <!-- ===================== Start JS Files ===================== -->
    @include('web.layouts.scripts.js')
    <!-- ===================== Start JS Files ===================== -->
</body>


</html>
