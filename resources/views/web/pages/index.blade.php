@extends('web.layouts.master')

{{--
    NO SEO META HERE. This view used to push `<meta name="description" content="description">`
    — the literal word, not a description — on top of the real tag that
    web/layouts/meta/meta.blade.php renders from the `seo_metas` record. Every page shipped
    two description tags, the second of them meaningless.

    SEO belongs to the layout and is edited under Website CMS → SEO Metas. The `@stack('meta')`
    extension point in the layout stays available for a page that genuinely needs an extra
    tag; a description is not that, because the layout already owns it.
--}}

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
