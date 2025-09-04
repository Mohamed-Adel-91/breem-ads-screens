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
        <section class="banner">
            <div class="swiper swiperContainerhome">

                @if ($hasSlides)
                    <div class="swiper-wrapper">
                        @foreach ($slides as $i => $img)
                            <div class="swiper-slide">
                                <div class="slide">
                                    <img src="{{ $img }}" alt="{{ $brand->name }} slide {{ $i + 1 }}"
                                        class="image" />
                                    <div class="overlay"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <!-- Navigation arrows -->
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                @elseif($fallback)
                    <div class="swiper-slide">
                        <div class="slide">
                            <img src="{{ $fallback }}" alt="{{ $brand->name }} banner" class="image" />
                            <div class="overlay"></div>
                        </div>
                    </div>
                @else
                    <div class="swiper-slide">
                        <div class="slide">
                            <img src="{{ asset('img/homepage/banner2.png') }}" alt="banner" class="image" />
                            <div class="overlay"></div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="container">
                <div class="py-16 relative">
                    <div class="banner_brand_logo">
                        <span class="w-full h-full">
                            <img class="w-full h-full object-contain"src="{{ asset($brand->logo_path) }}" alt="">
                        </span>
                    </div>
                    <h5 class="text-center w-full lg:w-2/3 mx-auto">{{ $brand->description }}</h5>
                </div>
            </div>
        </section>
        <section class="SectionNewCar custom-section">
            <div class="container">
                <!-- Main Swiper for Cars -->
                @include('web.pages.cars-view.partials.newCar-cards', ['cars' => $brand->cars])
            </div>
        </section>
    </main>
@endsection
