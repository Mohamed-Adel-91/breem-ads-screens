@php
    // No section-level settings used here
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
                            <img class="w-100" src="{{ asset($itemData['icon_url'] ?? '') }}" alt="">
                        </div>
                        <div>
                            <span>{{ $itemData['number'] ?? '' }}</span>
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
