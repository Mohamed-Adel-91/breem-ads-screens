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
        <section class="menusection custom-section">
            <div class="container">
                <h2 class="custom_title">
                    <span>مرحباً بك في التوكيل</span>
                </h2>
             <ul class="flex flex-wrap justify-between lg:gap-7">
                    <li class="itemmenu">
                        <a href="#">

                            <img src="img/global/4.png" alt="" class="greyImg" />
                            <img src="img/global/4red.png" class="redImg" alt="" />

                            <h5 class="second_title">افضل نتائج بحث</h5>
                        </a>
                    </li>
                    <li class="itemmenu">
                        <a href="#">

                            <img src="img/global/1.png" alt="" class="greyImg" />
                            <img src="img/global/1red.png" class="redImg" alt="" />
                            <h5 class="second_title">اختار سيارتك</h5>
                        </a>
                    </li>
                    <li class="itemmenu">
                        <a href="#">
                            <img src="img/global/2.png" alt="" class="greyImg" />
                            <img src="img/global/2red.png" class="redImg" alt="" />
                            <h5 class="second_title">احجز</h5>
                        </a>
                    </li>
                    <li class="itemmenu">
                        <a href="#">
                            <img src="img/global/5.png" alt="" class="greyImg" />
                            <img src="img/global/5red.png" class="redImg" alt="" />

                            <h5 class="second_title">قسط سيارتك</h5>
                        </a>
                    </li>
                    <li class="itemmenu">
                        <a href="#">
                            <img src="img/global/6.png" alt="" class="greyImg" />
                            <img src="img/global/6red.png" class="redImg" alt="" />

                            <h5 class="second_title">امن علي السياره</h5>
                        </a>
                    </li>
                    <li class="itemmenu">
                        <a href="#">
                            <img src="img/global/3.png" alt="" class="greyImg"/>
                            <img src="img/global/3red.png" class="redImg" alt="" />


                            <h5 class="second_title">امتلك</h5>
                        </a>
                    </li>
                </ul>
            </div>
        </Section>
        <section class="login_section faqs">
            <div class="container">
                <h3 class="englishTitle">FAQs</h3>
                <h2 class="custom_title mb-5">
                    <span>الأسئلة الشائعة</span>
                </h2>
                <h4 class="greyTitle">تواصل معنا - نحن هنا لمساعدتك</h4>
                <div class="flex-div lg:mx-0 lg:-mr-2 mt-8">
                    <div class="w-full lg:w-1/2 lg:pl-6 lg:mb-0 mb-8">
                        <div id="accordion-collapse" data-accordion="collapse">
                            <div class="custAccordion">
                                <h2 id="accordion-collapse-heading-1">
                                    <button type="button"
                                        class="flex items-center justify-between w-full p-5 font-medium rounded-t-xl gap-3"
                                        data-accordion-target="#accordion-collapse-body-1" aria-expanded="true"
                                        aria-controls="accordion-collapse-body-1">
                                        <span>لماذا التوكيل ؟</span>
                                        <img src="img/faq/minus.svg" alt="">
                                    </button>
                                </h2>
                                <div id="accordion-collapse-body-1" class="hidden"
                                    aria-labelledby="accordion-collapse-heading-1">
                                    <div class="p-5 pt-0">
                                        <p class="mb-2">
                                            لوريم ايبسوم هو نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه
                                            وضع
                                            والنصوص بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكل,لوريم ايبسوم
                                            هو
                                            نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع</p>
                                    </div>
                                </div>
                            </div>
                            <div class="custAccordion">
                                <h2 id="accordion-collapse-heading-2">
                                    <button type="button"
                                        class="flex items-center justify-between w-full p-5 font-medium  gap-3"
                                        data-accordion-target="#accordion-collapse-body-2" aria-expanded="false"
                                        aria-controls="accordion-collapse-body-2">
                                        <span>طرق التقسيط ؟</span>
                                        <img src="img/faq/plus.svg" alt="">
                                    </button>
                                </h2>
                                <div id="accordion-collapse-body-2" class="hidden"
                                    aria-labelledby="accordion-collapse-heading-2">
                                    <div class="p-5 pt-0 ">
                                        <p class="mb-2">لوريم ايبسوم هو نموذج افتراضي يوضع
                                            في التصاميم لتعرض على العميل ليتصور طريقه وضع
                                            والنصوص بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكل,لوريم ايبسوم
                                            هو
                                            نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع</p>
                                    </div>
                                </div>
                            </div>
                            <div class="custAccordion">
                                <h2 id="accordion-collapse-heading-3">
                                    <button type="button"
                                        class="flex items-center justify-between w-full p-5 font-medium gap-3"
                                        data-accordion-target="#accordion-collapse-body-3" aria-expanded="false"
                                        aria-controls="accordion-collapse-body-3">
                                        <span>التأمين على السيارات ؟</span>
                                        <img src="img/faq/plus.svg" alt="">
                                    </button>
                                </h2>
                                <div id="accordion-collapse-body-3" class="hidden"
                                    aria-labelledby="accordion-collapse-heading-3">
                                    <div class="p-5 pt-0 ">
                                        <p class="mb-2">لوريم ايبسوم هو نموذج افتراضي يوضع
                                            في التصاميم لتعرض على العميل ليتصور طريقه وضع
                                            والنصوص بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكل,لوريم ايبسوم
                                            هو
                                            نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع</p>
                                    </div>
                                </div>
                            </div>
                            <div class="custAccordion">
                                <h2 id="accordion-collapse-heading-4">
                                    <button type="button"
                                        class="flex items-center justify-between w-full p-5 font-medium gap-3"
                                        data-accordion-target="#accordion-collapse-body-4" aria-expanded="false"
                                        aria-controls="accordion-collapse-body-4">
                                        <span> مراكز الخدمات ؟</span>
                                        <img src="img/faq/plus.svg" alt="">
                                    </button>
                                </h2>
                                <div id="accordion-collapse-body-4" class="hidden"
                                    aria-labelledby="accordion-collapse-heading-4">
                                    <div class="p-5 pt-0 ">
                                        <p class="mb-2">لوريم ايبسوم هو نموذج افتراضي يوضع
                                            في التصاميم لتعرض على العميل ليتصور طريقه وضع
                                            والنصوص بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكل,لوريم ايبسوم
                                            هو
                                            نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 lg:pr-6">
                        <div class="contactUs">
                            <h2 class="contactUs_title">
                                إرسل لنا سؤال وسوف يتواصل فريقنا معك
                            </h2>
                            <form action="" class="flex-div" enctype="multipart/form-data">
                                @csrf
                                <div class="w-full lg:w-1/2 p-2 pb-0">
                                    <div class="form_group">
                                        <input name="name" class="form_group_control " type="text"
                                            placeholder="الأسم كامل ">
                                        <!-- <label class="form_group_label">الأسم كامل</label> -->
                                    </div>
                                </div>
                                <div class="w-full lg:w-1/2 p-2 pb-0">
                                    <div class="form_group">
                                        <input name="email" class="form_group_control " type="email"
                                            placeholder="الإيميل ">
                                        <!-- <label class="form_group_label">الإيميل</label> -->
                                    </div>
                                </div>
                                <div class="w-full lg:w-1/2 p-2 pb-0">
                                    <div class="form_group">
                                        <input name="phone" class="form_group_control " type="tel"
                                            placeholder=" رقم الموبايل">
                                        <!-- <label class="form_group_label">رقم الموبايل</label> -->
                                    </div>
                                </div>
                                <div class="w-full lg:w-1/2 p-2 pb-0">
                                    <div class="form_group">
                                        <div class=" branchesSection_map_select">
                                            <div class="custom-select-wrapper">
                                                <select class="custom-select">
                                                    <option selected disabled> المحافظة</option>
                                                    <option value="branch1">الفرع 1</option>
                                                    <option value="branch2">الفرع 2</option>
                                                    <option value="branch3">الفرع 3</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="w-full">
                                    <div class="form_group p-2 pt-0 pb-0">
                                        <textarea placeholder="أدخل السؤال"></textarea>
                                        <!-- <label class="form_group_label">أدخل السؤال</label> -->
                                    </div>
                                </div>
                                <div class="w-full">
                                    <div class="button flex items-center justify-center">
                                        <button class="form-button undefined">ارسال</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
@push('scripts-js')
    <script>
        // Sidebar functionality
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        function toggleSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('open');
            }
        }
        if (overlay) {
            overlay.addEventListener('click', () => {
                document.querySelector('.sidebar')?.classList.remove('open');
                overlay.classList.remove('open');
            });
        }
    </script>
@endpush
