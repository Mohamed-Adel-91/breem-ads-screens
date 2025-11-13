<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6/dist/css/tempus-dominus.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css"
    integrity="sha512-ZKX+BvQihRJPA8CROKBhDNvoc2aDMOdAlcm7TUQY+35XYtrd3yh95QOOhsPDQY9QnKE0Wqag9y38OIgEvb88cA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-tagsinput/0.7.1/bootstrap-tagsinput.css" />

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
