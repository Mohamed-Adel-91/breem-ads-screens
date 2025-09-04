<div class="container">
    <div class="Tobtitle">
        <h2>الشكل الخارجي للسيارة</h2>
    </div>
</div>
<div class="flex flex-col">
    <div class="w-full">
        <img class="w-full object-cover"
            src="{{ $car->heroExterior?->hero_image_path ?? asset('img/homepage/extirior.png') }}"
            alt="{{ $car->name }}">
    </div>
    <div class="mt-2">
        <div dir="rtl" class="swiper swiperExterior">
            <div class="swiper-wrapper">
                @foreach ($car->exteriors as $exterior)
                    <div class="swiper-slide">
                        <div class="slide">
                            <img src="{{ asset($exterior->image_path) }}" alt="{{ $car->name }}" class="image" />
                            <div class="overlay"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <!-- Navigation arrows -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </div>
</div>
