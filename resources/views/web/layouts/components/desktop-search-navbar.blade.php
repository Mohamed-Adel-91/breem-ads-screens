<nav class="py-3 hidden lg:block secBgColor">
    <div class="container">
        <div class="flex items-center justify-between flex-wrap flex-auto -mx-2 secBgColor">
            <div class="w-11/12 flex flex-wrap items-center flex-auto -mx-2 px-2">
                <h3 class="text-white text-xl font-medium mb-0 px-2" style="width: 13%;">
                    ابحث عن سيارة
                </h3>
                <form action="{{ route('web.search-result') }}" method="GET" role="search" aria-label="البحث عن السيارات"
                    style="width: 85%;" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center flex-wrap w-full -mx-2">
                        <div class="flex-1 px-2 mb-0 form_group">
                            <select id="navBrand" name="brandId" aria-label="اختر ماركة السيارة"
                                class="form_group_select">
                                <option value="">الماركة</option>
                                @foreach ($navbarBrands ?? [] as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex-1 px-2 mb-0 form_group">
                            <select id="navModel" name="modelId" class="form_group_select" disabled>
                                <option value="">الموديل</option>
                            </select>
                        </div>
                        <div class="flex-1 px-2 mb-0 form_group">
                            <select id="navTerm" name="categoryId" class="form_group_select" disabled>
                                <option value="">الفئة</option>
                            </select>
                        </div>
                        <div class="flex-1 px-2 mb-0">
                            <div class="form_group">
                                <input type="number" inputmode="numeric" name="priceFrom"
                                    class="form_group_control" />
                                    <label class="form_group_label">السعر من</label>
                            </div>
                        </div>
                        <div class="flex-1 px-2 mb-0">
                            <div class="form_group">
                                <input type="number" inputmode="numeric" name="priceTo"
                                    class="form_group_control" />
                                    <label class="form_group_label">السعر إلي</label>

                            </div>
                        </div>
                        <div class="flex-2 px-3">
                            <button type="submit" class="whiteButton">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" aria-hidden="true" class="w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <span>بحث</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="w-1/12 px-2">
                <ul class="list-none flex items-center justify-center gap-4">
                    <li>
                        <a href="{{ $setting->facebook }}" target="_blank" rel="noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="11.449" height="21.377"
                                viewBox="0 0 11.449 21.377">
                                <path
                                    d="M12.308,12.025,12.9,8.156H9.19V5.645a1.934,1.934,0,0,1,2.181-2.09h1.688V.261a20.58,20.58,0,0,0-3-.261C7.006,0,5.008,1.853,5.008,5.207V8.156h-3.4v3.869h3.4v9.352H9.19V12.025Z"
                                    transform="translate(-1.609)" fill="#fff"></path>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ $setting->instagram }}" target="_blank" rel="noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17.869" height="17.869"
                                viewBox="0 0 17.869 17.869">
                                <g transform="translate(-0.328 0)">
                                    <path
                                        d="M13.031,0h-8.2A4.843,4.843,0,0,0,0,4.837v8.2a4.842,4.842,0,0,0,4.837,4.837h8.2a4.842,4.842,0,0,0,4.837-4.837v-8.2A4.843,4.843,0,0,0,13.031,0Zm-4.1,13.821A4.886,4.886,0,1,1,13.82,8.935,4.891,4.891,0,0,1,8.934,13.821Zm5-8.62A1.444,1.444,0,1,1,15.38,3.757,1.445,1.445,0,0,1,13.937,5.2Z"
                                        transform="translate(0.329 0)" fill="#fff"></path>
                                    <path d="M149.51,146.02a3.49,3.49,0,1,0,3.49,3.49A3.494,3.494,0,0,0,149.51,146.02Z"
                                        transform="translate(-140.247 -140.575)" fill="#fff"></path>
                                    <path d="M388.389,96.3a.4.4,0,1,0,.4.4A.4.4,0,0,0,388.389,96.3Z"
                                        transform="translate(-374.386 -92.94)" fill="#fff"></path>
                                </g>
                            </svg>
                        </a>
                    </li>
                    <li>
                        <a href="{{ $setting->youtube }}" target="_blank" rel="noreferrer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="19.607" height="13.728"
                                viewBox="0 0 19.607 13.728">
                                <path
                                    d="M9.8,13.728c-.061,0-6.139,0-7.672-.42A2.457,2.457,0,0,1,.4,11.58,25.918,25.918,0,0,1,0,6.864,26.012,26.012,0,0,1,.4,2.149,2.514,2.514,0,0,1,2.132.4,59.846,59.846,0,0,1,9.8,0c.061,0,6.155,0,7.672.42A2.455,2.455,0,0,1,19.2,2.149a24.684,24.684,0,0,1,.4,4.732,26.058,26.058,0,0,1-.4,4.716,2.457,2.457,0,0,1-1.729,1.728A59.836,59.836,0,0,1,9.8,13.728ZM7.85,3.925V9.8l5.1-2.939-5.1-2.94Z"
                                    fill="#fff"></path>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
@push('scripts-js')
    <script>
        window.ROUTES = {
            MODELS_BY_BRAND: @json(route('web.brands.models', ['brand' => 'id_placeholder'])),
            TERMS_BY_CAR: @json(route('web.models.terms', ['car' => 'id_placeholder'])),
        };

        document.addEventListener('DOMContentLoaded', function() {
            const brandSel = document.getElementById('navBrand');
            const modelSel = document.getElementById('navModel');
            const termSel = document.getElementById('navTerm');

            function resetSelect(sel, placeholder) {
                sel.innerHTML = `<option value="">${placeholder}</option>`;
            }

            function disableAfterBrand(resetModels = true) {
                if (resetModels) resetSelect(modelSel, 'الموديل');
                resetSelect(termSel, 'الفئة');
                modelSel.disabled = !brandSel.value;
                termSel.disabled = true;
            }

            disableAfterBrand();
            brandSel.addEventListener('change', async function() {
                disableAfterBrand();
                const brandId = this.value;
                if (!brandId) return;

                try {
                    const url = window.ROUTES.MODELS_BY_BRAND.replace('id_placeholder', brandId);
                    const res = await fetch(url);
                    if (!res.ok) {
                        console.error('HTTP', res.status, await res.text());
                        return;
                    }
                    const data = await res.json();

                    (data.models || []).forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id;
                        opt.textContent = m.name;
                        modelSel.appendChild(opt);
                    });
                    modelSel.disabled = false;
                } catch (e) {
                    console.error(e);
                }
            });

            modelSel.addEventListener('change', async function() {
                resetSelect(termSel, 'الفئة');
                termSel.disabled = true;

                const modelId = this.value;
                if (!modelId) return;

                try {
                    const url = window.ROUTES.TERMS_BY_CAR.replace('id_placeholder', modelId);
                    const res = await fetch(url);
                    if (!res.ok) {
                        console.error('HTTP', res.status, await res.text());
                        return;
                    }
                    const data = await res.json();

                    if (Array.isArray(data.terms) && data.terms.length) {
                        data.terms.forEach(t => {
                            const opt = document.createElement('option');
                            opt.value = t.id;
                            opt.textContent = t.term_name;
                            termSel.appendChild(opt);
                        });
                        termSel.disabled = false;
                    } else {
                        console.warn('لا توجد فئات لهذا الموديل.');
                    }
                } catch (e) {
                    console.error(e);
                }
            });
        });
    </script>
@endpush
