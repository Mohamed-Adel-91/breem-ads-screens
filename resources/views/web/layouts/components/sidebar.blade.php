{{--
    The floating social rail.

    It used to hold four hand-written inline SVGs each wrapped in `href="#"` — every icon
    on every page of the site was a dead link — while a `sidebar.icons` setting sat beside
    it holding the real URLs that nothing read.

    There is no second URL store now. The rail reads the same `social.links` that the
    footer reads, so a link changed once in Settings changes in both places. It draws a
    fixed subset (SocialPlatforms::SIDEBAR_PLATFORMS) because the rail is a four-slot
    design element, not a directory — that is a shape decision, not a data one.

    The whole element disappears when nothing in its subset is configured, rather than
    leaving an empty white rail floating over the page.
--}}
@php
    $railLinks = array_intersect_key(
        $layoutSettings['social_links'] ?? [],
        array_flip(\App\Support\SocialPlatforms::SIDEBAR_PLATFORMS),
    );
@endphp

@if (!empty($railLinks))
    <div class="sidebar">
        <ul>
            @foreach (\App\Support\SocialPlatforms::SIDEBAR_PLATFORMS as $platform)
                @continue (!isset($railLinks[$platform]))
                {{--
                    The platform name is on the <li>, not implied by its position.
                    master.css used to colour each hover state with `:nth-child(n)`, which
                    was correct only while all four were always present — the moment a
                    channel is left unconfigured the next one inherits its brand colour.
                --}}
                <li class="sidebar__item sidebar__item--{{ $platform }}">
                    <x-web.social-links :links="$railLinks" :only="[$platform]" />
                </li>
            @endforeach
        </ul>
    </div>
@endif
