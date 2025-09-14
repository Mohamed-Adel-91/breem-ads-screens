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

<section class="Knowmore">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-3">
                <h3>{!! nl2br(e($section_data['title'] ?? '')) !!}</h3>
            </div>
            <div class="col-12 col-md-9">
                <p class="desc">
                    {{ $section_data['desc'] ?? '' }}
                </p>

                <a href="{{ $section_data['readmore_link'] ?? '#' }}" class="link_button">
                    {{ $section_data['readmore_text'] ?? '' }}
                </a>
            </div>
        </div>
    </div>
</section>
