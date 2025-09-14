@php
    // No section-level settings used here
@endphp

<section class="slider">
    <div class="container">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                @foreach ($section->items as $item)
                    @php
                        $itemData = $item->getTranslation('data', app()->getLocale());
                        if (is_string($itemData)) {
                            $decoded = json_decode($itemData, true);
                            $itemData = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                        }
                        if (!is_array($itemData)) {
                            $itemData = [];
                        }
                    @endphp
                    <div class="swiper-slide">
                        <img src="{{ asset($itemData['image_url'] ?? '') }}" alt="{{ $itemData['alt'] ?? '' }}">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
