<!-- Bootstrap 5 -->
<link rel="stylesheet" href="{{ asset('/assets/vendor/bootstrap/bootstrap.min.css') }}">

<!-- Main CSS -->
<link rel="stylesheet" href="{{ asset('/assets/fonts/style.css') }}">
@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('/assets/css/ar/main.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('/assets/css/en/main.css') }}">
@endif
@if (app('isRtl'))
    <link rel="stylesheet" href="{{ asset('/assets/css/rtl.css') }}">
@endif

<!-- Vendor CSS -->
<link rel="stylesheet" href="{{ asset('/assets/vendor/daterange/daterange.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/particles/particles.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/tempus-dominus/tempus-dominus.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/lightbox/css/lightbox.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/bootstrap-multiselect/bootstrap-multiselect.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/fontawesome/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/summernote/summernote-bs4.min.css') }}">
<link rel="stylesheet" href="{{ asset('/assets/vendor/input-tags/bootstrap-tagsinput.css') }}" />

<!--  Multiselect (z-index + RTL) -->
<style>
    .multiselect.btn {
        pointer-events: auto;
    }

    .multiselect-container.dropdown-menu {
        z-index: 2050;
        max-height: 220px;
        overflow-y: auto;
    }

    [dir="ltr"] .multiselect-container>li>a>label {
        text-align: left;
        padding-left: 1.75rem;
    }
</style>
