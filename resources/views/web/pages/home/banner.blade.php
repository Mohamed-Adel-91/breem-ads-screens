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

<section class="banner">
    <div class="banner_video">
        <video
            src="{{ asset($sectionSettings['video_url'] ?? '') }}"
            @if (!empty($sectionSettings['autoplay'] ?? null)) autoplay @endif
            @if (!empty($sectionSettings['loop'] ?? null)) loop @endif
            @if (!empty($sectionSettings['playsinline'] ?? null)) playsinline @endif
            @if (!empty($sectionSettings['muted'] ?? null)) muted @endif
            @if (!empty($sectionSettings['controls'] ?? null)) controls @endif
        ></video>
    </div>
</section>
