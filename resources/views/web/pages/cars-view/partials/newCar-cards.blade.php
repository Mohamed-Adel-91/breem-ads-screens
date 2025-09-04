<section class="SectionNewCar custom-section">
    <div class="container">
        <h2 class="sectiontitle custom_title">
            <span>السيارات الجديدة</span>
        </h2>
        <div class="flex flex-wrap w-full">
            <!-- Car cards loop -->
            @foreach ($cars as $car)
                <div class="px-4 w-full md:w-1/2 lg:w-2/6 mb-4">
                    <!-- CarCard Component -->
                    <div class="carcard">
                        <div class="carcard_header">
                            <!-- Main Image Slider -->
                            <div class="swiper" id="car-{{ $car->id }}-image-swiper">
                                <div class="swiper-wrapper">
                                    @forelse ($car->colors as $color)
                                        @if ($color->pivot->image_path)
                                            <div class="swiper-slide">
                                                <span class="spancardheader">
                                                    <img src="{{ asset($color->pivot->image_path) }}"
                                                        alt="{{ $car->name }} - {{ $color->name }}"
                                                        class="imgcardheader" />
                                                </span>
                                            </div>
                                        @endif
                                    @empty
                                        @if ($car->image_path)
                                            <div class="swiper-slide">
                                                <span class="spancardheader">
                                                    <img src="{{ $car->image_path }}" alt="{{ $car->name }}"
                                                        class="imgcardheader" />
                                                </span>
                                            </div>
                                        @endif
                                    @endforelse
                                </div>
                            </div>
                            <!-- Color Slider -->
                            <div class="flex justify-end items-center -mx-2">
                                <div class="w-1/2 px-2">
                                    <p class="mb-0">
                                        الألوان المتاحة للحجز
                                    </p>
                                </div>
                                <div class="w-1/2 px-2">
                                    <div class="swiper colorslider" id="car-{{ $car->id }}-color-swiper">
                                        <div class="swiper-wrapper">
                                            @forelse ($car->colors as $color)
                                                <div class="swiper-slide colorbox" data-color-id="{{ $color->id }}">
                                                    <span class="spancardcolor">
                                                        <img src="{{ asset($color->image_path) }}"
                                                            alt="{{ $car->name }} - {{ $color->name }}"
                                                            class="imgcardcolor" />
                                                    </span>
                                                </div>
                                            @empty
                                                <div class="swiper-slide colorbox">
                                                    <span class="spancardcolor">
                                                        <img src="img/homepage/color1.png" alt="color"
                                                            class="imgcardcolor" />
                                                    </span>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- CarBody Component -->
                    <div class="carBody">
                        <!-- Variant Slider -->
                        <div class="swiper" id="car-{{ $car->id }}-variant-swiper">
                            <div class="swiper-wrapper">
                                @forelse ($car->terms as $term)
                                    <div class="swiper-slide">
                                        <div class="header">
                                            <h3>
                                                {{ $car->brand->name }} - {{ $car->name }}
                                                <span>{{ $term->term_name }}</span>
                                            </h3>
                                            <div class="">
                                                <p class="price">
                                                    {{ $term->price !== null ? number_format((float) $term->price, 0, '.', ',') . ' جنيه مصري' : '—' }}
                                                </p>
                                                @if ($term->inventory <= 0)
                                                    <span class="emptyCar opacity-50 cursor-not-allowed">
                                                        لقد نفد المخزون المتاح من تلك الفئة
                                                    </span>
                                                @else
                                                    <p class="remaining">
                                                        باقي {{ $term->inventory }} سيارات فقط للحجز
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="actions">
                                            @if ($term->inventory <= 0)
                                                <a href="#" class="reserve-link opacity-50 cursor-not-allowed">
                                                    <button class="reserve">
                                                        <img src="img/homepage/compare.svg" alt="card" />
                                                        غير متاح
                                                    </button>
                                                </a>
                                            @else
                                                <a href="{{ route('web.booking', ['id' => $car->id, 'term_id' => $term->id]) }}"
                                                    class="reserve-link"
                                                    data-base-url="{{ route('web.booking', ['id' => $car->id, 'term_id' => $term->id]) }}">
                                                    <button class="reserve">
                                                        <img src="img/homepage/compare.svg" alt="card" />
                                                        احجز
                                                    </button>
                                                </a>
                                            @endif
                                            <a href="{{ route('web.comparison', ['id' => $car->id]) }}">
                                                <button class="compare">
                                                    <img src="img/homepage/doublearrow.svg" alt="arrow" />

                                                    قارن
                                                </button>
                                            </a>
                                            <a
                                                href="{{ route('web.cars.carinfo', ['id' => $car->id, \unicode_slug($car->name, '-')]) }}">
                                                <button class="more">
                                                    <img src="img/homepage/view.svg" alt="svg view" />
                                                    اعرف أكثر
                                                </button>
                                            </a>
                                        </div>
                                        <ul class="specs">
                                            @forelse ($term->specs as $spec)
                                                @if ($spec->status == 1)
                                                    <li>
                                                        <span class="bullet"></span>
                                                        {{ $spec->value }}
                                                    </li>
                                                @endif
                                            @empty
                                                <li>
                                                    <span class="bullet"></span>
                                                    لم يتم تحميل مميزات
                                                </li>
                                            @endforelse
                                        </ul>
                                    </div>
                                @empty
                                    <div class="swiper-slide">
                                        <div class="header">
                                            <h3>
                                                {{ $car->brand->name }} - {{ $car->name }}
                                            </h3>
                                        </div>
                                        لا يوجد فئات مضافة حتي الان
                                    </div>
                                @endforelse
                            </div>
                        </div>
                        <!-- Variants Buttons -->
                        <div class="variants">
                            <span>فئات السيارة:</span>
                            <div class="flex flex-wrap">
                                @forelse ($car->terms as $term)
                                    <button class="variantBtn {{ $loop->first ? 'active' : '' }}">
                                        {{ $term->term_name }}
                                    </button>
                                @empty
                                    لا يوجد فئات مضافة حتي الان
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

@push('scripts-js')
    <script>
        const carsSwiper = new Swiper("#cars-swiper", {
            // centeredSlides: true,
            autoplay: true,
            loop: true,
            slidesPerView: 3,
            spaceBetween: 20,
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
            breakpoints: {
                0: {
                    slidesPerView: 1
                },
                768: {
                    slidesPerView: 2
                },
                1200: {
                    slidesPerView: 3
                },
            },
        });

        function initializeCarSliders(carId) {
            const imageSwiperElement = document.querySelector(
                `#car-${carId}-image-swiper`
            );
            const colorSwiperElement = document.querySelector(
                `#car-${carId}-color-swiper`
            );
            const variantSwiperElement = document.querySelector(
                `#car-${carId}-variant-swiper`
            );
            if (
                !imageSwiperElement ||
                !colorSwiperElement ||
                !variantSwiperElement
            ) {
                console.warn(`Sliders for car-${carId} not found.`);
                return;
            }
            const colorBoxes = document.querySelectorAll(
                `#car-${carId}-color-swiper .colorbox`
            );
            const reserveLinks = document.querySelectorAll(
                `#car-${carId}-variant-swiper .reserve-link`
            );
            let selectedColorId = colorBoxes[0] ?
                colorBoxes[0].dataset.colorId :
                null;

            const updateBookingLinks = () => {
                if (!selectedColorId) return;
                reserveLinks.forEach((link) => {
                    const baseUrl = link.dataset.baseUrl;
                    link.href = `${baseUrl}&color_id=${selectedColorId}`;
                });
            };
            const imageSwiper = new Swiper(`#car-${carId}-image-swiper`, {
                // centeredSlides: true,

                slidesPerView: 1,
                spaceBetween: 0,
                on: {
                    slideChange: function() {
                        const colorSwiper = colorSwiperElement.swiper;
                        if (colorSwiper) {
                            colorSwiper.slideTo(this.activeIndex);
                            const colorBoxes = document.querySelectorAll(
                                `#car-${carId}-color-swiper .colorbox`
                            );
                            colorBoxes.forEach((box, index) => {
                                box.classList.toggle(
                                    "active",
                                    index === this.activeIndex
                                );
                            });
                            selectedColorId = colorBoxes[this.activeIndex] ?
                                colorBoxes[this.activeIndex].dataset.colorId :
                                null;
                            updateBookingLinks();
                        }
                    },
                },
            });
            const colorSwiper = new Swiper(`#car-${carId}-color-swiper`, {
                // centeredSlides: true,

                slidesPerView: 4,
                spaceBetween: 0,
                watchOverflow: true,
            });

            colorBoxes.forEach((box, index) => {
                box.addEventListener("click", () => {
                    imageSwiper.slideTo(index);
                    colorSwiper.slideTo(index);
                    colorBoxes.forEach((b) => b.classList.remove("active"));
                    box.classList.add("active");
                    selectedColorId = box.dataset.colorId;
                    updateBookingLinks();
                });
            });

            if (colorBoxes[0]) {
                colorBoxes[0].classList.add("active");
                updateBookingLinks();
            }
            const variantSwiper = new Swiper(
                `#car-${carId}-variant-swiper`, {
                    // centeredSlides: true,

                    slidesPerView: 1,
                    spaceBetween: 10,
                    on: {
                        slideChange: function() {
                            const variantButtons =
                                document.querySelectorAll(
                                    `#car-${carId}-variant-swiper + .variants .variantBtn`
                                );
                            variantButtons.forEach((btn, index) => {
                                btn.classList.toggle(
                                    "active",
                                    index === this.activeIndex
                                );
                            });
                        },
                    },
                }
            );
            document
                .querySelectorAll(
                    `#car-${carId}-variant-swiper + .variants .variantBtn`
                )
                .forEach((button, index) => {
                    button.addEventListener("click", () => {
                        variantSwiper.slideTo(index);
                        document
                            .querySelectorAll(
                                `#car-${carId}-variant-swiper + .variants .variantBtn`
                            )
                            .forEach((btn) =>
                                btn.classList.remove("active")
                            );
                        button.classList.add("active");
                    });
                });
        }
        [
            @foreach ($cars as $car)
                {{ $car->id }},
            @endforeach
        ].forEach((carId) => initializeCarSliders(carId));
    </script>
@endpush
