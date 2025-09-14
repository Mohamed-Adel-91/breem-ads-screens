<section class="where_us">
    <div class="container">
        <h3 class="text-center mb-5">{{ $whereTitle }}</h3>

        <div class="swiper whereSwiper">
            <div class="swiper-wrapper">
                @foreach ($whereSlides as $w)
                    <div class="swiper-slide">
                        <div class="location-card">
                            <img src="{{ asset($w['image_url']) }}" alt="{{ $w['overlay_text'] }}">
                            <div class="overlay">
                                <p>{{ $w['overlay_text'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="swiper-controls d-flex justify-content-between align-items-center mt-5">
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination text-center flex-grow-1"></div>
                <div class="swiper-button-next"></div>
            </div>

            <div class="button_book">
                <a href="{{ $brochure['link_url'] }}">
                    {{ $brochure['text'] }}
                    <img src="{{ asset($brochure['icon_url']) }}" alt="">
                </a>
            </div>
        </div>
    </div>
</section>
