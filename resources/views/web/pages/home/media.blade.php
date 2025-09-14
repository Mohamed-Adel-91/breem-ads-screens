@php
    // No section-level settings used here
@endphp

<section class="media">
    <div class="container">
        <div class="row">
            @foreach ($section->items as $item)
                @php
                    $itemData = $item->getTranslation('data', app()->getLocale());
                    if (is_string($itemData)) {
                        $decoded = json_decode($itemData, true);
                        $itemData = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                    }
                    if (!is_array($itemData)) {
                        $itemData = [];
                    }
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
