@php
    $sectionSettings = $section->getTranslation('settings', app()->getLocale());
    if (is_string($sectionSettings)) {
        $decoded = json_decode($sectionSettings, true);
        $sectionSettings = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
    }
    if (!is_array($sectionSettings)) {
        $sectionSettings = [];
    }
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
