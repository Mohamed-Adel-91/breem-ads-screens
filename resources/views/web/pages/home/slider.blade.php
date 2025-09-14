@php
    // No section-level settings used here
@endphp

<section class="slider">
    <div class="container">
        <div class="swiper mySwiper">
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
                        @php
                            $raw = $itemData['image_url'] ?? '';
                            if (preg_match('/^https?:\/\//', $raw)) {
                                $url = $raw;
                            } else {
                                $norm = str_starts_with($raw, 'frontend/') ? $raw : 'frontend/' . ltrim($raw, '/');
                                $url = asset($norm);
                            }
                        @endphp
                        <img src="{{ $url }}" alt="{{ $itemData['alt'] ?? '' }}">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
