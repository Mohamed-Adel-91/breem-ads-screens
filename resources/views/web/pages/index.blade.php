@extends('web.layouts.master')


<!-- SEO Meta -->
@push('meta')
    <!-- Page Title -->
    <title>بريم | الصفحة الرئيسيه</title>
    <!-- description -->
    <meta name="description" content="description" />
@endpush


@section('content')
    <!-- Main Content -->
    <main>
        @foreach ($sections as $section)
            @includeIf('web.pages.home.' . $section->type, ['section' => $section])
        @endforeach
    </main>
@endsection

@push('scripts-js')
@endpush
