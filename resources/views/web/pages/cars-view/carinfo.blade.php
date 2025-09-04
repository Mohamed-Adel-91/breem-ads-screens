@extends('web.layouts.master')

@push('meta')
    <!-- SEO Meta -->
<meta name="author" content="Icon Creations" />
<link rel="shortcut icon" type="image/x-icon" href="img/homepage/logo.ico" />
    <title>التوكيل | الموقع الرسمي لبيع وشراء السيارات في مصر</title>
    <meta name="description"
        content="التوكيل هو موقعك الأول لشراء وبيع السيارات الجديدة والمستعملة، مع آخر الأخبار والعروض، ودليلك الكامل لعالم السيارات." />
    <meta name="keywords"
        content="التوكيل, سيارات جديدة, سيارات مستعملة, مقارنة سيارات, أسعار السيارات, شراء سيارة, بيع سيارة" />
    <!-- Open Graph for Social Sharing -->
    <meta property="og:title" content="التوكيل | موقع السيارات الأول في مصر" />
    <meta property="og:description" content="اكتشف أحدث السيارات، قارن بين الموديلات، تابع آخر العروض والأخبار." />
    <meta property="og:image" content="https://dev-iconcreations.com/el-tawkeel/public/frontend/img/homepage/og-image.png" />
    <meta property="og:url" content="https://eltawkeel.com/" />
    <meta property="og:type" content="website" />
@endpush

@section('content')
    <!-- Main Content -->
    <main>
        <section class="custom-banner">
            <span>
                @if ($car->banner_path)
                    <img src="{{ asset($car->banner_path) }}" alt="{{ $car->name }}">
                @else
                    <img src="img/homepage/Kowmore.png" alt="{{ $car->name }}">
                @endif
            </span>
            <div class="banner_content">
                <div class="container">
                    <h2>{{ $car->brand->name }} {{ $car->name }} موديل {{ $car->year }}</h2>
                <p>{{ $car->description ?? 'يمكنك التعرف على المنتجات التي تحمل علامة ' . $car->brand->name }}</p>
                <a href="javascript:void(0)"
                    onclick="document.getElementById('car-{{ $car->id }}-term').scrollIntoView({behavior: 'smooth'});">
                    احجز الان
                </a>
                </div>
            </div>
        </section>

        <section class="tabs_Konwmore custom-section">
            <div class="container">
                <div class="flex justify-center overflow-hidden mb-6 border-b border-[#D03B37] tabs_button">
                    <button class="tab-btn px-2 py-4 font-bold w-full m-0 bg-[#D03B37] text-white active"
                        data-tab="tab1">أعرف أكتر</button>
                    <button class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center" data-tab="tab2">الشكل
                        الخارجي</button>
                    <button class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center" data-tab="tab3">الشكل
                        الداخلي</button>
                    <button class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center" data-tab="tab4">مميزات
                        السيارة</button>

                    <a @if ($car->catalog) href="{{ asset($car->catalog_path) }}" @endif download
                        class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center">
                        حمل كتيب السيارة
                    </a>
                    <a @if ($car->maintenance_schedule_pdf) href="{{ asset($car->maintenance_schedule_pdf_path) }}" @endif download
                        class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center">
                        حمل جدول الصيانات
                    </a>
                    <button
                        class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center flex gap-6 justify-center items-center"
                        data-tab="tab7" @if (!$car->view_360_degree) disabled @endif>شاهد

                        <svg xmlns="http://www.w3.org/2000/svg" width="35" height="17" viewBox="0 0 35 17"
                            fill="none">
                            <path
                                d="M27.9524 1.56177C27.9524 1.11841 28.2661 0.702393 28.771 0.702393C29.2856 0.702393 29.5857 1.14575 29.5857 1.56848C29.5857 1.98462 29.2856 2.42786 28.7742 2.42786C28.2832 2.42786 27.9524 2.02539 27.9524 1.56519V1.56177ZM27.1855 1.59375C27.1855 2.40076 27.8379 3.13037 28.7629 3.13037C29.6841 3.13037 30.3525 2.42114 30.3525 1.56519C30.3525 0.709229 29.6802 0 28.7671 0C27.9272 0 27.1855 0.623657 27.1855 1.5896V1.59375Z"
                                fill="#D2423F" />
                            <path
                                d="M22.5352 3.65686C22.5352 2.93689 22.9006 2.15039 23.8203 2.15039C24.95 2.15039 25.1274 3.18054 25.1274 3.64575V6.26001C25.1274 6.89136 24.7507 7.75537 23.8313 7.75537C22.9341 7.75537 22.5352 7.0022 22.5352 6.26001V3.65686ZM20.8848 6.2157C20.8848 8.45325 22.7124 9.20654 23.8645 9.20654C25.0054 9.20654 26.7778 8.45325 26.7778 6.2157V3.69019C26.7778 1.47461 24.9944 0.699219 23.8425 0.699219C22.8232 0.699219 20.8848 1.3418 20.8848 3.71228V6.2157Z"
                                fill="#D2423F" />
                            <path
                                d="M15.7219 6.29053C15.7219 5.39319 16.4199 4.83936 17.1509 4.83936C17.9817 4.83936 18.6021 5.48181 18.6021 6.30151C18.6021 7.12122 17.9597 7.75269 17.1399 7.75269C16.3867 7.75269 15.7219 7.17651 15.7219 6.30151V6.29053ZM16.9849 0.851562L14.8357 3.99756C14.448 4.5957 14.0938 5.29358 14.0938 6.15747C14.0938 6.77783 14.282 7.57544 14.7803 8.14038C15.301 8.81604 16.1873 9.20361 17.1953 9.20361C18.8789 9.20361 20.2524 7.92993 20.2524 6.15747C20.2524 4.35193 18.9011 3.38818 17.6494 3.38818C17.3945 3.38818 17.0845 3.46582 16.9958 3.51001C17.1177 3.33289 17.2395 3.17773 17.3503 3.0116L18.8901 0.851562H16.9849Z"
                                fill="#D2423F" />
                            <path
                                d="M9.62927 3.08093C9.65137 2.71533 9.8175 2.15039 10.5154 2.15039C11.0471 2.15039 11.3352 2.59351 11.3352 3.09192C11.3352 3.78979 10.6704 4.11108 10.1055 4.11108H9.73999V5.34058H10.4047C11.0471 5.34058 11.8114 5.70605 11.8114 6.51477C11.8114 7.20166 11.3904 7.75537 10.593 7.75537C9.7843 7.74438 9.3855 7.14624 9.34131 6.62561H7.67969C7.7019 7.0022 7.79041 7.61133 8.15601 8.10986C8.66553 8.85205 9.66248 9.20654 10.5597 9.20654C12.1659 9.20654 13.4619 8.09888 13.4619 6.63672C13.4619 6.271 13.3844 5.08582 11.8889 4.57617C12.3099 4.34363 12.9856 3.86743 12.9856 2.85925C12.9856 1.67407 12.0219 0.699219 10.582 0.699219C9.51855 0.699219 8.04517 1.29749 8.00098 3.08093H9.62927Z"
                                fill="#D2423F" />
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M22.7032 14.4089C29.6174 13.6902 34.6113 11.6279 34.6113 9.1958C34.6113 7.49268 32.1637 5.97119 28.3229 4.96484V5.64453C30.9958 6.41919 32.6417 7.47461 32.6417 8.63818C32.6417 10.4819 28.5099 12.0549 22.7032 12.6677V14.4089ZM17.3066 14.6814C17.5911 14.6814 17.8738 14.6792 18.1548 14.6748V16.9871L21.4545 13.8049L18.1548 10.6228V12.9355C17.874 12.9397 17.5912 12.9421 17.3066 12.9421C8.83728 12.9421 1.97156 11.0151 1.97156 8.63818C1.97156 7.47461 3.61737 6.41919 6.29053 5.64453V4.96484C2.44971 5.97119 0.00195312 7.49268 0.00195312 9.1958C0.00195312 12.2253 7.74951 14.6814 17.3066 14.6814Z"
                                fill="#D2423F" />
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <div class="tab-content">
                    <div id="tab1" class="tab-pane active">
                        @include('web.pages.cars-view.partials.know-more')
                    </div>
                    <div id="tab2" class="tab-pane hidden">
                        @include('web.pages.cars-view.partials.exteriors')
                    </div>
                    <div id="tab3" class="tab-pane hidden">
                        @include('web.pages.cars-view.partials.interiors')
                    </div>
                    <div id="tab4" class="tab-pane hidden">
                        @include('web.pages.cars-view.partials.car-info-features')
                    </div>
                    <div id="tab5" class="tab-pane hidden">هذا محتوى الكتيب</div>
                    <div id="tab6" class="tab-pane hidden">هذا محتوى جدول الصيانات</div>
                    <div id="tab7" class="tab-pane hidden">
                        <div class="container">
                            <div class="Tobtitle">
                                <div class="flex justify-center">
                                    @if ($car->view_360_degree)
                                        <iframe src="{{ $car->view_360_degree }}" width="100%" height="500px"
                                            frameborder="0"></iframe>
                                    @else
                                        <h2
                                            class="tab-btn px-2 py-4 font-bold w-full m-0 text-gray-700 text-center flex gap-6 justify-center items-center">
                                            شاهد
                                            <svg xmlns="http://www.w3.org/2000/svg" width="35" height="17"
                                                viewBox="0 0 35 17" fill="none">
                                                <path
                                                    d="M27.9524 1.56177C27.9524 1.11841 28.2661 0.702393 28.771 0.702393C29.2856 0.702393 29.5857 1.14575 29.5857 1.56848C29.5857 1.98462 29.2856 2.42786 28.7742 2.42786C28.2832 2.42786 27.9524 2.02539 27.9524 1.56519V1.56177ZM27.1855 1.59375C27.1855 2.40076 27.8379 3.13037 28.7629 3.13037C29.6841 3.13037 30.3525 2.42114 30.3525 1.56519C30.3525 0.709229 29.6802 0 28.7671 0C27.9272 0 27.1855 0.623657 27.1855 1.5896V1.59375Z"
                                                    fill="#D2423F" />
                                                <path
                                                    d="M22.5352 3.65686C22.5352 2.93689 22.9006 2.15039 23.8203 2.15039C24.95 2.15039 25.1274 3.18054 25.1274 3.64575V6.26001C25.1274 6.89136 24.7507 7.75537 23.8313 7.75537C22.9341 7.75537 22.5352 7.0022 22.5352 6.26001V3.65686ZM20.8848 6.2157C20.8848 8.45325 22.7124 9.20654 23.8645 9.20654C25.0054 9.20654 26.7778 8.45325 26.7778 6.2157V3.69019C26.7778 1.47461 24.9944 0.699219 23.8425 0.699219C22.8232 0.699219 20.8848 1.3418 20.8848 3.71228V6.2157Z"
                                                    fill="#D2423F" />
                                                <path
                                                    d="M15.7219 6.29053C15.7219 5.39319 16.4199 4.83936 17.1509 4.83936C17.9817 4.83936 18.6021 5.48181 18.6021 6.30151C18.6021 7.12122 17.9597 7.75269 17.1399 7.75269C16.3867 7.75269 15.7219 7.17651 15.7219 6.30151V6.29053ZM16.9849 0.851562L14.8357 3.99756C14.448 4.5957 14.0938 5.29358 14.0938 6.15747C14.0938 6.77783 14.282 7.57544 14.7803 8.14038C15.301 8.81604 16.1873 9.20361 17.1953 9.20361C18.8789 9.20361 20.2524 7.92993 20.2524 6.15747C20.2524 4.35193 18.9011 3.38818 17.6494 3.38818C17.3945 3.38818 17.0845 3.46582 16.9958 3.51001C17.1177 3.33289 17.2395 3.17773 17.3503 3.0116L18.8901 0.851562H16.9849Z"
                                                    fill="#D2423F" />
                                                <path
                                                    d="M9.62927 3.08093C9.65137 2.71533 9.8175 2.15039 10.5154 2.15039C11.0471 2.15039 11.3352 2.59351 11.3352 3.09192C11.3352 3.78979 10.6704 4.11108 10.1055 4.11108H9.73999V5.34058H10.4047C11.0471 5.34058 11.8114 5.70605 11.8114 6.51477C11.8114 7.20166 11.3904 7.75537 10.593 7.75537C9.7843 7.74438 9.3855 7.14624 9.34131 6.62561H7.67969C7.7019 7.0022 7.79041 7.61133 8.15601 8.10986C8.66553 8.85205 9.66248 9.20654 10.5597 9.20654C12.1659 9.20654 13.4619 8.09888 13.4619 6.63672C13.4619 6.271 13.3844 5.08582 11.8889 4.57617C12.3099 4.34363 12.9856 3.86743 12.9856 2.85925C12.9856 1.67407 12.0219 0.699219 10.582 0.699219C9.51855 0.699219 8.04517 1.29749 8.00098 3.08093H9.62927Z"
                                                    fill="#D2423F" />
                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                    d="M22.7032 14.4089C29.6174 13.6902 34.6113 11.6279 34.6113 9.1958C34.6113 7.49268 32.1637 5.97119 28.3229 4.96484V5.64453C30.9958 6.41919 32.6417 7.47461 32.6417 8.63818C32.6417 10.4819 28.5099 12.0549 22.7032 12.6677V14.4089ZM17.3066 14.6814C17.5911 14.6814 17.8738 14.6792 18.1548 14.6748V16.9871L21.4545 13.8049L18.1548 10.6228V12.9355C17.874 12.9397 17.5912 12.9421 17.3066 12.9421C8.83728 12.9421 1.97156 11.0151 1.97156 8.63818C1.97156 7.47461 3.61737 6.41919 6.29053 5.64453V4.96484C2.44971 5.97119 0.00195312 7.49268 0.00195312 9.1958C0.00195312 12.2253 7.74951 14.6814 17.3066 14.6814Z"
                                                    fill="#D2423F" />
                                            </svg>
                                        </h2>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <!-- Popup Section -->
            <div class="popupsection" id="videoPopup">
                <div class="popupsection_close" id="closeButton" onclick="closeVideoPopup()">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="popupsection_box">
                    <iframe id="videoIframe" width="100%" height="100%" src="" title="YouTube video player"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen>
                    </iframe>
                </div>
            </div>
        </section>
        <div id="car-{{ $car->id }}-term" class="car-term-card">
            @include('web.pages.cars-view.partials.one-car-term-cards', ['cars' => [$car]])
        </div>
    </main>
@endsection
@push('scripts-css')
    <style>
        html {
            scroll-behavior: smooth;
        }
    </style>
@endpush
@push('scripts-js')
    <script>
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (btn.hasAttribute('download') || !btn.getAttribute('data-tab')) {
                    return;
                }
                tabButtons.forEach(b => {
                    b.classList.remove('bg-[#D03B37]', 'text-white');
                    b.classList.add('bg-white', 'text-gray-700');
                });
                btn.classList.remove('bg-white', 'text-gray-700');
                btn.classList.add('bg-[#D03B37]', 'text-white');
                tabPanes.forEach(p => p.classList.add('hidden'));
                const target = btn.getAttribute('data-tab');
                const selectedTab = document.getElementById(target);
                if (selectedTab) {
                    selectedTab.classList.remove('hidden');
                }
            });
        });
        if (tabButtons.length > 0) {
            const firstTab = document.getElementById(tabButtons[0].getAttribute('data-tab'));
            if (firstTab) {
                firstTab.classList.remove('hidden');
            }
        }

        try {
            const swiperExterior = new Swiper(".swiperExterior", {
                // centeredSlides: true,

                loop: true,
                slidesPerView: 4,
                spaceBetween: 10,
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
                autoplay: {
                    delay: 5000,
                    disableOnInteraction: false,
                },
                speed: 800,
                breakpoints: {
                    0: {
                        slidesPerView: 1,
                    },
                    640: {
                        slidesPerView: 2,
                    },
                    1024: {
                        slidesPerView: 3,
                    },
                    1280: {
                        slidesPerView: 4,
                    },
                },
            });
        } catch (error) {
            console.error("Failed to initialize Swiper Exterior", error);
        }
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
                button.classList.add('active');
                document.getElementById(button.dataset.tab).classList.add('active');
            });
        });
    </script>
@endpush
