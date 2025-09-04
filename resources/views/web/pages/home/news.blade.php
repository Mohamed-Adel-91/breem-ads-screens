        <section class="custom-section relative">
            <div class="container relative">
                <div class="mainNews w-full lg:w-3/4 md:mb-0 mb-4">
                    <h2 class="custom_title text-center">
                        <span> أهم الأخبار </span>
                    </h2>
                    <div class="swiper" id="news-swiper">
                        <div class="swiper-wrapper">
                            @foreach ($news as $item)
                            <div class="swiper-slide">
                                <a href="{{ route('web.news.details', [$item->id, \unicode_slug($item->title, '-')]) }}"
                                    class="News_card zoomIn">
                                    <div class="News_card_header">
                                        <span>
                                            <img src="{{ $item->thumb_url }}" alt="{{ $item->altText }}" />
                                        </span>
                                    </div>
                                    <div class="News_card_body">
                                        <h5 class="text-lg text-black mb-3 font-normal">
                                            {{ $item->title }}
                                        </h5>
                                        <p class="text-gray-500">
                                            {{ $item->short_desc }}
                                        </p>
                                        <button class="flex items-center gap-3 transition-all mb-0 hover:gap-5">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="19.534" height="10.154"
                                                viewBox="0 0 19.534 10.154">
                                                <path id="Path_7658" data-name="Path 7658"
                                                    d="M840.759,355.741a11.933,11.933,0,0,0-19.534,0,11.933,11.933,0,0,0,19.534,0Zm-9.767-2.539a2.539,2.539,0,1,1-2.538,2.539,2.539,2.539,0,0,1,2.538-2.539Zm0,1.338a1.2,1.2,0,1,1-1.2,1.2,1.2,1.2,0,0,1,1.2-1.2Zm7.493,1.2a10.156,10.156,0,0,0-14.985,0,10.156,10.156,0,0,0,14.985,0Z"
                                                    transform="translate(-821.225 -350.664)" fill="#d03b37"
                                                    fill-rule="evenodd"></path>
                                            </svg>
                                            <span>لمعرفة المزيد</span>
                                        </button>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Centered Image -->
                <div class="tallImg lg:w-1/4 lg:block hidden">
                    <p class="text-xs text-gray-400 text-right">
                        ADVERTISEMENT
                    </p>
                    <!-- second_zone [async] Shell- -->
                    <script type="text/javascript">
                        if (!window.AdButler) {
                            (function() {
                                var s = document.createElement("script");
                                s.async = true;
                                s.type = "text/javascript";
                                s.src = 'https://servedby.adfyre.co/app.js';
                                var n = document.getElementsByTagName("script")[0];
                                n.parentNode.insertBefore(s, n);
                            }());
                        }
                    </script>
                    <script type="text/javascript">
                        var AdButler = AdButler || {};
                        AdButler.ads = AdButler.ads || [];
                        var abkw = window.abkw || '';
                        var plc994382 = window.plc994382 || 0;
                        document.write('<' + 'div id="placement_994382_' + plc994382 + '"></' + 'div>');
                        AdButler.ads.push({
                            handler: function(opt) {
                                AdButler.register(188745, 994382, [300, 600], 'placement_994382_' + opt.place, opt);
                            },
                            opt: {
                                place: plc994382++,
                                keywords: abkw,
                                domain: 'servedby.adfyre.co',
                                click: 'CLICK_MACRO_PLACEHOLDER'
                            }
                        });
                    </script>

                    <!-- <img src="img/ads/3.jpg" alt="Ad Image" class="max-w-full h-full" /> -->
                </div>
                <div class="smallImg lg:w-1/4 hidden ">
                    <p class="text-xs text-gray-400 text-right">
                        ADVERTISEMENT
                    </p>
                    <script type="text/javascript">
                        if (!window.AdButler) {
                            (function() {
                                var s = document.createElement("script");
                                s.async = true;
                                s.type = "text/javascript";
                                s.src = 'https://servedby.adfyre.co/app.js';
                                var n = document.getElementsByTagName("script")[0];
                                n.parentNode.insertBefore(s, n);
                            }());
                        }
                    </script>
                    <script type="text/javascript">
                        var AdButler = AdButler || {};
                        AdButler.ads = AdButler.ads || [];
                        var abkw = window.abkw || '';
                        var plc994386 = window.plc994386 || 0;
                        document.write('<' + 'div id="placement_994386_' + plc994386 + '"></' + 'div>');
                        AdButler.ads.push({
                            handler: function(opt) {
                                AdButler.register(188745, 994386, [300, 300], 'placement_994386_' + opt.place, opt);
                            },
                            opt: {
                                place: plc994386++,
                                keywords: abkw,
                                domain: 'servedby.adfyre.co',
                                click: 'CLICK_MACRO_PLACEHOLDER'
                            }
                        });
                    </script>
                </div>
                <!-- <div class="smallImg2 lg:w-1/4 lg:hidden block">
                    <p class="text-xs text-gray-400 text-right">
                        ADVERTISEMENT
                    </p>

                </div> -->
                <div class="e-with-fixed-bg lg:w-1/4 lg:hidden block">
                    <div class="bg-wrap">
                        <div class="bg"></div>
                    </div>
                 
                </div>
            </div>
        </section>

        @push('scripts-js')
        <script>
            const newsSwiper = new Swiper("#news-swiper", {
                slidesPerView: 2.5,
                spaceBetween: 20,

                breakpoints: {
                    0: {
                        slidesPerView: 1
                    },
                    768: {
                        slidesPerView: 1.5
                    },
                    1200: {
                        slidesPerView: 2.5
                    },
                },
            });
        </script>
        @endpush
