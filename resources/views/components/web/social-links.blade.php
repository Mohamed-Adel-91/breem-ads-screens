@props([
    // Configured URLs, canonical key => URL. Already filtered and ordered by
    // App\Services\LayoutService — this component never decides what is configured.
    'links' => [],
    // The subset this surface draws. Null means "every configured channel".
    'only' => null,
    // Wrapper element classes, and the per-item class prefix used by master.css.
    'itemClass' => '',
])

{{--
    The public site's social links, rendered from ONE source.

    The footer and the floating rail both render this component with the same `$links`
    array, which comes from the `social.links` setting. Neither surface holds a URL of its
    own, so an administrator changes a link in one place and both update.

    `$only` exists because the two surfaces are different SHAPES, not different data: the
    rail is a fixed four-slot design element while the footer lists everything configured.
    That is a presentation choice and it is passed in by the caller, never stored.

    A channel with no configured URL never reaches this component, so there is no `href="#"`
    branch to get wrong. That is the whole point of filtering upstream.
--}}
@php
    $platforms = $only === null ? array_keys($links) : array_values(array_intersect($only, array_keys($links)));
@endphp

@foreach ($platforms as $platform)
    @php $label = \App\Support\SocialPlatforms::label($platform); @endphp
    <a href="{{ $links[$platform] }}"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="{{ __('translate.layout.social_link', ['platform' => $label]) }}"
       @class([$itemClass => $itemClass !== '', 'social-link--' . $platform])>
        <x-web.social-icon :platform="$platform" />
    </a>
@endforeach
