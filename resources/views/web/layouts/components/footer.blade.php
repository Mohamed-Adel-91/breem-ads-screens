{{--
    The footer's business information comes from Settings, resolved once per request by
    App\Services\LayoutService and shared by the view composer in AppServiceProvider.
    There is no query in this file and there must not be one.

    Every block below is conditional. An unconfigured field renders nothing rather than
    falling back to the address and phone number that used to be hardcoded here — a stale
    contact detail on a live website is worse than a missing one, because nobody notices
    it is wrong.
--}}
<footer>
    <section class="footer">
        <div class="overlay"></div>
        <div class="site-container">
            <div class="row">
                <div class="col-md-4 d-flex justify-content-center align-items-center flex-column">
                    <div class="logo-footer">
                        <img src="{{ $layoutSettings['footer_logo']['src'] }}"
                             alt="{{ $layoutSettings['footer_logo']['alt'] }}">
                    </div>
                    <div class="footer_data mt-4">
                        @if (!empty($layoutSettings['address']))
                            <p>{{ $layoutSettings['address'] }}</p>
                        @endif

                        {{--
                            `.footer-contact-link` is what keeps these off Bootstrap's
                            default link blue. Nothing in the footer scope used to style an
                            anchor except the page links, so the moment the phone and email
                            became real `tel:`/`mailto:` anchors they inherited
                            `--bs-link-color` and rendered blue on the dark teal panel.
                            The class is styled in master.css; do not rely on the element
                            inheriting its parent's colour, because a bare `a` will not.

                            Visible digits follow the locale; the href does not. `tel:` is a
                            machine value parsed by the dialer, so it stays ASCII with the
                            `+` at the front — see LayoutService::telHref().
                        --}}
                        @if (!empty($layoutSettings['phone_link']))
                            <p>
                                <a class="footer-contact-link" href="tel:{{ $layoutSettings['phone_link'] }}">
                                    {{ localized_digits($layoutSettings['phone']) }}
                                </a>
                            </p>
                        @endif

                        @if (!empty($layoutSettings['email']))
                            <p>
                                <a class="footer-contact-link" href="mailto:{{ $layoutSettings['email'] }}">
                                    {{ $layoutSettings['email'] }}
                                </a>
                            </p>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="linkss">
                        <div class="pagess">
                            @php $locale = app()->getLocale(); @endphp
                            @foreach ($footerMenu?->items ?? [] as $item)
                                @php $target = $item->target ?? '_self'; @endphp
                                <a href="{{ url($locale . $item->url) }}" target="{{ $target }}">{{ $item->label }}</a>
                            @endforeach
                        </div>
                        {{--
                            Every configured channel, in the registry's order. The list is
                            already filtered by LayoutService, so this cannot emit href="#"
                            — an unconfigured channel is absent, not dead.

                            `flex-wrap` because there are eight possible icons now and a
                            fixed row would overflow the column on a narrow viewport.
                        --}}
                        <div class="footer-socials d-flex flex-wrap justify-content-center gap-4">
                            <x-web.social-links :links="$layoutSettings['social_links'] ?? []"
                                                item-class="footer-social" />
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    {{--
                        The map element is built here from a validated URL rather than
                        echoed as stored HTML. width/height match what the setting used to
                        carry, and master.css still caps and reflows it responsively.
                    --}}
                    @if (!empty($layoutSettings['map_embed_url']))
                        <iframe src="{{ $layoutSettings['map_embed_url'] }}"
                                width="400" height="300" style="border:0;"
                                title="{{ __('translate.layout.map_title') }}"
                                allowfullscreen loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                    @endif
                </div>
            </div>
        </div>
    </section>
</footer>
