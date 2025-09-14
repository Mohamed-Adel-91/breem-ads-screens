<section class="your_ads">
    <div class="container">
        <div class="row">
            <div class="col-12 col-sm-6">
                <h3>{{ $cta['title'] }}</h3>
                <p>{{ $cta['text'] }}</p>
                <a href="{{ $cta['link_url'] }}" class="link">{{ $cta['link_text'] }}</a>
            </div>
            <div class="col-12 col-sm-6">
                <div class="position-relative w-100">
                    @if ($cta['image_url'])
                        <img src="{{ asset($cta['image_url']) }}" alt="" class="w-100">
                    @endif
                    @if ($cta['overlay_image_url'])
                        <img src="{{ asset($cta['overlay_image_url']) }}" alt=""
                            class="position-absolute second_image">
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
