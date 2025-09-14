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

<section class="your_ads">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-6">
                <h3>{{ $sectionSettings['title'] ?? '' }}</h3>
                <p>{{ $sectionSettings['text'] ?? '' }}</p>
                <a href="{{ $sectionSettings['link_url'] ?? '#' }}" class="link">{{ $sectionSettings['link_text'] ?? '' }}</a>
            </div>
            <div class="col-12 col-sm-6">
                <div class="position-relative w-100">
                    <img src="{{ asset($sectionSettings['image_url'] ?? '') }}" alt="" class="w-100">
                    <img src="{{ asset($sectionSettings['overlay_image_url'] ?? '') }}" alt="" class="position-absolute second_image">
                </div>
            </div>
        </div>
    </div>
</section>
