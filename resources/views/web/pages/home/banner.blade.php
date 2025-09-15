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

<section class="banner">
    <div class="banner_video">
        <video
            src="{{ asset(media_path($section_data['video_path'] ?? '')) }}"
            @if (!empty($section_data['autoplay'] ?? null)) autoplay @endif
            @if (!empty($section_data['loop'] ?? null)) loop @endif
            @if (!empty($section_data['playsinline'] ?? null)) playsinline @endif
            @if (!empty($section_data['muted'] ?? null)) muted @endif
            @if (!empty($section_data['controls'] ?? null)) controls @endif
        ></video>
    </div>
</section>
