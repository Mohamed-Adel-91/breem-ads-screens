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
        <!-- banner Section -->
        @include('web.pages.home.banner')

        <!-- Slider -->
        @include('web.pages.home.slider')

        <!-- knowmore Section -->
        @include('web.pages.home.knowmore')

        <!-- media Section -->
        @include('web.pages.home.media')

        <!-- where_us Section -->
        @include('web.pages.home.where_us')

        <!-- your_ads Section -->
        @include('web.pages.home.your_ads')
    </main>
@endsection

@push('scripts-js')
@endpush
