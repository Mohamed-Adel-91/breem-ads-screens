@php
    $current = $section->getTranslation('settings', app()->getLocale(), true);
    $fallback = $section->getTranslation('settings', config('app.fallback_locale'), true);
    foreach (['current','fallback'] as $var) {
        if (is_string(${$var})) {
            $decoded = json_decode(${$var}, true);
            ${$var} = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array(${$var})) {
            ${$var} = [];
        }
    }
    $sectionSettings = array_replace($fallback, $current);
@endphp

<section class="where_us">
    <div class="container">
        <h3 class="text-center mb-5">{{ $sectionSettings['title'] ?? '' }}</h3>
        <div class="swiper whereSwiper">
            <div class="swiper-wrapper">
                @foreach ($section->items as $item)
                    @php
                        $current = $item->getTranslation('data', app()->getLocale(), true);
                        $fallback = $item->getTranslation('data', config('app.fallback_locale'), true);
                        foreach (['current','fallback'] as $var) {
                            if (is_string(${$var})) {
                                $decoded = json_decode(${$var}, true);
                                ${$var} = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                            }
                            if (!is_array(${$var})) {
                                ${$var} = [];
                            }
                        }
                        $itemData = array_replace($fallback, $current);
                    @endphp
                    <div class="swiper-slide">
                        <div class="location-card">
                            <img src="{{ asset($itemData['image_url'] ?? '') }}" alt="{{ $itemData['overlay_text'] ?? '' }}">
                            <div class="overlay">
                                <p>{{ $itemData['overlay_text'] ?? '' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="swiper-controls d-flex justify-content-between align-items-center mt-5">
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination text-center flex-grow-1"></div>
                <div class="swiper-button-next"></div>
            </div>

            @php
                $brochure = $sectionSettings['brochure'] ?? [];
                if (is_string($brochure)) {
                    $decoded = json_decode($brochure, true);
                    $brochure = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                }
                if (!is_array($brochure)) {
                    $brochure = [];
                }
            @endphp
            <div class="button_book">
                <a href="{{ $brochure['link_url'] ?? '#' }}">
                    {{ $brochure['text'] ?? '' }} <img src="{{ asset($brochure['icon_url'] ?? '') }}" alt="">
                </a>
            </div>
        </div>
    </div>
</section>
