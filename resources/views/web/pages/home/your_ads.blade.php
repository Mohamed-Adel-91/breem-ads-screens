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
                    @if(!empty($section_data['image_path']))
                        <img src="{{ asset(media_path($section_data['image_path'])) }}" alt="" class="w-100">
                    @endif
                    @if(!empty($section_data['overlay_image_path']))
                        <img src="{{ asset(media_path($section_data['overlay_image_path'])) }}" alt="" class="position-absolute second_image">
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
