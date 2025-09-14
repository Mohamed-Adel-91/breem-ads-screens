<section class="slider">
    <div class="container">
        <div class="swiper mySwiper">
            <div class="swiper-wrapper">
                @foreach ($sliderItems as $slide)
                    <div class="swiper-slide">
                        <img src="{{ asset($slide['image_url']) }}" alt="{{ $slide['alt'] }}">
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
