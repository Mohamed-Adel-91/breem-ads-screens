@php
    /*
     * The language control names the language it switches TO, and carries that language's
     * flag: an English page offers "العربية" behind the Saudi flag, an Arabic page offers
     * "English" behind the United States flag.
     *
     * TARGET IS DERIVED FROM THE ACTIVE LOCALE, not from the first URL segment.
     * The previous version read `request()->segment(1) === 'ar' ? 'en' : 'ar'` while
     * taking its label from `app()->getLocale()`. `{lang?}` is optional in
     * routes/web.php, so on a URL with no locale prefix segment(1) holds a path name,
     * that expression evaluates to 'ar', and the control offered Arabic while its label
     * said English. One source for both removes the disagreement.
     */
    $currentLocale = app()->getLocale();
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';

    /*
     * Same page, other locale. Route parameters and the query string are carried across,
     * so /en/whoweare?utm=x lands on /ar/whoweare?utm=x rather than dropping the reader
     * on the home page. The url() fallback only applies when there is no named route to
     * rebuild — a 404, for instance.
     */
    $switchParams = Route::current()?->parameters() ?? [];
    $switchParams['lang'] = $targetLocale;

    $switchUrl = Route::currentRouteName()
        ? route(Route::currentRouteName(), $switchParams)
        : url('/' . $targetLocale);

    if ($queryString = request()->getQueryString()) {
        $switchUrl .= '?' . $queryString;
    }

    /*
     * Flag asset, and its intrinsic size. The two flags have different official ratios
     * — Saudi Arabia is 3:2, the United States 1.9:1 — so the dimensions travel with the
     * file rather than being assumed equal. CSS sets the rendered width; these
     * attributes give the browser the aspect ratio so the row does not shift as the SVG
     * loads.
     */
    $flags = [
        'ar' => ['file' => 'sa.svg', 'width' => 30, 'height' => 20],
        'en' => ['file' => 'us.svg', 'width' => 38, 'height' => 20],
    ];

    $flag = $flags[$targetLocale];

    // Label in the target language — it is the one word on the page written in the
    // language being offered.
    $switchLabel = $targetLocale === 'ar' ? 'العربية' : 'English';
@endphp

<a class="nav-link lang-switch"
   href="{{ $switchUrl }}"
   hreflang="{{ $targetLocale }}"
   aria-label="{{ __('translate.layout.switch_language') }}">
    {{--
        The flag is decorative: the label beside it already names the language, and
        alt="Saudi Arabia" would make a screen reader announce the country on top of the
        aria-label that already says what the link does.
    --}}
    <img class="lang-switch__flag"
         src="{{ asset('frontend/img/flags/' . $flag['file']) }}"
         width="{{ $flag['width'] }}"
         height="{{ $flag['height'] }}"
         alt=""
         aria-hidden="true">
    <span class="lang-switch__label" lang="{{ $targetLocale }}">{{ $switchLabel }}</span>
</a>
