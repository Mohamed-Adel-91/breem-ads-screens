<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <base href="{{ asset('frontend') }}/">
    <!-- Meta Data -->
    @include('web.layouts.meta.meta')
    <!-- Style links -->
    @include('web.layouts.scripts.css')
    @php $lang = ['lang' => app()->getLocale()] @endphp
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

    <!-- SweetAlert for flash messages -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            const swal = @json(session('swal'));
            if (swal && swal.type && swal.text) {
                Swal.fire({
                    icon: swal.type,
                    title: swal.type === 'success' ? '{{ app()->getLocale() === 'ar' ? 'تم بنجاح' : 'Success' }}' : '{{ app()->getLocale() === 'ar' ? 'خطأ' : 'Error' }}',
                    text: swal.text,
                });
            }
            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: '{{ app()->getLocale() === 'ar' ? 'خطأ في التحقق' : 'Validation Error' }}',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                });
            @endif
        })();
    </script>
</body>


</html>
