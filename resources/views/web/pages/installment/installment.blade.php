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
    <!-- ✅ إصلاح السلاش الناقص -->
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
                    <span>قسط سيارتك</span>
                </h2>
                <div class="flex-div items-center">
                    <div class="w-full lg:w-1/2 px-2 mb-4">
                        <div class="top_image_installment">
                            <img class="h-full w-full object-cover" src="img/homepage/installment.png" alt="">
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 px-2 mb-4">
                        <div>
                            <p class="mb-4">
                                لو عايز تشتري عربية جديدة، التوكيل بيقدملك برامج تقسيط متنوعة تناسب احتياجاتك المختلفة…
                            </p>
                            <p class="mb-4">
                                التوكيل كمان بيوفر برنامج للشركات… إلخ
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="custom-section programinstallment">
            <div class="container">
                <h2 class="custom_title">
                    <span>قسط سيارتك</span>
                </h2>
                <div class="flex-div items-center">
                    <div class="w-full lg:w-1/2 px-2 mb-4 order-1 lg:order-2">
                        <div class="programinstallment_image">
                            <img class="h-full w-full object-cover" src="img/homepage/bottom_installment.png"
                                alt="">
                        </div>
                    </div>
                    <div class="w-full lg:w-1/2 px-2 mb-4 order-2 lg:order-1">
                        <div>
                            <p>يعرض لك التوكيل مجموعة لا حصر لها من أشهر وأحدث ماركات السيارات العالمية…</p>
                        </div>
                    </div>
                </div>
                <div class="flex-div justify-center mt-4">
                    @foreach ($installment_programs as $item)
                        <div class="w-full sm:w-1/2 lg:w-1/4 mb-4 lg:mb-0 px-2">
                            <div class="programinstallment_programcard">
                                <div class="programinstallment_programcard_title">
                                    <h5 class="text-xl">{{ $item->name }}</h5>
                                    <p class="mb-3">{{ $item->bank->name }}</p>
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
                    <span>أحسب قسطك</span>
                </h2>
                <div class="flex-div">
                    <div class="w-full lg:w-1/2 px-2 order-2 lg:order-1">
                        <div class="py-10 px-6 grayBgSection rounded-md flex items-center h-full ">
                            <form id="installmentForm" class="form_installment" action="{{ route('web.installment-form') }}"
                                method="GET"
                                data-models-template="{{ route('web.brands.models', ['brand' => 'ID_PLACEHOLDER']) }}"
                                data-terms-template="{{ route('web.models.terms', ['car' => 'ID_PLACEHOLDER']) }}"
                                data-price-url="{{ route('web.price') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="flex-div">
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="brandId" name="brandId" class="form_group_select">
                                            <option value="">اختر الماركة</option>
                                            @foreach ($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="modelId" name="modelId" class="form_group_select" disabled>
                                            <option value="">اختر الموديل</option>
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="categoryId" name="categoryId" class="form_group_select" disabled>
                                            <option value="">اختر الفئة</option>
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="programId" name="programId" class="form_group_select" disabled>
                                            <option value="">برنامج التمويل</option>
                                            @foreach ($installment_programs as $prog)
                                                <option value="{{ $prog->id }}"
                                                    data-rate="{{ (float) $prog->interest_rate_per_year }}">
                                                    {{ $prog->bank->name }} - {{ $prog->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <select id="tenorDuration" name="tenorDuration" class="form_group_select" disabled>
                                            <option value="">عدد الشهور</option>
                                            <option value="12">12</option>
                                            <option value="24">24</option>
                                            <option value="36">36</option>
                                            <option value="48">48</option>
                                            <option value="60">60</option>
                                        </select>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <div class="flex-div mx-0 carPriceParent">
                                            <input id="carPrice" name="car_price" class="form_group_controlpayment"
                                                type="number" inputmode="numeric" placeholder="سعر السيارة" readonly>
                                            <!-- <label class="form_group_label">سعر السيارة</label> -->
                                            <span class="form_group_pound">ج.م</span>
                                        </div>
                                        <div class="form_group_invalid" data-error-for="carPrice"></div>
                                    </div>
                                    <div class="form_group px-2 w-full lg:w-1/2">
                                        <div class="flex-div mx-0 carPriceParent">
                                            <div class="w-2/3 px-0 relative">
                                                <input id="downPayment" name="down_payment"
                                                    class="form_group_controlpayment" placeholder="المقدم" type="number" inputmode="numeric"
                                                    disabled>
                                                <!-- <label class="form_group_label">المقدم</label> -->
                                                <span class="form_group_pound">ج.م</span>
                                            </div>
                                            <div class="w-1/3 px-0 relative">
                                                <input id="downPaymentPercent" name="down_payment_percent"
                                                    class="form_group_controlprecent" type="number" inputmode="numeric"
                                                    disabled>
                                                <span class="form_group_precent">%</span>
                                            </div>
                                        </div>
                                        <div class="form_group_invalid"></div>
                                    </div>
                                    <input type="hidden" id="selectedCarId" name="car_id">
                                    <input type="hidden" id="selectedTermId" name="term_id">
                                    <div class="w-full px-2 mt-4 text-center">
                                        <button type="submit" class="form-button mx-auto">احسب القسط</button>
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
            const form = document.getElementById('installmentForm');
            const MODELS_URL_TMPL = form.dataset.modelsTemplate;
            const TERMS_URL_TMPL = form.dataset.termsTemplate;
            const PRICE_URL = form.dataset.priceUrl;
            console.group('🔧 ROUTES (installment)');
            console.table({
                MODELS_URL_TMPL,
                TERMS_URL_TMPL,
                PRICE_URL
            });
            console.groupEnd();
            const brandSel = document.getElementById('brandId');
            const modelSel = document.getElementById('modelId');
            const termSel = document.getElementById('categoryId');
            const programSel = document.getElementById('programId');
            const tenorSel = document.getElementById('tenorDuration');
            const priceInput = document.getElementById('carPrice');
            const dpInput = document.getElementById('downPayment');
            const dpPctInput = document.getElementById('downPaymentPercent');
            const carIdInput = document.getElementById('selectedCarId');
            const termIdInput = document.getElementById('selectedTermId');

            function logDetailedError(step, response, error) {
                console.group(`❌ خطأ في ${step}`);
                if (response) {
                    console.log('Response Status:', response.status);
                    console.log('Response URL   :', response.url);
                    console.log('Content-Type   :', response.headers.get('content-type'));
                }
                console.log('Error          :', error);
                console.groupEnd();
            }
            async function handleResponse(response, step) {
                console.group(`📊 تفاصيل الاستجابة — ${step}`);
                console.log('Status    :', response.status, response.statusText);
                console.log('URL       :', response.url);
                const headers = Object.fromEntries(response.headers.entries());
                console.log('Headers   :', headers);
                console.groupEnd();
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`HTTP ${response.status} في ${step}:`, errorText.slice(0, 500));
                    throw new Error(`HTTP ${response.status}`);
                }
                const ct = response.headers.get('content-type') || '';
                if (!ct.includes('application/json')) {
                    const html = await response.text();
                    console.error(`Expected JSON but got ${ct} في ${step}:`, html.slice(0, 500));
                    console.error('🚨 Server returned non-JSON. أسباب معتادة:');
                    console.error('1) Route غير صحيح/404');
                    console.error('2) Redirect للّوجين/لغة');
                    console.error('3) Exception داخل الكنترولر');
                    throw new Error('Non-JSON response');
                }
                return response.json();
            }
            async function jsonFetch(url, step) {
                console.log('📡', step, '→', url);
                const res = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                return handleResponse(res, step);
            }

            function resetSelect(sel, placeholder) {
                sel.innerHTML = `<option value="">${placeholder}</option>`;
            }

            function resetDP() {
                dpInput.value = '';
                dpPctInput.value = '';
            }

            function enableAfterPrice() {
                programSel.disabled = false;
                tenorSel.disabled = false;
                dpInput.disabled = false;
                dpPctInput.disabled = false;
            }

            function disableAllNext() {
                modelSel.disabled = !brandSel.value;
                resetSelect(modelSel, 'اختر الموديل');
                termSel.disabled = true;
                resetSelect(termSel, 'اختر الفئة');
                programSel.disabled = true;
                tenorSel.disabled = true;
                priceInput.value = '';
                dpInput.disabled = true;
                dpPctInput.disabled = true;
                resetDP();
                carIdInput.value = '';
                termIdInput.value = '';
            }
            disableAllNext();
            brandSel.addEventListener('change', async function() {
                console.log('🔄 تم اختيار الماركة:', this.value);
                disableAllNext();
                if (!this.value) return;
                try {
                    const url = MODELS_URL_TMPL.replace('ID_PLACEHOLDER', this.value);
                    const data = await jsonFetch(url, 'جلب الموديلات');
                    console.log('✅ تم جلب الموديلات:', data);
                    resetSelect(modelSel, 'اختر الموديل');
                    (data.models || []).forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.name;
                        modelSel.appendChild(opt);
                    });
                    modelSel.disabled = modelSel.options.length <= 1;
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
                tenorSel.disabled = true;
                priceInput.value = '';
                resetDP();
                carIdInput.value = this.value || '';
                termIdInput.value = '';
                resetSelect(termSel, 'اختر الفئة');
                if (!this.value) return;
                try {
                    const url = TERMS_URL_TMPL.replace('ID_PLACEHOLDER', this.value);
                    const data = await jsonFetch(url, 'جلب الفئات');
                    console.log('✅ تم جلب الفئات:', data);
                    (data.terms || []).forEach(t => {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.term_name;
                        termSel.appendChild(opt);
                    });
                    termSel.disabled = termSel.options.length <= 1;
                    console.log(`✅ تم إضافة ${data.terms?.length || 0} فئة`);
                } catch (error) {
                    logDetailedError('جلب الفئات', null, error);
                    alert('حدث خطأ في جلب الفئات. يرجى المحاولة مرة أخرى.');
                }
            });
            termSel.addEventListener('change', async function() {
                console.log('🔄 تم اختيار الفئة:', this.value);
                programSel.disabled = true;
                tenorSel.disabled = true;
                priceInput.value = '';
                resetDP();
                termIdInput.value = this.value || '';
                if (!this.value || !modelSel.value) return;
                try {
                    const url =
                        `${PRICE_URL}?car_id=${encodeURIComponent(modelSel.value)}&term_id=${encodeURIComponent(this.value)}`;
                    const data = await jsonFetch(url, 'جلب السعر');
                    console.log('✅ تم جلب السعر:', data);
                    const priceNum = Number(data?.price);
                    if (Number.isFinite(priceNum)) {
                        priceInput.value = priceNum;
                        enableAfterPrice();
                        console.log('✅ تم تفعيل برنامج التمويل وباقي الحقول');
                    } else {
                        console.warn('⚠️ لا يوجد سعر صالح في الاستجابة');
                        priceInput.value = '';
                    }
                } catch (error) {
                    logDetailedError('جلب السعر', null, error);
                    alert('حدث خطأ في جلب سعر السيارة. يرجى المحاولة مرة أخرى.');
                }
            });
            let syncing = false;

            function clamp(val, min, max) {
                const n = Number(val);
                if (Number.isNaN(n)) return null;
                return Math.min(Math.max(n, min), max);
            }

            function computePercentFromAmount(amount, price) {
                if (!price) return '';
                return Math.round((amount / price) * 100);
            }

            function computeAmountFromPercent(pct, price) {
                if (!price) return '';
                return Math.round((pct / 100) * price);
            }
            dpInput.addEventListener('input', function() {
                if (syncing) return;
                syncing = true;
                const price = Number(priceInput.value) || 0;
                let amount = clamp(this.value, 0, price);
                console.log('✍️ تغيّر المقدم (جنيه):', this.value, '→ after clamp:', amount, 'price:',
                    price);
                this.value = (amount ?? '');
                dpPctInput.value = (amount != null && price > 0) ?
                    computePercentFromAmount(amount, price) :
                    '';
                console.log('↔️ النسبة (%) أصبحت:', dpPctInput.value);
                syncing = false;
            });
            dpPctInput.addEventListener('input', function() {
                if (syncing) return;
                syncing = true;
                const price = Number(priceInput.value) || 0;
                let percent = clamp(this.value, 0, 100);
                console.log('✍️ تغيّر النسبة (%):', this.value, '→ after clamp:', percent, 'price:', price);
                this.value = (percent ?? '');
                dpInput.value = (percent != null && price > 0) ?
                    computeAmountFromPercent(percent, price) :
                    '';
                console.log('↔️ المقدم (جنيه) أصبح:', dpInput.value);
                syncing = false;
            });
            priceInput.addEventListener('change', function() {
                console.log('ℹ️ تغيّر سعر العربية:', this.value);
                if (!this.value) {
                    resetDP();
                    return;
                }
                if (dpInput.value) dpInput.dispatchEvent(new Event('input'));
                else if (dpPctInput.value) dpPctInput.dispatchEvent(new Event('input'));
            });
        });
    </script>
@endpush
