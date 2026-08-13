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
