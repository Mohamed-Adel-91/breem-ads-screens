{{--
    The public site's navigation bar, shared by both headers.

    WHY ONE PARTIAL: transparent-header and solid-header held two near-identical copies
    of this markup, and had already drifted — one carried a comment pointing at the other
    for the reasoning behind its own phone link. They now differ only in the class on
    <header>, which is the only thing that was ever really different.

    STRUCTURE. Three groups on one row, all bounded by `.site-container` so the navbar
    lines up with the page content beneath it:

        [ brand ]        [ primary navigation ]        [ phone · language ]

    The previous markup nested `.container > nav > .container-fluid`; the inner
    `.container-fluid` re-expanded to the full width of the outer container, so nothing
    was really constraining the bar.

    THE COLLAPSE. There used to be TWO divs both carrying id="navbarTogglerDemo03" — one
    for the page links, one for the phone and language links. Bootstrap resolves a
    toggler's data-bs-target to the FIRST match, so on mobile the second group could
    never be opened: the phone number and the language switch were unreachable below
    992px. One collapse now holds both lists, under an id that is unique on the page.

    @param bool $solid  true for the opaque inner-page header, false for the transparent
                        header that sits over the home hero.
--}}

@php
    $locale = app()->getLocale();
@endphp

<header @class(['secondheader' => $solid ?? false])>
    <nav class="navbar navbar-expand-lg site-navbar">
        <div class="site-container site-navbar__inner">
            <a class="navbar-brand site-navbar__brand" href="{{ url($locale) }}">
                <img src="img/logo.png" alt="Breem">
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#breemPrimaryNav" aria-controls="breemPrimaryNav"
                aria-expanded="false" aria-label="{{ __('translate.layout.toggle_navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse site-navbar__collapse" id="breemPrimaryNav">
                <ul class="navbar-nav pages site-navbar__pages">
                    @foreach ($headerMenu?->items ?? [] as $item)
                        @php $target = $item->target ?? '_self'; @endphp
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url($locale . $item->url) }}" target="{{ $target }}">
                                {{ $item->label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <ul class="navbar-nav pages site-navbar__meta">
                    <li class="nav-item">
                        {{--
                            Visible phone digits follow the page locale. Safe because the
                            href is "#", not a tel: link — if this ever becomes tel:, the
                            href must keep ASCII digits or the dialler breaks. Localize
                            the label, never the target.
                        --}}
                        <a class="nav-link" href="#">{{ localized_digits($layoutSettings['phone'] ?? '') }}</a>
                    </li>
                    <li class="nav-item">
                        @include('web.layouts.components.language-switch')
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
