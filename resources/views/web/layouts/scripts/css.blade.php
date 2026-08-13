<!-- Favicon-->
{{--
    asset(), not a relative href. The layout sets <base href=".../frontend/">, so the
    previous relative "img/global/logo.ico" resolved to /frontend/img/global/logo.ico —
    a directory that does not exist, so the public site served a 404 for its favicon and
    overrode the working absolute one that meta.blade.php had already declared.
--}}
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}" />
<!-- Bootstrap v4 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/css/bootstrap-multiselect.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
<!-- Fontawesome -->
<link rel="stylesheet" href="css/all.min.css" />
<!-- animate -->
<link rel="stylesheet" href="css/animate.css" />
<!-- Main Style -->
<link rel="stylesheet" href="css/master.css" />
<!-- Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Scripts Stack CSS Style -->
@stack('scripts-css')
