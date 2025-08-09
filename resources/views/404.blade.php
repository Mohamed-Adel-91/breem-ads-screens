<!doctype html>
<html lang="en">
<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="shortcut icon" href="{{ asset('assets/img/fav.png') }}" />
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
			<h5>We're sorry!<br/>The page you have requested cannot be found.</h5>
			{{-- <a href="{{ route('admin.index') }}" class="btn btn-primary">Go back to Dashboard</a> --}}
		</div>
        {{-- @include('admin.layouts.scripts.js') --}}
	</body>
</html>
