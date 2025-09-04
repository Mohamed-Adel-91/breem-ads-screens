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
        <section class=" thanks_section">
            <div class="flex flex-wrap">
                <div class="w-full sm:w-1/2 text-center py-10 flex items-center  md:order-1 order-2" style="background: #B1BAC3;">
                    <div class="w-full md:w-1/2 mx-4 md:mx-auto">
                        <h3 class="trim_title">
                            <span class="">أمن علي سيارتك</span>
                        </h3>

                        <h3 class="trim_title"> لقد تم تقديم طلب التأمين الخاص بك</h3>

                        <p class="mb-2 text-lg">الرقم الطلب الخاص بك هو</p>
                        <p class="mb-2 text-lg">{{ $order->reference_number }}</p>
                        <p class="mb-2 text-lg">سيتصل بيك خدمة العملاء خلال 72 ساعة</p>

                        <div class="w-full px-2 text-center mt-6">
                            <button type="submit"
                                class="form-button mx-auto mainColor border-transparent px-20 py-2"
                                onclick="window.location.href='{{ route('web.home') }}'">الرجوع الي الصفحة الرئيسيه</button>
                        </div>
                    </div>

                </div>
                <div class="w-full md:w-1/2 flex items-center justify-center  md:order-2 order-1">
                    <span class="block w-full  h-full">
                        <img src="{{ asset('frontend/img/homepage/insurance.png') }}" alt="" class="w-full h-full object-cover" />
                    </span>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts-js')
@endpush
