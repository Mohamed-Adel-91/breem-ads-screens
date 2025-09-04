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
    @php
        $brands = $brands ?? collect();
        $models = $models ?? collect();
        $terms = $terms ?? collect();
        $programs = $programs ?? collect();
        $selected = $selected ?? [];

        $selProg = $selectedProgram ?? null;
        $progName = $selProg ? $selProg->insurance_company . ' - ' . $selProg->program_name : '—';
        $yearlyEGP =
            $selProg && is_numeric($selProg->annual_price)
                ? number_format((float) $selProg->annual_price, 0) . ' جنيه سنويا'
                : '—';
    @endphp
    <main>
        <section class="menusection custom-section">
            <div class="container">
                <h2 class="custom_title">
                    <span>التأمين علي السيارات</span>
                </h2>
            </div>
        </section>

        <section class="insurance">
            <div class="container">
                <div class="flex-div">
                    <div class="w-full lg:w-4/6 px-2 mb-4 lg:mb-lg-0 lg:order-1 order-2">
                        <form class="insurance_form" action="{{ route('web.insurance.submit') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="brand_id" id="post_brand_id"
                                value="{{ $selected['brandId'] ?? '' }}">
                            <input type="hidden" name="car_model_id" id="post_car_model_id"
                                value="{{ $selected['modelId'] ?? '' }}">
                            <input type="hidden" name="car_term_id" id="post_car_term_id"
                                value="{{ $selected['termId'] ?? '' }}">
                            <input type="hidden" name="insurance_id" id="post_insurance_id"
                                value="{{ $selected['programId'] ?? '' }}">

                            <input type="hidden" name="car_price" id="post_car_price"
                                value="{{ $selected['price'] ?? '' }}">
                            <input type="hidden" name="annual_price_at_submission" id="annual_price_at_submission"
                                value="{{ $selectedProgram->annual_price ?? '' }}">
                            <h5 class="main-color text-lg mb-4">
                                ادخل معلوماتك الشخصية:
                            </h5>
                            <div class="flex-div">
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <input name="full_name" class="form_group_control" type="text" placeholder="الاسم بالكامل" />
                                    <!-- <label class="form_group_label">الاسم بالكامل</label> -->
                                    <div class="form_group_invalid"></div>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <input name="phone_number" class="form_group_control" type="tel" placeholder="رقم الموبايل" />
                                    <!-- <label
                                        class="form_group_label">رقم الموبايل</label> -->
                                    <div class="form_group_invalid"></div>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <input name="individual_email" class="form_group_control" placeholder="البريد الالكتروني" type="email" />
                                    <!-- <label
                                        class="form_group_label">البريد الالكتروني</label> -->
                                    <div class="form_group_invalid"></div>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <input name="national_id" class="form_group_control" type="number" placeholder="رقم البطاقة" />
                                    <!-- <label
                                        class="form_group_label">رقم البطاقة</label> -->
                                    <div class="form_group_invalid"></div>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <p class="text-xs">
                                        برجاء أدراج صوره وجه البطاقة الشخصية
                                    </p>
                                    <label class="  form_group_dragDrop" for="id_image_front">
                                        <input accept=".jpg,.png,.gif" type="file" name="front_national_id_image" />
                                        <svg width="32" height="32" viewBox="0 0 32 32" fill="none"
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
                                        برجاء أدراج صوره لظهر البطاقة
                                        الشخصية
                                    </p>
                                    <label class="  form_group_dragDrop" for="id_image_back"><input accept=".jpg,.png,.gif"
                                            type="file" name="back_national_id_image" /><svg width="32"
                                            height="32" viewBox="0 0 32 32" fill="none"
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
                            <hr class="w-full my-4" />
                            <h5 class="main-color text-lg mb-4">
                                ادخل معلومات عن السيارة:
                            </h5>
                            <div class="flex-div">
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <h5 class="text-color text-lg mb-3">
                                        هل العربية في حيازه الشركة؟
                                        <sup class="mainColor text-lg font-bold">*</sup>
                                    </h5>
                                    <select name="other_ownership" class="form_group_select">
                                        <option disabled="" value="0">
                                            أختر...
                                        </option>
                                        <option value="1">نعم</option>
                                        <option value="0">لا</option>
                                    </select>
                                    <div class="form_group_invalid"></div>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <h5 class="text-color text-lg mb-3">
                                        هل العربية عليها حظر بيع؟
                                        <sup class="mainColor text-lg font-bold">*</sup>
                                    </h5>
                                    <select name="sale_blocked" class="form_group_select">
                                        <option disabled="" value="0">
                                            أختر...
                                        </option>
                                        <option value="1">نعم</option>
                                        <option value="0">لا</option>
                                    </select>
                                    <div class="form_group_invalid"></div>
                                </div>
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <input name="chassis_number" class="form_group_control" type="text" placeholder="رقم الشاسي" />
                                    <!-- <label
                                        class="form_group_label">رقم الشاسية</label> -->
                                    <div class="form_group_invalid"></div>
                                </div>
                            </div>
                            <div class="flex-div">
                                <div class="form_group w-full lg:w-1/2 px-2">
                                    <p class="text-xs">
                                        برجاء أدراج صوره لرخصة السيارة
                                    </p>
                                    <label class="  form_group_dragDrop" for="driver_license_photo"><input
                                            accept=".jpg,.png,.gif" type="file" name="car_license_image" /><svg
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
                                        برجاء أدراج عقد الشراء,فاتوره
                                        السيارة أو جواب المرور
                                    </p>
                                    <label class="  form_group_dragDrop" for="purchase_contract"><input
                                            accept=".jpg,.png,.gif" type="file" name="car_documentation_image" /><svg
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
                            <h5 class="main-color text-lg mb-4">
                                الشروط والاحكام:
                            </h5>
                            <div class="flex-div">
                                <div class="tirms_cond cust-scrollbar">
                                    <h5 class="mb-4 text-blak">
                                        برجاء الموافقة علي الشروط والاحكام
                                    </h5>
                                    <label class="form_group_check"><input type="checkbox" name="agreed_terms" />
                                        <p>
                                            اوافق علي جميع الشروط والاحكام
                                            وأقر أن المعلومات المقدمة صحيحة
                                            وصحيحة ، وأنني أؤكد المعلومات
                                            المذكورة .أعلاه
                                        </p>
                                    </label>
                                    <div class="form_group_invalid"></div>
                                </div>
                            </div>
                            <div class="flex-div justify-center pt-4">
                                <button type="submit" id="submitInsuranceBtn" class="form-button">
                                    تقديم طلب
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="w-full lg:w-2/6 px-2 lg:order-2 order-1">
                        <div class="infoBox py-4 px-4 rounded-md shadow-md grayBgSection">
                            <p class="mb-4 text-center">الدفعة السنويه المقدره</p>
                            <h5 id="yearlyPriceText" class="text-black text-lg font-bold mb-6 text-center">
                                {{ $yearlyEGP }}
                            </h5>
                            <div class="flex-div mb-4 border-b border-gray-300 justify-between items-center">
                                <div class="px-2">
                                    <p class="mb-4">برنامج التأمين</p>
                                </div>
                                <div class="px-2">
                                    <p id="programNameText" class="text-black text-base font-bold mb-4">
                                        {{ $progName }}</p>
                                </div>
                            </div>
                            <p class="mb-4 text-center text-sm">! حساب التأمين استرشادي فقط</p>
                        </div>
                        <div class="py-10 px-6 mt-6 grayBgSection rounded-md flex items-center">
                            <form id="insuranceSideForm" class="form_installment"
                                action="{{ route('web.insurance-form') }}" method="GET"
                                data-models-template="{{ route('web.brands.models', ['brand' => 'ID_PLACEHOLDER']) }}"
                                data-terms-template="{{ route('web.models.terms', ['car' => 'ID_PLACEHOLDER']) }}"
                                data-price-url="{{ route('web.price') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="flex-div">
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="brandId" name="brandId" class="form_group_select">
                                            <option value="">اختر الماركة</option>
                                            @foreach ($brands as $b)
                                                <option value="{{ $b->id }}" @selected(($selected['brandId'] ?? null) == $b->id)>
                                                    {{ $b->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="modelId" name="modelId" class="form_group_select"
                                            @disabled($models->count() === 0)>
                                            <option value="">اختر الموديل</option>
                                            @foreach ($models as $m)
                                                <option value="{{ $m->id }}" @selected(($selected['modelId'] ?? null) == $m->id)>
                                                    {{ $m->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="categoryId" name="categoryId" class="form_group_select"
                                            @disabled($terms->count() === 0)>
                                            <option value="">اختر الفئة</option>
                                            @foreach ($terms as $t)
                                                <option value="{{ $t->id }}" @selected(($selected['termId'] ?? null) == $t->id)>
                                                    {{ $t->term_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <div class="flex-div mx-0 carPriceParent">
                                            <input id="carPrice" name="car_price" class="form_group_controlpayment"
                                                type="number" inputmode="numeric"
                                                value="{{ $selected['price'] ?? '' }}" readonly placeholder="سعر السيارة">
                                            <!-- <label class="form_group_label">سعر السيارة</label> -->
                                            <span class="form_group_pound">ج.م</span>
                                        </div>
                                        <div class="form_group_invalid"></div>
                                    </div>

                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="programId" name="programId" class="form_group_select"
                                            @disabled(empty($selected['price']))>
                                            <option value="">برنامج التأمين</option>
                                            @foreach ($programs as $p)
                                                <option value="{{ $p->id }}"
                                                    data-annual-price="{{ (int) $p->annual_price }}"
                                                    @selected(($selected['programId'] ?? null) == $p->id)>
                                                    {{ $p->insurance_company }} - {{ $p->program_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form_group_invalid"></div>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector(".sidebar");
            const overlay = document.querySelector(".sidebar-overlay");

            function toggleSidebar() {
                if (sidebar && overlay) {
                    sidebar.classList.toggle("open");
                    overlay.classList.toggle("open");
                }
            }
            overlay?.addEventListener("click", () => {
                sidebar?.classList.remove("open");
                overlay.classList.remove("open");
            });

            const postForm = document.querySelector('.insurance_form');
            const sideForm = document.getElementById('insuranceSideForm');
            const submitBtn = document.getElementById('submitInsuranceBtn');
            const originalSubmitHtml = submitBtn ? submitBtn.innerHTML : '';

            const selBrand = document.getElementById('brandId');
            const selModel = document.getElementById('modelId');
            const selTerm = document.getElementById('categoryId');
            const selProg = document.getElementById('programId');
            const inputPrice = document.getElementById('carPrice');

            const hBrand = document.getElementById('post_brand_id');
            const hModel = document.getElementById('post_car_model_id');
            const hTerm = document.getElementById('post_car_term_id');
            const hProg = document.getElementById('post_insurance_id');
            const hCar = document.getElementById('post_car_price');
            const hTotal = document.getElementById('annual_price_at_submission');

            const txtProgram = document.getElementById('programNameText');
            const txtYearly = document.getElementById('yearlyPriceText');

            const MODELS_URL_TMPL = sideForm?.dataset.modelsTemplate;
            const TERMS_URL_TMPL = sideForm?.dataset.termsTemplate;
            const PRICE_URL = sideForm?.dataset.priceUrl;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf) axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
            axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

            const fmtEGP = n => Number.isFinite(n) ? (new Intl.NumberFormat('ar-EG').format(Math.round(n)) +
                ' جنيه سنويا') : '—';
            const resetSelect = (el, placeholder) => {
                if (!el) return;
                el.innerHTML = `<option value="">${placeholder}</option>`;
            };
            async function jsonFetch(url) {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const ct = res.headers.get('content-type') || '';
                if (!ct.includes('application/json')) throw new Error('Non-JSON');
                return await res.json();
            }

            function clearErrors(form) {
                form.querySelectorAll('.form_group_invalid').forEach(el => el.textContent = '');
                form.querySelectorAll('.border-red-500').forEach(el => el.classList.remove('border-red-500'));
            }

            function showErrors(form, errors) {
                Object.keys(errors || {}).forEach(field => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (!input) return;
                    input.classList.add('border-red-500');
                    let invalid = input.closest('.form_group')?.querySelector('.form_group_invalid') ||
                        input.parentElement?.parentElement?.querySelector('.form_group_invalid');
                    if (invalid) invalid.textContent = errors[field][0];
                });
            }

            function updateProgramBox() {
                if (!selProg) return;
                const opt = selProg.selectedOptions?.[0];
                if (!opt) {
                    if (txtProgram) txtProgram.textContent = '—';
                    if (txtYearly) txtYearly.textContent = '—';
                    return;
                }
                txtProgram && (txtProgram.textContent = opt.textContent.trim());
                const annual = parseInt(opt.dataset.annualPrice || '0', 10);
                txtYearly && (txtYearly.textContent = annual ? fmtEGP(annual) : '—');
            }

            function syncHidden() {
                if (!postForm) return;
                if (hBrand) hBrand.value = selBrand?.value || '';
                if (hModel) hModel.value = selModel?.value || '';
                if (hTerm) hTerm.value = selTerm?.value || '';
                if (hProg) hProg.value = selProg?.value || '';

                const carPrice = Number(inputPrice?.value);
                if (hCar) hCar.value = Number.isFinite(carPrice) ? carPrice : '';

                const opt = selProg?.selectedOptions?.[0];
                const annual = opt ? parseInt(opt.dataset.annualPrice || '0', 10) : 0;
                if (hTotal) hTotal.value = Number.isFinite(annual) ? annual : '';
                if (txtYearly) txtYearly.textContent = annual ? fmtEGP(annual) : '—';
            }

            if (sideForm) {
                selBrand?.addEventListener('change', async function() {
                    resetSelect(selModel, 'اختر الموديل');
                    resetSelect(selTerm, 'اختر الفئة');
                    if (inputPrice) inputPrice.value = '';
                    if (selProg) selProg.disabled = true;
                    if (!this.value) {
                        if (selModel) selModel.disabled = true;
                        if (selTerm) selTerm.disabled = true;
                        syncHidden();
                        return;
                    }

                    try {
                        const url = MODELS_URL_TMPL.replace('ID_PLACEHOLDER', this.value);
                        const data = await jsonFetch(url);
                        (data.models || []).forEach(m => {
                            const o = document.createElement('option');
                            o.value = m.id;
                            o.textContent = m.name;
                            selModel.appendChild(o);
                        });
                        selModel.disabled = (selModel.options.length <= 1);
                    } catch (e) {
                        console.error('Fetch models failed:', e);
                    }
                    syncHidden();
                });

                selModel?.addEventListener('change', async function() {
                    resetSelect(selTerm, 'اختر الفئة');
                    if (inputPrice) inputPrice.value = '';
                    if (selProg) selProg.disabled = true;
                    if (!this.value) {
                        if (selTerm) selTerm.disabled = true;
                        syncHidden();
                        return;
                    }

                    try {
                        const url = TERMS_URL_TMPL.replace('ID_PLACEHOLDER', this.value);
                        const data = await jsonFetch(url);
                        (data.terms || []).forEach(t => {
                            const o = document.createElement('option');
                            o.value = t.id;
                            o.textContent = t.term_name;
                            selTerm.appendChild(o);
                        });
                        selTerm.disabled = (selTerm.options.length <= 1);
                    } catch (e) {
                        console.error('Fetch terms failed:', e);
                    }
                    syncHidden();
                });

                selTerm?.addEventListener('change', async function() {
                    if (!this.value || !selModel?.value) {
                        if (inputPrice) inputPrice.value = '';
                        if (selProg) selProg.disabled = true;
                        return syncHidden();
                    }
                    try {
                        const url =
                            `${PRICE_URL}?car_id=${encodeURIComponent(selModel.value)}&term_id=${encodeURIComponent(this.value)}`;
                        const data = await jsonFetch(url);
                        const priceNum = Number(data?.price);
                        if (inputPrice) inputPrice.value = Number.isFinite(priceNum) ? priceNum : '';
                        if (selProg) selProg.disabled = !Number.isFinite(priceNum);
                    } catch (e) {
                        console.error('Fetch price failed:', e);
                    }
                    syncHidden();
                });

                selProg?.addEventListener('change', () => {
                    updateProgramBox();
                    syncHidden();
                });

                updateProgramBox();
                syncHidden();
            }

            if (postForm) {
                postForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    clearErrors(postForm);
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner"></span> جاري تقديم الطلب';
                    }
                    const formData = new FormData(postForm);
                    axios.post(postForm.action, formData)
                        .then(res => {
                            const {
                                message,
                                reference,
                                redirect
                            } = res.data || {};
                            Swal.fire({
                                icon: 'success',
                                html: `
                                    <div style="text-align:center;direction:rtl">
                                        <p>${message || 'تم إرسال طلب التأمين بنجاح'}</p>
                                        ${reference ? `<p class="mt-2"><strong>رقم الطلب:</strong> ${reference}</p>` : ''}
                                        <p class="mt-2">سيتم تحويلك خلال ثانيتين…</p>
                                    </div>
                                    `,
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false,
                            }).then(() => {
                                if (redirect) {
                                    window.location.href = redirect;
                                } else if (submitBtn) {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalSubmitHtml;
                                }
                            });
                            postForm.reset();
                        })
                        .catch(err => {
                            if (err.response && err.response.status === 422) {
                                const {
                                    errors,
                                    message
                                } = err.response.data || {};
                                showErrors(postForm, errors);
                                let list = '<ul>';
                                Object.values(errors).forEach(arr => arr.forEach(msg => list += `<li>${msg}</li>`));
                                list += '</ul>';
                                Swal.fire({
                                    icon: 'error',
                                    text: message || 'خطأ في البيانات',
                                    html: list,
                                    confirmButtonColor: '#d03b37'
                                }).then(() => {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalSubmitHtml;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: 'تعذر الاتصال بالخادم - من فضلك تأكد من تسجيل الدخول و اختيار البرنامج المناسب',
                                    confirmButtonColor: '#d03b37'
                                }).then(() => {
                                    submitBtn.disabled = false;
                                    submitBtn.innerHTML = originalSubmitHtml;
                                });
                            }
                        });
                });
            }
        });
    </script>
@endpush
