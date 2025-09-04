@extends('web.layouts.master')

@push('meta')
    <title>التوكيل | الموقع الرسمي لبيع وشراء السيارات في مصر</title>
    <meta name="description"
        content="التوكيل هو موقعك الأول لشراء وبيع السيارات الجديدة والمستعملة، مع آخر الأخبار والعروض، ودليلك الكامل لعالم السيارات." />
    <meta name="keywords"
        content="التوكيل, سيارات جديدة, سيارات مستعملة, مقارنة سيارات, أسعار السيارات, شراء سيارة, بيع سيارة" />
    <meta property="og:title" content="التوكيل | موقع السيارات الأول في مصر" />
    <meta property="og:description" content="اكتشف أحدث السيارات، قارن بين الموديلات، تابع آخر العروض والأخبار." />
    <meta property="og:image" content="https://dev-iconcreations.com/el-tawkeel/public/frontend/img/homepage/og-image.png" />
    <meta property="og:url" content="https://eltawkeel.com/" />
    <meta property="og:type" content="website" />
@endpush

@section('content')
    @php
        $brands = $brands ?? collect();
        $models = $models ?? collect();
        $terms = $terms ?? collect();
        $programs = $programs ?? collect();
        $branches = $branches ?? collect();
        $selected = $selected ?? [];
        $summary = $summary ?? ['principal' => 0.0, 'apr' => 0.0, 'monthly' => null, 'total' => null];
        $fmt = fn($n) => is_numeric($n) ? number_format((float) $n, 0, '.', ',') . ' جنيه' : '—';
        $fmtMonthly = fn($n) => is_numeric($n) ? number_format((float) round($n), 0, '.', ',') . ' جنيه شهريا' : '—';
        $fmtEgp = function ($n) {
            return is_numeric($n) ? number_format((float) $n, 0) . ' جنيه' : '—';
        };
        $selectedProgram = null;
        if (!empty($selected['programId'] ?? null)) {
            $selectedProgram = $programs->firstWhere('id', (int) $selected['programId']);
        }
        $selectedProgramText = $selectedProgram
            ? ($selectedProgram->bank?->name ? $selectedProgram->bank->name . ' - ' : '') . $selectedProgram->name
            : '—';
    @endphp

    <main>
        <section class="menusection custom-section">
            <div class="container">
                <h2 class="custom_title"><span>حاسبة التمويل</span></h2>
            </div>
        </section>

        <section class="insurance">
            <div class="container">
                <div class="flex-div">
                    <div class="w-full lg:w-4/6 px-2 mb-4 lg:mb-lg-0 lg:order-1 order-2">
                        <form class="insurance_form" id="installmentOrderForm" method="POST"
                            action="{{ route('web.installment.submit') }}" enctype="multipart/form-data">
                            @csrf

                            <input type="hidden" name="brand_id" id="post_brand_id"
                                value="{{ $selected['brandId'] ?? '' }}">
                            <input type="hidden" name="car_model_id" id="post_model_id"
                                value="{{ $selected['modelId'] ?? '' }}">
                            <input type="hidden" name="term_id" id="post_term_id" value="{{ $selected['termId'] ?? '' }}">
                            <input type="hidden" name="program_id" id="post_program_id"
                                value="{{ $selected['programId'] ?? '' }}">
                            <input type="hidden" name="tenor_months" id="post_tenor"
                                value="{{ $selected['tenor'] ?? '' }}">
                            <input type="hidden" name="car_price" id="post_price" value="{{ $selected['price'] ?? '' }}">
                            <input type="hidden" name="down_payment" id="post_dp"
                                value="{{ $selected['down_payment'] ?? '' }}">
                            <input type="hidden" name="down_payment_percent" id="post_dp_pct"
                                value="{{ $selected['down_payment_percent'] ?? '' }}">

                            @if (request()->filled('brandId'))
                                <h5 class="main-color text-lg mb-4">اختر اقرب فرع:</h5>
                                <div class="flex flex-wrap mb-4">
                                    <div class="branchesSection_map_select basis-full w-full mb-8">
                                        <div class="custom-select-wrapper">
                                            <select class="custom-select" name="branch_id" id="branch_id">
                                                <option selected disabled value=""> اختر اقرب فرع </option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}" data-location="{{ $branch->location }}" @selected(old('branch_id') == $branch->id)>
                                                        {{ $branch->brand->name }} - {{ \App\Enums\CityEnum::getDescription($branch->city) }} - {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="branchesSection_map_locations basis-full w-full h-80 sm:h-96 mb-8"
                                        id="map"></div>
                                </div>
                            @else
                                <p class="text-center text-sm text-gray-500 mb-8">
                                    برجاء اختيار الماركة أولًا لعرض الفروع والخريطة.
                                </p>
                            @endif

                            <h5 class="main-color text-lg mb-4">اختيار نظام التقديم:</h5>
                            <div class="flex-div">
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <label class="form_group_radio" data-form="personal">
                                        <input type="radio" name="dealing_type" id="dealing_type_personal" value="1"
                                            checked>
                                        <span>أفراد</span>
                                    </label>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <label class="form_group_radio" data-form="companies">
                                        <input type="radio" name="dealing_type" id="dealing_type_companies"
                                            value="2">
                                        <span>شركات</span>
                                    </label>
                                </div>
                            </div>

                            <h5 class="main-color text-lg mb-4">بيانات العميل:</h5>
                            <div class="personal mainForm">
                                <div class="flex-div">
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="text" name="full_name" id="full_name" placeholder="الاسم بالكامل"
                                            value="{{ old('full_name') }}" />
                                        <!-- <label class="form_group_label" for="full_name">الاسم بالكامل</label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="number" name="phone" id="phone" placeholder="رقم الموبايل"
                                            value="{{ old('phone') }}" />
                                        <!-- <label class="form_group_label" for="phone">رقم الموبايل</label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="email" name="email" id="email" placeholder="البريد الالكتروني"
                                            value="{{ old('email') }}" />
                                        <!-- <label class="form_group_label" for="email">البريد الالكتروني</label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="number" name="national_id" placeholder="رقم البطاقه"
                                            id="national_id" value="{{ old('national_id') }}" />
                                        <!-- <label class="form_group_label" for="national_id">رقم البطاقه</label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <select name="car_owned_by_other" id="car_owned_by_other"
                                            class="form_group_select">
                                            <option disabled value="" selected> هل السيارة ملك للغير ؟</option>
                                            <option value="1">نعم</option>
                                            <option value="2">لا</option>
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                </div>

                                <div class="flex-div">
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            برجاء أدراج صوره وجه البطاقة الشخصية
                                        </p>
                                        <label class="  form_group_dragDrop" for="front_national_id_image"><input
                                                accept=".jpg,.png,.gif" type="file"
                                                name="front_national_id_image" /><svg width="32" height="32"
                                                viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            برجاء أدراج صوره لظهر البطاقة الشخصية
                                        </p>
                                        <label class="  form_group_dragDrop" for="back_national_id_image"><input
                                                accept=".jpg,.png,.gif" type="file"
                                                name="back_national_id_image" /><svg width="32" height="32"
                                                viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            خطاب بنكي ( كشف حساب بنكي )
                                        </p>
                                        <label class="  form_group_dragDrop" for="bank_statement">
                                            <input accept=".jpg,.png,.gif" type="file" name="bank_statement" /><svg
                                                width="32" height="32" viewBox="0 0 32 32" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            خطاب موارد بشريه ( شهاده من الشركة بالمرتب)
                                        </p>
                                        <label class="  form_group_dragDrop" for="hr_letter"><input
                                                accept=".jpg,.png,.gif" type="file" name="hr_letter" /><svg
                                                width="32" height="32" viewBox="0 0 32 32" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- companies --}}
                            <div class="companies mainForm">
                                <div class="flex-div">
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="text" name="company_name" placeholder="اسم الشركة"
                                            id="company_name" value="{{ old('company_name') }}" />
                                        <!-- <label class="form_group_label" for="company_name">اسم الشركة</label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="number" name="representative_phone" placeholder="رقم الممثل
                                            القانوني"
                                            id="representative_phone" value="{{ old('representative_phone') }}" />
                                        <!-- <label class="form_group_label" for="representative_phone">
                                            رقم الممثل
                                            القانوني
                                        </label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="email" name="company_email" placeholder="البريد الالكتروني"
                                            id="company_email" value="{{ old('company_email') }}" />
                                        <!-- <label class="form_group_label" for="company_email">البريد الالكتروني</label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <input class="form_group_control" type="number"
                                            name="commercial_registration_number" id="commercial_registration_number"
                                            value="{{ old('commercial_registration_number') }}" placeholder="رقم السجل
                                            التجاري" />
                                        <!-- <label class="form_group_label" for="commercial_registration_number">
                                            رقم السجل
                                            التجاري
                                        </label> -->
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <select name="car_owned_by_other" id="car_owned_by_other_company"
                                            class="form_group_select">
                                            <option disabled value="" selected> هل السيارة ملك للغير ؟</option>
                                            <option value="1">نعم</option>
                                            <option value="2">لا</option>
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                </div>

                                <div class="flex-div">
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            برجاء أدراج صوره السجل التجاري
                                        </p>
                                        <label class="  form_group_dragDrop" for="commercial_registration_image"><input
                                                accept=".jpg,.png,.gif" type="file"
                                                name="commercial_registration_image" /><svg width="32" height="32"
                                                viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            برجاء أدراج صوره البطاقة الضريبية
                                        </p>
                                        <label class="  form_group_dragDrop" for="tax_card_image"><input
                                                accept=".jpg,.png,.gif" type="file" name="tax_card_image" /><svg
                                                width="32" height="32" viewBox="0 0 32 32" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group w-full lg:w-1/2 px-2">
                                        <p class="text-xs">
                                            خطاب بنكي ( كشف حساب بنكي )
                                        </p>
                                        <label class="  form_group_dragDrop" for="company_bank_statement"><input
                                                accept=".jpg,.png,.gif" type="file"
                                                name="company_bank_statement" /><svg width="32" height="32"
                                                viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path
                                                    d="M5.33317 6.66667H22.6665V16H25.3332V6.66667C25.3332 5.196 24.1372 4 22.6665 4H5.33317C3.8625 4 2.6665 5.196 2.6665 6.66667V22.6667C2.6665 24.1373 3.8625 25.3333 5.33317 25.3333H15.9998V22.6667H5.33317V6.66667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M10.6665 14.6667L6.6665 20H21.3332L15.9998 12L11.9998 17.3333L10.6665 14.6667Z"
                                                    fill="#0658C2"></path>
                                                <path
                                                    d="M25.3332 18.6667H22.6665V22.6667H18.6665V25.3333H22.6665V29.3333H25.3332V25.3333H29.3332V22.6667H25.3332V18.6667Z"
                                                    fill="#0658C2"></path>
                                            </svg>
                                            <div class="">
                                                <span class="inputValue">
                                                    برجاء
                                                    تحميل او
                                                    إدراج ملفك هنا!
                                                </span>
                                            </div>
                                        </label>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="main-color text-lg mb-4">الشروط والاحكام:</h5>
                            <div class="flex-div">
                                <div class="tirms_cond cust-scrollbar">
                                    <h5 class="mb-4 text-blak">برجاء الموافقة علي الشروط والاحكام</h5>
                                    <label class="form_group_check">
                                        <input type="checkbox" name="agreed_terms" />
                                        <p>اوافق علي جميع الشروط والاحكام وأقر أن المعلومات المقدمة صحيحة وصحيحة ، وأنني
                                            أؤكد المعلومات المذكورة أعلاه</p>
                                    </label>
                                    <div class="form_group_invalid"></div>
                                </div>
                            </div>

                            <div class="flex-div justify-center pt-4">
                                <button type="submit" id="submitInstallmentBtn" class="form-button">تقديم طلب</button>
                            </div>
                        </form>
                    </div>

                    <div class="w-full lg:w-2/6 px-2 lg:order-2 order-1 ">
                        <div class="infoBox py-4 px-4 rounded-md shadow-md grayBgSection">
                            <p class="mb-4 text-center">الدفعة الشهرية المقدرة</p>
                            <h5 id="monthlyPaymentText" class="text-black text-lg font-bold mb-6 text-center">
                                {{ $summary['monthly'] !== null ? number_format((float) $summary['monthly'], 0) . ' جنيه شهريا' : '—' }}
                            </h5>

                            <div class="flex-div mb-4 border-b border-gray-300 justify-between items-center">
                                <div class="px-2">
                                    <p class="mb-4">برنامج التمويل</p>
                                </div>
                                <div class="px-2">
                                    <p id="programNameText" class="text-black text-base font-bold mb-4">
                                        {{ $selectedProgramText }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex-div mb-4 border-b border-gray-300 justify-between items-center">
                                <div class="px-2">
                                    <p class="mb-4">قيمة المقدم</p>
                                </div>
                                <div class="px-2">
                                    <p id="downPaymentText" class="text-black text-base font-bold mb-4">
                                        {{ $fmtEgp($selected['down_payment'] ?? null) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex-div mb-4 border-b border-gray-300 justify-between items-center">
                                <div class="px-2">
                                    <p class="mb-4">مدة التقسيط</p>
                                </div>
                                <div class="px-2">
                                    <p id="tenorText" class="text-black text-base font-bold mb-4">
                                        {{ !empty($selected['tenor']) ? $selected['tenor'] . ' شهر' : '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex-div mb-4 border-b border-gray-300 justify-between items-center">
                                <div class="px-2">
                                    <p class="mb-4">إجمالي المبلغ شامل الفائدة</p>
                                </div>
                                <div class="px-2">
                                    <p id="totalText" class="text-black text-base font-bold mb-4">
                                        {{ $fmtEgp($summary['total'] ?? null) }}
                                    </p>
                                </div>
                            </div>

                            <p class="mb-4 text-center text-sm">! حساب الأقساط استرشادي فقط</p>
                        </div>

                        <div class="py-10 px-6 mt-6 grayBgSection rounded-md flex items-center">
                            <form id="installmentParamsForm" class="form_installment"
                                action="{{ route('web.installment-form') }}" method="GET"
                                data-models-template="{{ route('web.brands.models', ['brand' => 'ID_PLACEHOLDER']) }}"
                                data-terms-template="{{ route('web.models.terms', ['car' => 'ID_PLACEHOLDER']) }}"
                                data-price-url="{{ route('web.price') }}" enctype="multipart/form-data">
                                @csrf

                                <div class="flex-div">
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="brandId" name="brandId" class="form_group_select">
                                            <option value="">اختر الماركة</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}" @selected(($selected['brandId'] ?? null) == $brand->id)>
                                                    {{ $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="modelId" name="modelId" class="form_group_select"
                                            @disabled(($models->count() ?? 0) === 0)>
                                            <option value="">اختر الموديل</option>
                                            @foreach ($models as $m)
                                                <option value="{{ $m->id }}" @selected(($selected['modelId'] ?? null) == $m->id)>
                                                    {{ $m->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="categoryId" name="categoryId" class="form_group_select"
                                            @disabled(($terms->count() ?? 0) === 0)>
                                            <option value="">اختر الفئة</option>
                                            @foreach ($terms as $t)
                                                <option value="{{ $t->id }}" @selected(($selected['termId'] ?? null) == $t->id)>
                                                    {{ $t->term_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- برنامج --}}
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="programId" name="programId" class="form_group_select"
                                            @disabled(empty($selected['price']))>
                                            <option value="">برنامج التمويل</option>
                                            @foreach ($programs as $prog)
                                                <option value="{{ $prog->id }}"
                                                    data-rate="{{ (float) $prog->interest_rate_per_year }}"
                                                    @selected(($selected['programId'] ?? null) == $prog->id)>
                                                    {{ $prog->bank?->name ? $prog->bank->name . ' - ' : '' }}{{ $prog->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="tenorDuration" name="tenorDuration" class="form_group_select"
                                            @disabled(empty($selected['price']))>
                                            <option value="">عدد الشهور</option>
                                            @foreach ([12, 24, 36, 48, 60] as $m)
                                                <option value="{{ $m }}" @selected(($selected['tenor'] ?? null) == $m)>
                                                    {{ $m }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <div class="flex-div mx-0  carPriceParent">
                                            <input id="carPrice" name="car_price" class="form_group_controlpayment"
                                                type="number" inputmode="numeric"
                                                value="{{ $selected['price'] ?? '' }}" readonly placeholder="سعر السيارة">
                                            <!-- <label class="form_group_label">سعر السيارة</label> -->
                                            <span class="form_group_pound">ج.م</span>
                                        </div>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <div class="flex-div mx-0 carPriceParent">
                                            <div class="w-2/3 px-0 relative">
                                                <input id="downPayment" name="down_payment"
                                                    class="form_group_controlpayment" type="number" placeholder="المقدم" inputmode="numeric"
                                                    value="{{ $selected['down_payment'] ?? '' }}"
                                                    @disabled(empty($selected['price']))>
                                                <!-- <label class="form_group_label">المقدم</label> -->
                                                <span class="form_group_pound">ج.م</span>
                                            </div>
                                            <div class="w-1/3 px-0 relative">
                                                <input id="downPaymentPercent" name="down_payment_percent"
                                                    class="form_group_controlprecent" type="number" inputmode="numeric"
                                                    value="{{ $selected['down_payment_percent'] ?? '' }}"
                                                    @disabled(empty($selected['price']))>
                                                <span class="form_group_precent">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    <input type="hidden" id="selectedCarId" name="car_id"
                                        value="{{ $selected['modelId'] ?? '' }}">
                                    <input type="hidden" id="selectedTermId" name="term_id"
                                        value="{{ $selected['termId'] ?? '' }}">

                                    <div class="w-full px-2 mt-4 text-center">
                                        <button type="submit" class="form-button mx-auto">تعديل</button>
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
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB2oKnoBeDDRxl3hqwuoyx7k8lKrjPRf0w&callback=initMap&libraries=&v=weekly"
        async></script>
    <script>
        const branches = @json($branches);
        let map;
        const branchCoords = {};
    </script>
    <script>
        const sidebar = document.querySelector(".sidebar");
        const overlay = document.querySelector(".sidebar-overlay");

        function toggleSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.toggle("open");
                overlay.classList.toggle("open");
            }
        }

        if (overlay) {
            overlay.addEventListener("click", () => {
                document
                    .querySelector(".sidebar")
                    ?.classList.remove("open");
                overlay.classList.remove("open");
            });
        }

        function initMap() {
            if (!branches.length) return;
            const centerCoords = parseCoords(branches[0].location);
            if (!centerCoords) return;
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: centerCoords
            });
            branches.forEach(b => {
                const coords = parseCoords(b.location);
                if (!coords) return;
                branchCoords[b.id] = coords;
                const marker = new google.maps.Marker({
                    position: coords,
                    map,
                    title: b.name
                });
                marker.addListener('click', () => window.open(b.location, '_blank'));
            });
        }

        function parseCoords(urlOrCoords) {
            const str = String(urlOrCoords);

            // Pattern: !2dlongitude!3dlatitude
            let match = str.match(/!2d(-?\d+(?:\.\d+)?)!3d(-?\d+(?:\.\d+)?)/);
            if (match) {
                return {
                    lat: parseFloat(match[2]),
                    lng: parseFloat(match[1])
                };
            }

            // Pattern: @latitude,longitude
            match = str.match(/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/);
            if (match) {
                return {
                    lat: parseFloat(match[1]),
                    lng: parseFloat(match[2])
                };
            }

            // Pattern: lat,lng
            match = str.match(/(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/);
            if (match) {
                return {
                    lat: parseFloat(match[1]),
                    lng: parseFloat(match[2])
                };
            }

            return null;
        }

        const branchSel = document.getElementById('branch_id');
        branchSel?.addEventListener('change', () => {
            const branch = branches.find(b => b.id == branchSel.value);
            const coords = parseCoords(branch?.location);
            if (coords) {
                map.setCenter(coords);
                map.setZoom(15);
            }
        });
    </script>
    <script>
        const form = document.getElementById('installmentOrderForm');
        form?.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitInstallmentBtn');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span> جاري تقديم الطلب';

            const formData = new FormData(form);
            axios.post(form.action, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(({
                    data
                }) => {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم تقديم طلبك بنجاح',
                        html: `رقم الطلب المرجعي: <b>${data.reference_number}</b>`,
                        timer: 2000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                    }).then(() => {
                        window.location.href = data.redirect_url;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalHtml;
                    });
                })
                .catch(err => {
                    if (err.response && err.response.status === 422) {
                        const errors = err.response.data.errors;
                        let list = '<ul>';
                        Object.values(errors).forEach(arr => arr.forEach(msg => list += `<li>${msg}</li>`));
                        list += '</ul>';
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ في البيانات',
                            confirmButtonText: 'حسنا',
                            confirmButtonColor: '#d03b37',
                            html: list
                        }).then(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            confirmButtonText: 'حسنا',
                            confirmButtonColor: '#d03b37',
                            text: ' تعذر الاتصال بالخادم - من فضلك تأكد من تسجيل الدخول و اختيار البرنامج التمويلي',
                        }).then(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        });
                    }
                });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('installmentParamsForm');
            if (!form) return;

            const MODELS_URL_TMPL = form.dataset.modelsTemplate;
            const TERMS_URL_TMPL = form.dataset.termsTemplate;
            const PRICE_URL = form.dataset.priceUrl;

            const selBrand = document.getElementById('brandId');
            const selModel = document.getElementById('modelId');
            const selTerm = document.getElementById('categoryId');
            const selProgram = document.getElementById('programId');
            const selTenor = document.getElementById('tenorDuration');

            const inputPrice = document.getElementById('carPrice');
            const inputDP = document.getElementById('downPayment');
            const inputDPPct = document.getElementById('downPaymentPercent');

            const postBrand = document.getElementById('post_brand_id');
            const postModel = document.getElementById('post_model_id');
            const postTerm = document.getElementById('post_term_id');
            const postProg = document.getElementById('post_program_id');
            const postTenor = document.getElementById('post_tenor');
            const postPrice = document.getElementById('post_price');
            const postDP = document.getElementById('post_dp');
            const postDPPct = document.getElementById('post_dp_pct');

            const hiddenCarId = document.getElementById('selectedCarId');
            const hiddenTermId = document.getElementById('selectedTermId');

            const txtMonthly = document.getElementById('monthlyPaymentText');
            const txtProg = document.getElementById('programNameText');
            const txtDP = document.getElementById('downPaymentText');
            const txtTenor = document.getElementById('tenorText');
            const txtTotal = document.getElementById('totalText');

            const fmtEGP = n => (Number.isFinite(n) ? new Intl.NumberFormat('ar-EG').format(Math.round(n)) +
                ' جنيه' : '—');

            function resetSelect(el, placeholder) {
                el.innerHTML = `<option value="">${placeholder}</option>`;
            }

            function enableAfterPrice(enabled) {
                selProgram.disabled = !enabled;
                selTenor.disabled = !enabled;
                inputDP.disabled = !enabled;
                inputDPPct.disabled = !enabled;
            }

            function getAPR() {
                const opt = selProgram?.selectedOptions?.[0];
                const rate = opt ? parseFloat(opt.dataset.rate) : NaN;
                return Number.isFinite(rate) ? rate : 0;
            }

            function computeMonthly(principal, aprYear, months) {
                if (!months || months <= 0) return {
                    monthly: null,
                    total: null
                };
                const i = (aprYear / 100) / 12;
                if (i <= 0) {
                    const m = principal / months;
                    return {
                        monthly: m,
                        total: m * months
                    };
                }
                const m = principal * (i / (1 - Math.pow(1 + i, -months)));
                return {
                    monthly: m,
                    total: m * months
                };
            }

            function syncPostHidden() {
                postBrand.value = selBrand.value || '';
                postModel.value = selModel.value || '';
                postTerm.value = selTerm.value || '';
                postProg.value = selProgram.value || '';
                postTenor.value = selTenor.value || '';

                postPrice.value = inputPrice.value || '';
                postDP.value = inputDP.value || '';
                postDPPct.value = inputDPPct.value || '';
            }

            function recalcBox() {
                const price = Number(inputPrice.value) || 0;
                const dp = Math.min(Number(inputDP.value) || 0, price);
                const months = Number(selTenor.value) || 0;
                const apr = getAPR();
                const principal = Math.max(price - dp, 0);

                txtProg.textContent = selProgram.value ? selProgram.selectedOptions[0].textContent.trim() : '—';
                txtDP.textContent = fmtEGP(dp);
                txtTenor.textContent = months ? (months + ' شهر') : '—';

                if (!price || !months) {
                    txtMonthly.textContent = '—';
                    txtTotal.textContent = '—';
                } else {
                    const {
                        monthly,
                        total
                    } = computeMonthly(principal, apr, months);
                    txtMonthly.textContent = Number.isFinite(monthly) ? (new Intl.NumberFormat('ar-EG').format(Math
                        .round(monthly)) + ' جنيه شهريا') : '—';
                    txtTotal.textContent = fmtEGP(total);
                }
                syncPostHidden();
            }
            async function jsonFetch(url, step) {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const text = await res.text();
                if (!res.ok) {
                    console.error(`HTTP ${res.status} @ ${step}`, text.slice(0, 300));
                    throw new Error('HTTP ' + res.status);
                }
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    console.error('Expected JSON', ct, text.slice(0, 200));
                    throw new Error('Non-JSON');
                }
                return JSON.parse(text);
            }
            async function loadModels(brandId) {
                resetSelect(selModel, 'اختر الموديل');
                resetSelect(selTerm, 'اختر الفئة');
                inputPrice.value = '';
                enableAfterPrice(false);
                if (!brandId) {
                    selModel.disabled = true;
                    selTerm.disabled = true;
                    return;
                }
                const url = MODELS_URL_TMPL.replace('ID_PLACEHOLDER', brandId);
                const data = await jsonFetch(url, 'models');
                (data.models || []).forEach(m => {
                    const o = document.createElement('option');
                    o.value = m.id;
                    o.textContent = m.name;
                    selModel.appendChild(o);
                });
                selModel.disabled = (selModel.options.length <= 1);
                recalcBox();
            }
            async function loadTerms(modelId) {
                resetSelect(selTerm, 'اختر الفئة');
                inputPrice.value = '';
                enableAfterPrice(false);
                if (!modelId) {
                    selTerm.disabled = true;
                    return;
                }
                const url = TERMS_URL_TMPL.replace('ID_PLACEHOLDER', modelId);
                const data = await jsonFetch(url, 'terms');
                (data.terms || []).forEach(t => {
                    const o = document.createElement('option');
                    o.value = t.id;
                    o.textContent = t.term_name;
                    selTerm.appendChild(o);
                });
                selTerm.disabled = (selTerm.options.length <= 1);
                recalcBox();
            }
            async function fetchPrice(modelId, termId) {
                const url =
                    `${PRICE_URL}?car_id=${encodeURIComponent(modelId)}&term_id=${encodeURIComponent(termId)}`;
                const data = await jsonFetch(url, 'price');
                const priceNum = Number(data?.price);
                inputPrice.value = Number.isFinite(priceNum) ? priceNum : '';
                enableAfterPrice(Number.isFinite(priceNum));
                recalcBox();
            }

            selBrand.addEventListener('change', async function() {
                await loadModels(this.value);
                hiddenCarId.value = '';
                hiddenTermId.value = '';
                recalcBox();
            });
            selModel.addEventListener('change', async function() {
                hiddenCarId.value = this.value || '';
                await loadTerms(this.value);
                hiddenTermId.value = '';
                recalcBox();
            });
            selTerm.addEventListener('change', async function() {
                hiddenTermId.value = this.value || '';
                if (this.value && selModel.value) await fetchPrice(selModel.value, this.value);
            });

            let syncing = false;
            inputDP.addEventListener('input', function() {
                if (syncing) return;
                syncing = true;
                const price = Number(inputPrice.value) || 0;
                let amount = Number(this.value);
                if (!Number.isFinite(amount)) amount = 0;
                amount = Math.max(0, Math.min(amount, price));
                this.value = amount || '';
                inputDPPct.value = (price > 0) ? Math.round((amount / price) * 100) : '';
                syncing = false;
                recalcBox();
            });
            inputDPPct.addEventListener('input', function() {
                if (syncing) return;
                syncing = true;
                const price = Number(inputPrice.value) || 0;
                let pct = Number(this.value);
                if (!Number.isFinite(pct)) pct = 0;
                pct = Math.max(0, Math.min(pct, 100));
                this.value = pct || '';
                inputDP.value = (price > 0) ? Math.round((pct / 100) * price) : '';
                syncing = false;
                recalcBox();
            });
            selTenor.addEventListener('change', recalcBox);
            selProgram.addEventListener('change', recalcBox);

            recalcBox();
        });
    </script>

@endpush
