@extends('web.layouts.master')

@push('meta')
    <title>بريم | تواصل معنا</title>
    <meta name="description" content="description" />
@endpush

@section('content')
    <main>
        @php
            $locale = app()->getLocale();
            $banner = $sections->firstWhere('type', 'second_banner') ?? null;
            $heading = $sections->firstWhere('type', 'contact_us') ?? null;
            $map = $sections->firstWhere('type', 'map') ?? null;
            $bottom = $sections->firstWhere('type', 'bottom_banner') ?? null;
        @endphp

        @if ($banner)
            <section class="second_banner">
                <div class="banner_image">
                    <img src="{{ data_get($banner->getTranslations('section_data'), "$locale.image_url") }}" alt="">
                </div>
            </section>
        @endif

        <section class="contact_us mt-5">
            <div class="container">
                <h2>{{ data_get($heading?->getTranslations('section_data'), "$locale.title") }}</h2>
                <p>{{ data_get($heading?->getTranslations('section_data'), "$locale.subtitle") }}</p>
                <div class="row">
                    @include('web.pages.contact-forms.ads-subscribe')

                    @include('web.pages.contact-forms.screens-subscribe')

                    @include('web.pages.contact-forms.ads-creating-subscribe')

                    @include('web.pages.contact-forms.faqs')
                </div>
            </div>
        </section>

        @if ($map)
            <section class="map">
                <div class="back_image">
                    <img class="bann" src="{{ data_get($map->getTranslations('section_data'), "$locale.background_image") }}" alt="">
                    <div class="overlay"></div>
                    <div class="map_content">
                        <h3>{{ data_get($map->getTranslations('section_data'), "$locale.title") }}</h3>
                        <p><img src="img/loc.png" alt="">
                            <span>{{ data_get($map->getTranslations('section_data'), "$locale.address") }}</span>
                        </p>
                        <p class="contact mt-5">
                            <span>
                                <img src="img/phone.png" alt="">
                                {{ data_get($map->getTranslations('section_data'), "$locale.phone_label") }}
                            </span>
                        </p>
                        <p class="contact">
                            <span>
                                <img src="img/whats.png" alt="">
                                {{ data_get($map->getTranslations('section_data'), "$locale.whatsapp_label") }}
                            </span>
                        </p>
                    </div>
                </div>
            </section>
        @endif

        @if ($bottom)
            <section class="w-100">
                <img class="w-100" src="{{ data_get($bottom->getTranslations('section_data'), "$locale.image_url") }}" alt="">
            </section>
        @endif
    </main>
@endsection

@push('scripts-js')
    <script>
        $(function() {
            if (typeof $.fn.multiselect !== 'function') {
                console.error('bootstrap-multiselect لم يتم تحميله.');
                return;
            }

            $('#modelselect').multiselect({
                includeSelectAllOption: true,
                selectAllText: 'تحديد الكل',
                allSelectedText: 'تم تحديد الكل',
                nonSelectedText: 'اختر الأماكن',
                buttonWidth: '100%',
                buttonClass: 'btn btn-light w-100 text-start',
                maxHeight: 200,
                numberDisplayed: 3,
                buttonContainer: '<div class="btn-group w-100" />',
                templates: {
                    button: '<button type="button" class="multiselect dropdown-toggle w-100 text-start" data-bs-toggle="dropdown">' +
                        '<span class="multiselect-selected-text"></span> <b class="caret"></b>' +
                        '</button>'
                }
            });
        });
    </script>
@endpush

