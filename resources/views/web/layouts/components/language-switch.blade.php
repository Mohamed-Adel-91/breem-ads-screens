@php
    $currentLocale = app()->getLocale();
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    $switchParams = Route::current()?->parameters() ?? [];
    $switchParams['lang'] = $targetLocale;
    $switchUrl = Route::currentRouteName()
        ? route(Route::currentRouteName(), $switchParams)
        : url('/' . $targetLocale);
    if ($queryString = request()->getQueryString()) {
        $switchUrl .= '?' . $queryString;
    }
    $flags = [
        'ar' => ['file' => 'sa.svg', 'width' => 30, 'height' => 20],
        'en' => ['file' => 'us.svg', 'width' => 38, 'height' => 20],
    ];
    $flag = $flags[$targetLocale];
    $switchLabel = $targetLocale === 'ar' ? 'العربية' : 'English';
@endphp

<a class="nav-link lang-switch"
   href="{{ $switchUrl }}"
   hreflang="{{ $targetLocale }}"
   aria-label="{{ __('translate.layout.switch_language') }}">
    <img class="lang-switch__flag"
         src="{{ asset('frontend/img/flags/' . $flag['file']) }}"
         width="{{ $flag['width'] }}"
         height="{{ $flag['height'] }}"
         alt=""
         aria-hidden="true">
    <span class="lang-switch__label" lang="{{ $targetLocale }}">{{ $switchLabel }}</span>
</a>
