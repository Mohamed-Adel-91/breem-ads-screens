<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <base href="{{ asset('frontend') }}/">
    <!-- Meta Data -->
    @include('web.layouts.meta.meta')
    <!-- Style links -->
    @include('web.layouts.scripts.css')
</head>

<body>
    <!-- ==================== Header ==================== -->
    @include('web.layouts.components.header')
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
