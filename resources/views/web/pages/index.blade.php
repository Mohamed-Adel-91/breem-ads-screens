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
        <!-- Slider -->
        @include('web.pages.home.slider')

        <!-- Menu Section -->
        @include('web.pages.home.menu-section')

        <!-- New Cars Section -->
        @include('web.pages.cars-view.partials.car-cards')

        <!-- Compare Section -->
        @include('web.pages.home.compare-section')

        <!-- installment Services Section -->
        @include('web.pages.home.services')

        <!-- News Section -->
        @include('web.pages.home.news')

        <!-- Video Section -->
        @include('web.pages.home.videos')
    </main>
@endsection

@push('scripts-js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const videoCards = document.querySelectorAll(
                ".home_video_Section_cardvideo, .home_video_Section_smallcardvideo"
            );
            const popup = document.getElementById("videoPopup");
            const closeBtn = document.getElementById("closeButton");
            const iframe = document.getElementById("videoIframe");

            videoCards.forEach((card) => {
                card.addEventListener("click", function(e) {
                    e.preventDefault();
                    const videoUrl = this.getAttribute("data-video-url");
                    if (videoUrl) {
                        iframe.src = videoUrl;
                        popup.classList.add("active");
                        document.body.style.overflow = "hidden";
                    }
                });
            });
            closeBtn.addEventListener("click", function() {
                closePopup();
            });
            popup.addEventListener("click", function(e) {
                if (e.target === popup) {
                    closePopup();
                }
            });
            document.addEventListener("keydown", function(e) {
                if (
                    e.key === "Escape" &&
                    popup.classList.contains("active")
                ) {
                    closePopup();
                }
            });

            function closePopup() {
                popup.classList.remove("active");
                iframe.src = "";
                document.body.style.overflow = "auto";
            }
        });
    </script>
@endpush
