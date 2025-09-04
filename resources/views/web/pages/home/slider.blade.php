        <section class="banner">
            <div class="swiper swiperContainerhome">
                <div class="swiper-wrapper">
          <div class="swiper-slide">
                        <div class="slide">
                            <img src="img/homepage/slide2.jpg" alt="banner 1" class="image" />

                            <div class="overlay"></div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide">
                             <img src="img/homepage/Desktop.png" alt="banner 3" class="image lg:block hidden" />
                            <img src="img/homepage/Tablet.png" alt="banner 3"
                                class="image lg:hidden md:block hidden" />
                            <img src="img/homepage/Mobile1.png" alt="banner 3" class="image md:hidden block" />
                            <div class="overlay"></div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide">
                            <img src="img/homepage/slide3.jpg" alt="banner 3" class="image lg:block hidden" />
                            <img src="img/homepage/tablet5.png" alt="banner 3"
                                class="image lg:hidden md:block hidden" />
                            <img src="img/homepage/mobile5.png" alt="banner 3" class="image md:hidden block" />
                            <div class="overlay"></div>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="slide">
                            <img src="img/homepage/slide4.jpg" alt="banner 3" class="image lg:block hidden" />
                            <img src="img/homepage/tablet3.png" alt="banner 3"
                                class="image lg:hidden md:block hidden" />
                            <img src="img/homepage/mobile3.png" alt="banner 3" class="image md:hidden block" />
                            <div class="overlay"></div>
                        </div>
                    </div>

                </div>

                <!-- Navigation arrows -->
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </section>

        @push('scripts-js')
    <script>
      const swiper = new Swiper(".swiperContainerhome", {
                loop: true,
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                speed: 1000,
            });
    </script>
@endpush