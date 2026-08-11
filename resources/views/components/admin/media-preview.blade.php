@props([
    'url' => null,
    'alt' => null,
    'fallback' => null,
    'emptyLabel' => null,
    'maxHeight' => '150px',
    'linkable' => true,
    'caption' => null,
])

@php
    $source = filled($url) ? $url : $fallback;
    $altText = filled($alt) ? $alt : __('admin.media.preview_alt', ['label' => __('admin.table.image')]);
    $missingLabel = $emptyLabel ?: __('admin.media.no_media_selected');
@endphp

<div {{ $attributes->class(['admin-media-preview']) }}>
    @if (filled($source))
        @if ($caption)
            <span class="d-block text-muted small mb-1" data-media-preview-caption>{{ $caption }}</span>
        @endif

        @if ($linkable)
            <a href="{{ $source }}"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="{{ __('admin.media.open_full_size') }}">
                <img src="{{ $source }}"
                     alt="{{ $altText }}"
                     class="img-thumbnail"
                     style="max-height: {{ $maxHeight }}; width: auto;">
            </a>
        @else
            <img src="{{ $source }}"
                 alt="{{ $altText }}"
                 class="img-thumbnail"
                 style="max-height: {{ $maxHeight }}; width: auto;">
        @endif
    @else
        <p class="text-muted small mb-0">{{ $missingLabel }}</p>
    @endif
</div>
