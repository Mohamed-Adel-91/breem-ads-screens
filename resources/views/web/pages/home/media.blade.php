@php
    // 
@endphp

<section class="media">
    <div class="container">
        <div class="row">
            @foreach ($section->items as $item)
                @php
                    $current = $item->getTranslation('data', app()->getLocale(), true);
                    $fallback = $item->getTranslation('data', config('app.fallback_locale'), true);
                    foreach (['current','fallback'] as $var) {
                        if (is_string(${$var})) {
                            $decoded = json_decode(${$var}, true);
                            ${$var} = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                        }
                        if (!is_array(${$var})) {
                            ${$var} = [];
                        }
                    }
                    $itemData = array_replace($fallback, $current);
                @endphp
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="box">
                        <div class="image">
                            <img class="w-100 h-100  object-contain;" src="{{ asset(media_path($itemData['icon_path'] ?? '')) }}" alt="">
                        </div>
                        <div>
                            {{--
                                The statistic figure. CMS-authored text such as "+658",
                                so localized_digits() substitutes only the digits and
                                leaves the "+" where it is — the browser's bidi algorithm
                                places the sign for the paragraph direction. Arabic reads
                                "+٦٥٨"; English is unchanged.
                            --}}
                            <span>{{ localized_digits($itemData['number'] ?? '') }}</span>
                        </div>
                        <div class="desc">
                            <p>{{ $itemData['label'] ?? '' }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
