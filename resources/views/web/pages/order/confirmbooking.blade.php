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
    <main>
        <section class="custom-section confirm_booking">
            <div class="banner_section_background"></div>

            <div class="container relative z-10">
                <div class="banner_section_logo">
                    <img src="{{ $brandLogo ?? asset('img/bookcar/logo_car.png') }}"
                        alt="{{ $bookingCarClone->car_brand_name ?? '' }}">
                </div>

                <h3 class="Car_banner_title">
                    <span class="span1">3</span>
                    <span>تأكيد البيانات</span>
                </h3>

                <div class="flex flex-wrap  w-full pt-8">
                    <div class="w-1/3 px-2 ">
                        <h2 class=" text-xl font-bold text-darkmb-4">
                            {{ $bookingCarClone->car_brand_name ?? '' }} - {{ $bookingCarClone->car_model_name ?? '' }}
                        </h2>
                        @if (!empty($order->reference_number))
                            <p class="text-white/80 text-sm">رقم المرجع: <span
                                    class="font-semibold">{{ $order->reference_number }}</span></p>
                        @endif
                        <p class="text-white/80 text-sm">الحالة:
                            <span class="font-semibold">
                                {{ method_exists($order->status, 'description') ? $order->status->description : 'قيد المراجعة' }}
                            </span>
                        </p>
                    </div>
                    <div class="w-2/3 px-2 relative car_imagee">
                        <img src="{{ $firstColorImage ?? asset('img/bookcar/carr_image.png') }}"
                            alt="{{ $bookingCarClone->car_model_name ?? '' }}">
                    </div>
                </div>
            </div>
        </section>

        <section class="custom-section card_confirm">
            <div class="container">
                <div class="text-center mb-4">
                    <h5 class="text-md mb-3">سوف يتم تسليم السيارة خلال 15 يوم</h5>
                </div>

                <div class="flex flex-wrap" style="row-gap: 1rem;">

                    <div class="w-full sm:w-1/2 lg:w-1/3 px-2 ">
                        <div class="bg-white rounded-md shadow-md p-4 h-full min-h-[100%]">
                            <h5 class="mainColor text-center mb-4">بيانات العميل</h5>
                            <div class="flex-div">
                                @if ($isCash)
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">الإسم</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->cash_full_name }}</p>
                                    </div>
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">رقم المحمول</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->cash_phone_number }}</p>
                                    </div>
                                    @if ($order->cash_individual_email)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">الإيميل</h5>
                                            <p class="text-gray-500 text-sm">{{ $order->cash_individual_email }}</p>
                                        </div>
                                    @endif
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">رقم الهوية</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->cash_national_id }}</p>
                                    </div>
                                @elseif($isIndiv)
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">الإسم</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->installment_full_name }}</p>
                                    </div>
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">رقم المحمول</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->installment_phone_number }}</p>
                                    </div>
                                    @if ($order->installment_individual_email)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">الإيميل</h5>
                                            <p class="text-gray-500 text-sm">{{ $order->installment_individual_email }}</p>
                                        </div>
                                    @endif
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">رقم الهوية</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->installment_national_id }}</p>
                                    </div>
                                @elseif($isComp)
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">إسم الشركة</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->installment_company_name }}</p>
                                    </div>
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">هاتف الممثل القانوني</h5>
                                        <p class="text-gray-500 text-sm">
                                            {{ $order->installment_legal_representative_phone_number }}</p>
                                    </div>
                                    @if ($order->installment_company_email)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">الإيميل</h5>
                                            <p class="text-gray-500 text-sm">{{ $order->installment_company_email }}</p>
                                        </div>
                                    @endif
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">السجل التجاري</h5>
                                        <p class="text-gray-500 text-sm">
                                            {{ $order->installment_commercial_registration_number }}</p>
                                    </div>
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">البطاقة الضريبية</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->installment_tax_card_number }}</p>
                                    </div>
                                @endif

                                @if (!empty($order->branch_name))
                                    <div class="w-full px-2 mb-3">
                                        <h5 class="font-bold text-black">الفرع المختار</h5>
                                        <p class="text-gray-500 text-sm">{{ $order->branch_name }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="w-full sm:w-1/2 lg:w-1/3 px-2 ">
                        <div class="bg-white rounded-md shadow-md p-4 h-full min-h-[100%]">
                            <h5 class="mainColor text-center mb-4">بيانات السيارة</h5>
                            <div class="flex-div">
                                <div class="w-1/2 px-2 mb-3">
                                    <h5 class="font-bold text-black">النوع</h5>
                                    <p class="text-gray-500 text-sm">{{ $bookingCarClone->car_brand_name ?? '' }}</p>
                                </div>
                                <div class="w-1/2 px-2 mb-3">
                                    <h5 class="font-bold text-black">الموديل/الفئة</h5>
                                    <p class="text-gray-500 text-sm">
                                        {{ $bookingCarClone->car_model_name . ' - ' . $bookingCarClone->car_term_name ?? '' }}
                                    </p>
                                </div>
                                <div class="w-1/2 px-2 mb-3">
                                    <h5 class="font-bold text-black">سعر السيارة</h5>
                                    <p class="text-gray-500 text-sm">
                                        {{ isset($bookingCarClone->price) ? number_format($bookingCarClone->price) . ' جنيه مصري' : '' }}
                                    </p>
                                </div>
                                <div class="w-1/2 px-2 mb-3">
                                    <h5 class="font-bold text-black">المقدم</h5>
                                    <p class="text-gray-500 text-sm">
                                        {{ isset($bookingCarClone->reservation_amount) ? number_format($bookingCarClone->reservation_amount) . ' جنيه مصري' : '' }}
                                    </p>
                                </div>
                                <div class="w-1/2 px-2 mb-3">
                                    <h5 class="font-bold text-black">اللون المفضل الأول</h5>
                                    <p class="text-gray-500 text-sm">{{ $firstColorName }}</p>
                                </div>
                                <div class="w-1/2 px-2 mb-3">
                                    <h5 class="font-bold text-black">اللون المفضل الثاني</h5>
                                    <p class="text-gray-500 text-sm">{{ $secondColorName }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-full sm:w-1/2 lg:w-1/3 px-2 ">
                        <div class="bg-white rounded-md shadow-md p-4 h-full min-h-[100%]">
                            <h5 class="mainColor text-center mb-4">بيانات الدفع</h5>
                            <div class="flex-div">

                                <div class="w-full px-2 mb-3">
                                    <h5 class="font-bold text-black">نظام الدفع</h5>
                                    <p class="text-gray-500 text-sm">{{ $paymentLabel }}</p>
                                </div>
                                <div class="w-full px-2 mb-3">
                                    <h5 class="font-bold text-black">
                                        مبلغ الحجز المطلوب دفعه لتأكيد طلبك

                                    </h5>
                                    <p class="text-2xl font-bold text-green-600">
                                        {{ number_format($order->reservation_amount) }} ج.م</p>
                                </div>




                                @if (!$isCash)
                                    <div class="w-1/2 px-2 mb-3">
                                        <h5 class="font-bold text-black">نوع العميل</h5>
                                        <p class="text-gray-500 text-sm">{{ $isIndiv ? 'أفراد' : 'شركات' }}</p>
                                    </div>

                                    @if ($order->bank_id)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">البنك</h5>
                                            <p class="text-gray-500 text-sm">{{ optional($order->bank)->name }}</p>
                                        </div>
                                    @endif

                                    @if ($order->installment_duration)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">مدة التقسيط</h5>
                                            <p class="text-gray-500 text-sm">{{ $order->installment_duration }} شهر</p>
                                        </div>
                                    @endif

                                    @if ($order->down_payment_amount)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">المقدم (قيمة)</h5>
                                            <p class="text-gray-500 text-sm">
                                                {{ number_format((int) $order->down_payment_amount) }} ج.م</p>
                                        </div>
                                    @endif

                                    @if ($order->down_payment_percent)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">المقدم (نسبة)</h5>
                                            <p class="text-gray-500 text-sm">
                                                {{ rtrim(rtrim(number_format((float) $order->down_payment_percent, 2), '0'), '.') }}%
                                            </p>
                                        </div>
                                    @endif

                                    @if ($order->monthly_installment_amount)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">القسط الشهري</h5>
                                            <p class="text-gray-500 text-sm">
                                                {{ number_format((int) $order->monthly_installment_amount) }} ج.م</p>
                                        </div>
                                    @endif

                                    @if ($order->interest_rate)
                                        <div class="w-1/2 px-2 mb-3">
                                            <h5 class="font-bold text-black">الفائدة</h5>
                                            <p class="text-gray-500 text-sm">
                                                {{ rtrim(rtrim(number_format((float) $order->interest_rate, 2), '0'), '.') }}%
                                            </p>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="custom-section payment_confirm">
            <div class="container">
                <h4 class="mainColor text-md mb-5">أختر طريقة دفع مبلغ الحجز</h4>
                <form action="" class="form" enctype="multipart/form-data">
                    @csrf
                    <div class="flex-div">
                        <div class="px-2 sm:w-1/2 lg:w-1/5">
                            <div class="form_group">
                                <label class="form_group_checkbox" for="">
                                    <input type="radio" name="payWith" id="visa">
                                    <span>
                                        <img src="img/bookcar/visa.png" alt="visa.png">
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="px-2 sm:w-1/2 lg:w-1/5">
                            <div class="form_group">
                                <label class="form_group_checkbox" for="">
                                    <input type="radio" name="payWith">
                                    <span>
                                        <img src="img/bookcar/valu.png" alt="fawry">
                                    </span>
                                </label>
                            </div>
                        </div>
                        <!-- <div class="w-full px-2 text-center mt-6">
                    <button type="submit" class="form-button mx-auto bg-white mainColor border-transparent px-20 py-2">ادفع الأن</button>
                  </div> -->
                    </div>
                </form>
                <!-- <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h5 class="text-lg font-semibold">ملخص الدفع</h5>
                            <span class="text-2xl font-bold text-green-600">
                                {{ number_format($order->reservation_amount) }} ج.م
                            </span>
                        </div>
                        <p class="text-gray-600 text-sm mb-4">
                            مبلغ الحجز المطلوب دفعه لتأكيد طلبك
                        </p>
                        <p class="text-gray-500 text-xs">
                            رقم المرجع: {{ $order->reference_number }}
                        </p>
                    </div> -->

                <div class="flex-div">
                    <div class="w-full px-2 text-center mt-6">
                        {{-- <button type="button" id="pay-now-btn"
                            class="form-button mx-auto bg-white mainColor border-transparent px-20 py-2"
                            onclick="redirectToPayment()">
                            ادفع الأن
                        </button> --}}
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts-js')
    @if (isset($kashierConfig))
        <script
            id="kashier-iFrame"
            src="https://payments.kashier.io/kashier-checkout.js"
            data-amount="{{ $kashierConfig['amount'] }}"
            data-hash="{{ $kashierConfig['hash'] }}"
            data-currency="{{ $kashierConfig['currency'] }}"
            data-orderId="{{ $kashierConfig['orderId'] }}"
            data-merchantId="{{ $kashierConfig['merchantId'] }}"
            data-merchantRedirect="{{ $kashierConfig['merchantRedirect'] }}"
            data-serverWebhook="{{ $kashierConfig['serverWebhook'] }}"
            data-mode="{{ $kashierConfig['mode'] }}"
            data-description="{{ $kashierConfig['description'] }}"
            data-failureRedirect="{{ $kashierConfig['failureRedirect'] }}"
            @if(isset($kashierConfig['customerReference']))
            data-customerReference="{{ $kashierConfig['customerReference'] }}"
            @endif
            data-allowedMethods="card,wallet"
            data-display="ar"
            data-brandColor="#0066cc"
            data-type="external">
        </script>
    @endif

    <script>
        const KASHIER_DEBUG_MODE = "{{ env('KASHIER_DEBUG_MODE', false) }}";

        function kashierLogger(...args) {
            if (KASHIER_DEBUG_MODE) {
                console.log("[Kashier]", ...args);
            }
        }
        kashierLogger('Page loaded. Pay parameter:', "{{ request()->get('pay') }}");
        kashierLogger('Kashier config available:', {{ isset($kashierConfig) ? 'true' : 'false' }});
        @if (isset($kashierConfig))
            kashierLogger('Kashier Config:', @json($kashierConfig));
        @endif
        function responseCallBack(e) {
            kashierLogger("Kashier message received:", e.data);
            try {
                if (e.data && e.data.message === "success") {
                    kashierLogger("✅ Success payment", e.data);
                } else if (e.data && e.data.message === "failure") {
                    kashierLogger("❌ Failure payment", e.data);
                    alert('فشل في عملية الدفع. يرجى المحاولة مرة أخرى.');
                } else {
                    kashierLogger("ℹ️ Other Actions", e.data);
                }
            } catch (error) {
                kashierLogger("⚠️ Error handling payment response:", error);
            }
        }
        if (window.addEventListener) {
            addEventListener("message", responseCallBack, false);
        } else {
            attachEvent("onmessage", responseCallBack);
        }
    </script>
@endpush
