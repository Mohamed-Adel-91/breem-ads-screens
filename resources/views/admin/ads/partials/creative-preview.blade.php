{{--
    Ad creative presentation. Uses the URL the Ad model already resolves
    (`$ad->file_url` → App\Support\MediaUrl), so storage locations and the S3/local
    decision are untouched. Images and GIFs reuse the canonical x-admin.media-preview
    component; videos use a plain HTML5 <video> element. No upload library, no
    transcoding, no media manager.

    Usage: @include('admin.ads.partials.creative-preview', ['ad' => $ad, 'caption' => '...'])
--}}
@php
    $caption = $caption ?? null;
    $creativeUrl = $ad->file_url;
    $creativeType = $ad->file_type;
    $creativeLabel = data_get($ad->getTranslations('title'), app()->getLocale())
        ?: __('admin.ads.untitled', ['id' => $ad->id]);
@endphp

@if ($creativeUrl)
    @if ($creativeType === 'video')
        @if ($caption)
            <span class="d-block text-muted small mb-1">{{ $caption }}</span>
        @endif
        <video controls
               preload="metadata"
               class="img-thumbnail admin-ad-creative"
               aria-label="{{ $creativeLabel }}">
            <source src="{{ $creativeUrl }}">
            {{ __('admin.cms.ui.unsupported_video') }}
        </video>
    @else
        <x-admin.media-preview
            :url="$creativeUrl"
            :alt="$creativeLabel"
            :caption="$caption" />
    @endif

    <a href="{{ $creativeUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="d-inline-block mt-2 small">
        <i class="fe fe-external-link" aria-hidden="true"></i>
        {{ __('admin.ads.form.open_asset') }}
    </a>
@else
    <p class="text-muted small mb-0">{{ __('admin.ads.show.no_media') }}</p>
@endif
