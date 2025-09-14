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

<section class="your_ads">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-6">
                <h3>{{ $section_data['title'] ?? '' }}</h3>
                <p>{{ $section_data['text'] ?? '' }}</p>
                <a href="{{ $section_data['link_url'] ?? '#' }}" class="link">{{ $section_data['link_text'] ?? '' }}</a>
            </div>
            <div class="col-12 col-sm-6">
                <div class="position-relative w-100">
                    @php
                        $rawMain = $section_data['image_url'] ?? '';
                        if (preg_match('/^https?:\/\//', $rawMain)) {
                            $mainUrl = $rawMain;
                        } else {
                            $normMain = str_starts_with($rawMain, 'frontend/') ? $rawMain : 'frontend/' . ltrim($rawMain, '/');
                            $mainUrl = asset($normMain);
                        }

                        $rawOverlay = $section_data['overlay_image_url'] ?? '';
                        if (preg_match('/^https?:\/\//', $rawOverlay)) {
                            $overlayUrl = $rawOverlay;
                        } else {
                            $normOverlay = str_starts_with($rawOverlay, 'frontend/') ? $rawOverlay : 'frontend/' . ltrim($rawOverlay, '/');
                            $overlayUrl = asset($normOverlay);
                        }
                    @endphp
                    <img src="{{ $mainUrl }}" alt="" class="w-100">
                    <img src="{{ $overlayUrl }}" alt="" class="position-absolute second_image">
                </div>
            </div>
        </div>
    </div>
</section>
