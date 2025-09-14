@extends('web.layouts.master')


<!-- SEO Meta -->
@push('meta')
    <meta name="description" content="description" />
@endpush


@section('content')
    <!-- Main Content -->
    <main>
        @php
            $partialMap = [
                'banner' => 'banner',
                'partners' => 'slider',
                'about' => 'knowmore',
                'stats' => 'media',
                'where_us' => 'where_us',
                'cta' => 'your_ads',
            ];
        @endphp
        @foreach ($sections as $section)
            @php $partial = $partialMap[$section->type] ?? $section->type; @endphp
            @includeIf('web.pages.home.' . $partial, ['section' => $section])
        @endforeach
    </main>
@endsection

@push('scripts-js')
@endpush
