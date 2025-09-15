<footer>
    <section class="footer">
        <div class="overlay"></div> <!-- أوفرلاي -->
        <div class="container">
            <div class="row">
                <div class="col-md-4 d-flex justify-content-center align-items-center flex-column">
                    <div class="logo-footer">
                        <img src="img/whitelogo.png" alt="">
                    </div>
                    <div class="footer_data mt-4">
                        <p> شارع بني تميم متفرع من الملك فهد – حي المروج، مبنى رقم 2174، الدور الخامس الرمز البريدي 12282 – الرياض، المملكة العربية السعودية.</p>
                        <p>۹۹٦٥٤۳۳٤+</p>
                        <p>info@breem.com</p>
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
                        <div class="d-flex gap-4">
                            @php
                                $icons = [
                                    'linkedin' => 'img/LinkedIn-Icon.png',
                                    'youtube' => 'img/Youtube-Icon.png',
                                    'twitter' => 'img/Twitter-Icon.png',
                                    'facebook' => 'img/Facebook-Icon.png',
                                ];
                            @endphp
                            @foreach ($layoutSettings['social_links'] ?? [] as $name => $link)
                                @if(isset($icons[$name]))
                                    <a href="{{ $link }}"><img src="{{ $icons[$name] }}" alt="{{ $name }}"></a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    @if (!empty($layoutSettings['map_iframe']))
                        {!! $layoutSettings['map_iframe'] !!}
                    @endif
                </div>
            </div>
        </div>
    </section>
</footer>
