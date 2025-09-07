<!-- CSRF Token -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Basic meta -->
<meta charset="UTF-8" />
<!-- Mobile Specific Meta -->
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<!-- IE Browser Support -->
<meta http-equiv="X-UA-Compatible" content="ie=edge" />
<!-- author -->
<meta name="author" content="Frame&Code" />
<!-- upper bar color for mobile -->
<meta content="#627E90" name="theme-color" />
<!-- Favicon -->
<link rel="shortcut icon" href="{{ asset('/favicon.ico') }}" type="image/x-icon" />
<!-- stack meta -->
@stack('meta')
