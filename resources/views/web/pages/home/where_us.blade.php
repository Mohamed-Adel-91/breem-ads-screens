@php
    $current = $section->getTranslation('section_data', app()->getLocale(), true);
    $fallback = $section->getTranslation('section_data', config('app.fallback_locale'), true);
    foreach (['current','fallback'] as $var) {
        if (is_string(${$var})) {
            $decoded = json_decode(${$var}, true);
            ${$var} = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }
        if (!is_array(${$var})) {
            ${$var} = [];
        }
    }
    $section_data = array_replace($fallback, $current);
@endphp

<section class="where_us">
    <div class="container">
        <h3 class="text-center mb-5">{{ $section_data['title'] ?? '' }}</h3>
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
                            @php
                                $rawImage = $itemData['image_url'] ?? '';
                                if (preg_match('/^https?:\/\//', $rawImage)) {
                                    $imgUrl = $rawImage;
                                } else {
                                    $normImage = str_starts_with($rawImage, 'frontend/') ? $rawImage : 'frontend/' . ltrim($rawImage, '/');
                                    $imgUrl = asset($normImage);
                                }
                            @endphp
                            <img src="{{ $imgUrl }}" alt="{{ $itemData['overlay_text'] ?? '' }}">
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
                $brochure = $section_data['brochure'] ?? [];
                if (is_string($brochure)) {
                    $decoded = json_decode($brochure, true);
                    $brochure = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                }
                if (!is_array($brochure)) {
                    $brochure = [];
                }
            @endphp
            <div class="button_book">
                @php
                    $rawIcon = $brochure['icon_url'] ?? '';
                    if (preg_match('/^https?:\/\//', $rawIcon)) {
                        $iconUrl = $rawIcon;
                    } else {
                        $normIcon = str_starts_with($rawIcon, 'frontend/') ? $rawIcon : 'frontend/' . ltrim($rawIcon, '/');
                        $iconUrl = asset($normIcon);
                    }
                @endphp
                <a href="{{ $brochure['link_url'] ?? '#' }}">
                    {{ $brochure['text'] ?? '' }} <img src="{{ $iconUrl }}" alt="">
                </a>
            </div>
        </div>
    </div>
</section>
