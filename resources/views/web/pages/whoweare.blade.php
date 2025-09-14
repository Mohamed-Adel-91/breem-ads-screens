@extends('web.layouts.master')

@push('meta')
    <meta name="description" content="description" />
@endpush

@section('content')
    <main>
        @php
            $locale = app()->getLocale();
            $banner = $sections->firstWhere('type', 'second_banner') ?? null;
            $who    = $sections->firstWhere('type', 'who_we') ?? null;
            $port   = $sections->firstWhere('type', 'port_image') ?? null;
        @endphp

        @if ($banner)
            <section class="second_banner">
                <div class="banner_image">
                    <img src="{{ data_get($banner->getTranslations('section_data'), "$locale.image_url") }}" alt="">
                </div>
            </section>
        @endif

        @if ($who)
            <section class="who_we">
                <div class="container">
                    <h2>{{ data_get($who->getTranslations('section_data'), "$locale.title") }}</h2>
                    <p>
                        {{ data_get($who->getTranslations('section_data'), "$locale.description") }}
                    </p>

                    @foreach ($who->items as $it)
                        @php
                            $data = $it->getTranslations('data');
                            $title = data_get($data, "$locale.title");
                            $text  = data_get($data, "$locale.text");
                            $bullets = data_get($data, "$locale.bullets", []);
                        @endphp
                        <div class="mt-5 bottom-desc">
                            <h4><img src="img/Vector.png" class="ms-4" alt="">{{ $title }}</h4>
                            <p>{{ $text }}</p>
                            @if (!empty($bullets))
                                <ul>
                                    @foreach ($bullets as $b)
                                        <li>{{ $b }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($port)
            <section class="port_image">
                <img src="{{ data_get($port->getTranslations('section_data'), "$locale.image_url") }}" alt="">
            </section>
        @endif
    </main>
@endsection

@push('scripts-js')
@endpush

