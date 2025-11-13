<!doctype html>
<html lang="en">
<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
		<title>Error! - 404</title>
		<link href="https://fonts.googleapis.com/css?family=Erica+One&amp;display=swap" rel="stylesheet">
        <link rel="icon" type="image/x-icon" href="{{asset('/assets/images/fav.png')}}">
        @include('admin.layouts.scripts.css')
	</head>
	<body class="authentication">
		<div id="particles-js"></div>
		<div class="countdown-bg"></div>
		<div class="error-screen">
			<h1>404</h1>
			<h5>نأسف!<br/>الصحة التي طلبتها غير متواجدة.</h5>
			<a href="{{ route('web.home') }}" class="btn btn-primary">الرجوع للصفحة الرئيسيه</a>
		</div>
        @include('admin.layouts.scripts.js')
        <script src="{{ asset('/assets/vendor/particles/particles.min.js') }}"></script>
        <script src="{{ asset('/assets/vendor/particles/particles-custom-error.js') }}"></script>
	</body>
</html>
