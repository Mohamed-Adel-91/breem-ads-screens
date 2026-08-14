@php
    $locale = app()->getLocale();
@endphp
<header @class(['secondheader' => $solid ?? false])>
    <nav class="navbar navbar-expand-lg site-navbar">
        <div class="site-container site-navbar__inner">
            <a class="navbar-brand site-navbar__brand" href="{{ url($locale) }}">
                <img src="{{ $layoutSettings['header_logo']['src'] }}"
                     alt="{{ $layoutSettings['header_logo']['alt'] }}">
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
                    {{--
                        The phone number, from the same `site.phone` setting the footer
                        reads — resolved once per request by App\Services\LayoutService and
                        shared by the view composer. There is no second phone source.

                        It used to render `href="#"`, so the number in the header was the
                        one place on the site a visitor could see it and not call it. The
                        dialable form is `phone_link`: ASCII, `+` at the front, produced by
                        LayoutService::telHref() — the same value the footer dials, so the
                        normalisation (including a `+` typed at the visual end of an RTL
                        field) is not reimplemented here.

                        Visible digits follow the locale. The href never does: a dialler
                        cannot parse Arabic-Indic numerals.

                        The whole item is conditional. An unconfigured phone leaves no
                        empty row and no `tel:` with nothing after it — the language switch
                        simply becomes the only item, which the flex/gap rules already
                        handle at every width.
                    --}}
                    @if (!empty($layoutSettings['phone_link']))
                        <li class="nav-item">
                            <a class="nav-link site-navbar__phone"
                               href="tel:{{ $layoutSettings['phone_link'] }}">
                                {{ localized_digits($layoutSettings['phone']) }}
                            </a>
                        </li>
                    @endif
                    <li class="nav-item">
                        @include('web.layouts.components.language-switch')
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
