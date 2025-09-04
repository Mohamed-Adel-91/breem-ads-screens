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
        <section class="custom-section">
            <div class="container">
                <h2 class="custom_title">
                    <span>أمن علي سيارتك</span>
                </h2>
                <div class="flex-div items-center">
                    <div class="w-full lg:w-1/2 px-2 mb-4 undefined lg:order-undefined">
                        <div class="top_image_installment">
                            <img class="h-full w-full object-cover" src="img/homepage/insurance.png" alt="">
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 px-2 mb-4 undefined lg:order-undefined">
                        <div>
                            <p class="mb-4">
                                لوريم ايبسوم هو نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع النصوص
                                بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكلي
                            </p>
                            <p class="mb-4">
                                لوريم ايبسوم هو نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع النصوص
                                بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكلي
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="custom-section programinstallment">
            <div class="container">
                <h2 class="custom_title">
                    <span>عروض التأمين</span>
                </h2>
                <div class="flex-div items-center">
                    <div class="w-full lg:w-1/2 px-2 mb-4 order-1 lg:order-2 lg:order-2">
                        <div class="programinstallment_image">
                            <img class="h-full w-full object-cover" src="img/homepage/bottom_installment.png"
                                alt="">
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 px-2 mb-4 order-2 lg:order-1 lg:order-1">
                        <div>
                            <p class="mb-4">
                                لوريم ايبسوم هو نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع النصوص
                                بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكلي
                            </p>
                            <p class="mb-4">
                                لوريم ايبسوم هو نموذج افتراضي يوضع في التصاميم لتعرض على العميل ليتصور طريقه وضع النصوص
                                بالتصاميم كان لوريم إيبسوم ولايزال المعيار للنص الشكلي
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex-div justify-center mt-4">
                    @foreach ($insurance_programs as $item)
                        <div class="w-full sm:w-1/2 lg:w-1/4 mb-4 lg:mb-0 px-2">
                            <div class="programinstallment_programcard">
                                <div class="programinstallment_programcard_title">
                                    <div class="flex justify-center mb-2">
                                        <img src="{{ $item->company_logo_path }}" alt="{{ $item->insurance_company }}">
                                    </div>
                                    <h5 class="text-xl">{{ $item->insurance_company }}- {{ $item->program_name }}</h5>
                                    <p class="mb-3">{{ $item->coverage_rate }}% نسبة التحمل في السنة الأولى</p>
                                    <p>{{ $item->annual_price }} جنيه سنويا</p>
                                </div>
                                <div class="programinstallment_programcard_body">
                                    <ul>
                                        @foreach ($item->features ?? [] as $f)
                                            @if (!empty($f['value']))
                                                <li>{{ $f['name'] }} - {{ $f['value'] }}</li>
                                            @elseif(empty($f['value']))
                                                <li>{{ $f['name'] }} </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="custom-section">
            <div class="container">
                <h2 class="custom_title">
                    <span>التأمين علي السيارات</span>
                </h2>
                <div class="flex-div">
                    <div class="w-full lg:w-1/2 px-2 order-2 lg:order-1">
                        <div class="py-10 px-6 grayBgSection rounded-md flex items-center h-full ">
                            <form id="insuranceForm" class="form_installment" action="{{ route('web.insurance-form') }}"
                                method="GET"
                                data-models-template="{{ route('web.brands.models', ['brand' => 'ID_PLACEHOLDER']) }}"
                                data-terms-template="{{ route('web.models.terms', ['car' => 'ID_PLACEHOLDER']) }}"
                                data-price-url="{{ route('web.price') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="flex-div">
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="brandId" name="brandId" class="form_group_select">
                                            <option value="">اختر الماركة</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form_group_invalid" data-error-for="brandId"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="modelId" name="modelId" class="form_group_select" disabled>
                                            <option value="">اختر الموديل</option>
                                        </select>
                                        <div class="form_group_invalid" data-error-for="modelId"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="categoryId" name="categoryId" class="form_group_select" disabled>
                                            <option value="">اختر الفئة</option>
                                        </select>
                                        <div class="form_group_invalid" data-error-for="categoryId"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <div class="flex-div mx-0 carPriceParent">
                                            <input id="carPrice" name="car_price" class="form_group_controlpayment"
                                                type="number" inputmode="numeric" readonly placeholder="سعر السيارة">
                                            <!-- <label class="form_group_label">سعر السيارة</label> -->
                                            <span class="form_group_pound">ج.م</span>
                                        </div>
                                        <div class="form_group_invalid" data-error-for="carPrice"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="programId" name="programId" class="form_group_select" disabled>
                                            <option value="">برنامج التأمين</option>
                                            @foreach ($insurance_programs as $item)
                                                <option value="{{ $item->id }}">
                                                    {{ $item->insurance_company }} - {{ $item->program_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form_group_invalid" data-error-for="programId"></div>
                                    </div>
                                    <input type="hidden" id="selectedCarId" name="car_id">
                                    <input type="hidden" id="selectedTermId" name="term_id">
                                    <div class="w-full px-2 mt-4 text-center">
                                        <button type="submit" class="form-button mx-auto">قدم الطلب </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 px-2 order-1 lg:order-2">
                        <div class="image_installment">
                            <img class="h-full w-full object-cover" src="img/homepage/precent.png" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
@push('scripts-js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('insuranceForm');
            const MODELS_URL_TMPL = form.dataset.modelsTemplate;
            const TERMS_URL_TMPL = form.dataset.termsTemplate;
            const PRICE_URL = form.dataset.priceUrl;
            const brandSel = document.getElementById('brandId');
            const modelSel = document.getElementById('modelId');
            const termSel = document.getElementById('categoryId');
            const programSel = document.getElementById('programId');
            const priceInput = document.getElementById('carPrice');
            const carIdInput = document.getElementById('selectedCarId');
            const termIdInput = document.getElementById('selectedTermId');
            window.API_ROUTES = Object.freeze({
                MODELS_BY_BRAND: MODELS_URL_TMPL,
                TERMS_BY_CAR: TERMS_URL_TMPL,
                PRICE: PRICE_URL,
            });

            function resetSelect(sel, placeholder) {
                sel.innerHTML = `<option value="">${placeholder}</option>`;
            }

            function disableUntilPick() {
                modelSel.disabled = !brandSel.value;
                termSel.disabled = true;
                programSel.disabled = true;
                priceInput.value = '';
                carIdInput.value = '';
                termIdInput.value = '';
                resetSelect(modelSel, 'اختر الموديل');
                resetSelect(termSel, 'اختر الفئة');
            }

            function logDetailedError(step, response, error) {
                console.group(`❌ خطأ في ${step}`);
                console.log('Response Status:', response?.status);
                console.log('Response URL:', response?.url);
                console.log('Error:', error);
                console.groupEnd();
            }
            async function handleResponse(response, step) {
                console.log(`📊 Response details for ${step}:`, {
                    status: response.status,
                    statusText: response.statusText,
                    url: response.url,
                    headers: Object.fromEntries(response.headers.entries())
                });
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`HTTP Error ${response.status} في ${step}:`, errorText);
                    if (response.status === 404) {
                        throw new Error(`Route not found: ${response.url}`);
                    }
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const responseText = await response.text();
                    console.error(`Expected JSON but got ${contentType} في ${step}:`, responseText.substring(0,
                        500));
                    if (contentType && contentType.includes('text/html')) {
                        console.error('🚨 Server returned HTML instead of JSON. This usually means:');
                        console.error('1. Route is not found (404)');
                        console.error('2. There is an exception in the controller');
                        console.error('3. Route is not configured properly');
                        throw new Error('Server returned HTML instead of JSON. Check console for details.');
                    }
                    throw new Error(`Expected JSON response but got ${contentType}`);
                }
                return await response.json();
            }
            disableUntilPick();
            brandSel.addEventListener('change', async function() {
                console.log('🔄 تم اختيار الماركة:', this.value);
                disableUntilPick();
                if (!this.value) return;
                try {
                    const url = MODELS_URL_TMPL.replace('ID_PLACEHOLDER', this.value);
                    console.log('📡 جاري جلب الموديلات من:', url);
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await handleResponse(response, 'جلب الموديلات');
                    console.log('✅ تم جلب الموديلات:', data);
                    if (!data.success) {
                        throw new Error(data.message || 'فشل في جلب الموديلات');
                    }
                    resetSelect(modelSel, 'اختر الموديل');
                    (data.models || []).forEach(model => {
                        const option = document.createElement('option');
                        option.value = model.id;
                        option.textContent = model.name;
                        modelSel.appendChild(option);
                    });
                    modelSel.disabled = false;
                    console.log(`✅ تم إضافة ${data.models?.length || 0} موديل`);
                } catch (error) {
                    logDetailedError('جلب الموديلات', null, error);
                    alert('حدث خطأ في جلب الموديلات. يرجى المحاولة مرة أخرى.');
                }
            });
            modelSel.addEventListener('change', async function() {
                console.log('🔄 تم اختيار الموديل:', this.value);
                termSel.disabled = true;
                programSel.disabled = true;
                priceInput.value = '';
                carIdInput.value = this.value || '';
                termIdInput.value = '';
                resetSelect(termSel, 'اختر الفئة');
                if (!this.value) return;
                try {
                    const url = TERMS_URL_TMPL.replace('ID_PLACEHOLDER', this.value);
                    console.log('📡 جاري جلب الفئات من:', url);
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await handleResponse(response, 'جلب الفئات');
                    console.log('✅ تم جلب الفئات:', data);
                    if (!data.success) {
                        throw new Error(data.message || 'فشل في جلب الفئات');
                    }
                    (data.terms || []).forEach(term => {
                        const option = document.createElement('option');
                        option.value = term.id;
                        option.textContent = term.term_name;
                        termSel.appendChild(option);
                    });
                    termSel.disabled = false;
                    console.log(`✅ تم إضافة ${data.terms?.length || 0} فئة`);
                } catch (error) {
                    logDetailedError('جلب الفئات', null, error);
                    alert('حدث خطأ في جلب الفئات. يرجى المحاولة مرة أخرى.');
                }
            });
            termSel.addEventListener('change', async function() {
                console.log('🔄 تم اختيار الفئة:', this.value);
                programSel.disabled = true;
                priceInput.value = '';
                termIdInput.value = this.value || '';
                if (!this.value || !modelSel.value) return;
                try {
                    const url =
                        `${PRICE_URL}?car_id=${encodeURIComponent(modelSel.value)}&term_id=${encodeURIComponent(this.value)}`;
                    console.log('📡 جاري جلب السعر من:', url);
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        }
                    });
                    const data = await handleResponse(response, 'جلب السعر');
                    console.log('✅ تم جلب السعر:', data);
                    if (!data.success || !data.price) {
                        throw new Error(data.message || 'لم يتم العثور على سعر');
                    }
                    priceInput.value = data.price;
                    programSel.disabled = false;
                    console.log('✅ تم تفعيل برنامج التأمين');
                } catch (error) {
                    logDetailedError('جلب السعر', null, error);
                    alert('حدث خطأ في جلب سعر السيارة. يرجى المحاولة مرة أخرى.');
                }
            });
            programSel.addEventListener('change', function() {
                console.log('🔄 تم اختيار برنامج التأمين:', this.value);
            });
        });
    </script>
@endpush
