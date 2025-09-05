@extends('web.layouts.master')

@push('meta')
    <!-- SEO Meta -->
    <!-- Basic Page Needs -->
    <meta charset="UTF-8" />
    <!-- description -->
    <meta name="description" content="description" />
    <!-- author -->
    <meta name="author" content="Icon Creations" />
    <!-- Mobile Specific Meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- IE Browser Support -->
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <!-- upper bar color for mobile -->
    <meta content="#627E90" name="theme-color" />

    <!-- Page Title -->
    <title>بريم</title>
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
