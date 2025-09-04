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
    <main class="comparePage">
        <section class=" comparesection custom-section" id="cars-compare-form">
            <div class="container">
                <h2 class="custom_title">
                    <span>مقارنة السيارات</span>
                </h2>
                <form class="w-full form" action="" method="GET" enctype="multipart/form-data">
                    @csrf
                    <div class="flex flex-wrap w-full -mx-2">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="w-1/2 md:w-3/12 px-2 form_group car-group" data-index="{{ $i }}">
                                <select name="cars[{{ $i }}][brand_id]" class="mb-4 form_group_select js-brand">
                                    <option value="" disabled selected>اختر الماركة</option>
                                    @foreach ($brands as $brand)
                                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                                <select name="cars[{{ $i }}][model_id]" class="mb-4 form_group_select js-model"
                                    disabled>
                                    <option value="" selected>اختر الموديل</option>
                                </select>
                                <select name="cars[{{ $i }}][term_id]" class="mb-4 form_group_select js-term"
                                    disabled>
                                    <option value="" selected>اختر الفئة</option>
                                </select>
                            </div>
                        @endfor
                        <div class="flex justify-center mt-3 w-full">
                            <button type="submit" class="form_button" id="compareSubmit">قارن</button>
                        </div>
                    </div>
                </form>
            </div>
        </section>
        <section class="custom-section pb-0" id="cars-compare" >
            <div class="container">
                <div>
                    <div class="flex-div mb-5">
                        <div class="px-2 w-full sm:mb-0 mb-5 sm:w-1/3 lg:w-1/4">
                            <h2 class="Carbrand w-full h-full font-medium text-[1.8rem] items-center">مقارنة السيارات</h2>
                             <div class="pt-5 md:block hidden">
                            <h3 class="comparesection_button mb-5">حمل مواصفات العربية</h3>
                            <h3 class="comparesection_button mb-5">متاح للحجز</h3>
                        </div>
                        </div>
                        <div class="px-2 w-full sm:w-2/3 lg:w-3/4">
                            <div class="flex-div justify-evenly">
                                @forelse($terms as $term)
                                    @php 
                                    $car = $term->model; 
                                        $catalogUrl = optional($term->model)->catalog_path;
                                        $canBook = (int) ($term->inventory ?? 0) > 0;
                                    @endphp
                                    <div class="mb-4 px-2 w-full sm:w-1/2 lg:w-1/4">
                                        <a href="{{ route('web.cars.carinfo', [$car->id, \unicode_slug($car->name, '-')]) }}"
                                            class="carbrandtype">
                                            <span class="w-full h-full">
                                                <img class="w-full h-full object-contain"
                                                    src="{{ $car->image_path ?? asset('img/compare/placeholder.png') }}"
                                                    alt="{{ $term->display_name }}">
                                            </span>
                                            <h2 class="font-medium text-[1.2rem]">{{ $term->display_name }}</h2>
                                            <h5>{{ $term->price_formatted }}</h5>
                                        </a>
                                        <div class="pt-5">
                                        @if ($catalogUrl)
                                            <a href="{{ $catalogUrl }}" download
                                                class="mb-5 block w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center gap-2 text-sm font-medium text-black">
                                                حمل الآن
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                                </svg>
                                            </a>
                                        @else
                                            <div class="mb-5 w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center gap-2 text-sm font-medium text-gray-400 cursor-not-allowed"
                                                aria-disabled="true">
                                                لا يوجد كتالوج
                                            </div>
                                        @endif
                                        @if ($canBook)
                                            <a href="{{ route('web.booking', ['id' => optional($term->model)->id, 'term_id' => $term->id]) }}"
                                                class="mb-5 w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center gap-2 text-sm font-medium text-black">
                                                احجز الآن
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </a>
                                        @else
                                            <div class="mb-5 w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center text-sm font-medium text-gray-400 cursor-not-allowed"
                                                aria-disabled="true">
                                                غير متاح للحجز
                                            </div>
                                        @endif
                                    </div>
                                    </div>
                                @empty
                                    <div class="text-gray-500 px-2 py-6">اختر حتى 4 سيارات من الأعلى للمقارنة.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
            @if ($terms->isNotEmpty())
            <section class="custom-section pt-0 hidden" id="cars-compare-actions">
                <div class="container">
                    <div class="flex-div mb-5 ">
                        <div class="px-2 w-full sm:mb-0 mb-5 sm:w-1/3 lg:w-1/4 md:block hidden">
                            <h3 class="comparesection_button mb-5">حمل مواصفات العربية</h3>
                            <h3 class="comparesection_button mb-5">متاح للحجز</h3>
                        </div>
                        <div class="px-2 w-full sm:w-2/3 lg:w-3/4">
                            <div class="flex-div justify-evenly">
                                @foreach ($terms as $term)
                                    @php
                                        $catalogUrl = optional($term->model)->catalog_path;
                                        $canBook = (int) ($term->inventory ?? 0) > 0;
                                    @endphp
                                    <div class="px-2 w-full sm:w-1/2 lg:w-1/4">
                                        @if ($catalogUrl)
                                            <a href="{{ $catalogUrl }}" download
                                                class="mb-5 block w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center gap-2 text-sm font-medium text-black">
                                                حمل الآن
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
                                                </svg>
                                            </a>
                                        @else
                                            <div class="mb-5 w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center gap-2 text-sm font-medium text-gray-400 cursor-not-allowed"
                                                aria-disabled="true">
                                                لا يوجد كتالوج
                                            </div>
                                        @endif
                                        @if ($canBook)
                                            <a href="{{ route('web.booking', ['id' => optional($term->model)->id, 'term_id' => $term->id]) }}"
                                                class="mb-5 w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center gap-2 text-sm font-medium text-black">
                                                احجز الآن
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-600"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7" />
                                                </svg>
                                            </a>
                                        @else
                                            <div class="mb-5 w-full h-[56px] lieanerBg shadow rounded flex items-center justify-center text-sm font-medium text-gray-400 cursor-not-allowed"
                                                aria-disabled="true">
                                                غير متاح للحجز
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
        <section class="tables" id="cars-compare-top-features">
            <div class="container">
                @if ($terms->isNotEmpty())
                    <div class="overflow-x-auto w-full mb-12">
                        <table class="w-full border-collapse text-center">
                            <thead>
                                <tr class="bg-red-600 text-white">
                                    <th class="p-3 border">مواصفات السيارة</th>
                                    @foreach ($terms as $term)
                                        <th class="p-3 border">{{ $term->display_name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($topSpecsRows as $row)
                                    <tr class="{{ $loop->odd ? 'bg-gray-50' : '' }}">
                                        <td class="p-3 border">{{ $row['label'] }}</td>
                                        @foreach ($terms as $term)
                                            @php $val = $term->top[$row['key']] ?? null; @endphp
                                            <td class="p-3 border">{{ $val !== null && $val !== '' ? $val : '—' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                @if ($terms->isNotEmpty() && $featureRowBlocks->isNotEmpty())
                    @foreach ($featureRowBlocks as $block)
                        @php
                            $title = $block['title'];
                            $rows = $block['rows'];
                        @endphp

                        <div class="overflow-x-auto w-full mb-12">
                            <table class="min-w-full border border-gray-300 text-sm text-center bg-white">
                                <thead>
                                    <tr class="bg-red-600 text-white font-bold">
                                        <th class="p-3 border border-gray-300">{{ $title }}</th>
                                        @foreach ($terms as $term)
                                            <th class="p-3 border border-gray-300">{{ $term->display_name }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $r)
                                        <tr class="{{ $loop->odd ? 'bg-gray-50' : '' }}">
                                            <td class="p-3 border">{{ $r['label'] }}</td>
                                            @foreach ($terms as $term)
                                                @php
                                                    $f = $term->feat_by_label[$r['key']] ?? null;
                                                    $value = $f['value'] ?? null;
                                                    $status = $f['status'] ?? false;
                                                @endphp
                                                <td class="p-3 border">
                                                    @if ($value && trim($value) !== '')
                                                        {{ $value }}
                                                    @else
                                                        @if ($status)
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="mx-auto h-4 w-4 text-green-600" fill="none"
                                                                viewBox="0 0 24 24" stroke="#232323">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="3" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="mx-auto h-4 w-4 text-gray-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="#232323">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="3" d="M5 12h14" />
                                                            </svg>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

    </main>
@endsection
@push('scripts-js')
    <script>
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
    <script>
        window.ROUTES = {
            MODELS_BY_BRAND: @json(route('web.brands.models', ['brand' => 'id_placeholder'])),
            TERMS_BY_CAR: @json(route('web.models.terms', ['car' => 'id_placeholder'])),
        };
        window.PREFILL = @json($prefill ?? []);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.comparesection form').forEach(form => {
                form.addEventListener('submit', e => {
                    const groups = form.querySelectorAll('.car-group');
                    let selectedTerms = 0;
                    groups.forEach(g => {
                        const term = g.querySelector('.js-term');
                        if (term && term.value) selectedTerms++;
                    });
                    if (selectedTerms < 2) {
                        e.preventDefault();
                        groups.forEach(g => {
                            const term = g.querySelector('.js-term');
                            if (!term || !term.value) {
                                g.classList.add('ring-2', 'ring-red-100', 'rounded-md');
                            } else {
                                g.classList.remove('ring-2', 'ring-red-100', 'rounded-md');
                            }
                        });

                        if (window.Swal) {
                            Swal.fire({
                                title: 'اختيارات غير كافية',
                                text: 'من فضلك اختر على الأقل سيارتين للمقارنة.',
                                icon: 'warning',
                                confirmButtonText: 'حسناً',
                                confirmButtonColor: '#d03b37'
                            });
                        } else {
                            alert('من فضلك اختر على الأقل سيارتين للمقارنة.');
                        }
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const groups = document.querySelectorAll('.car-group');
            const resetSelect = (sel, placeholder) => {
                sel.innerHTML = `<option value="" selected>${placeholder}</option>`;
            };
            const fetchModelsByBrand = async (brandId, modelSel) => {
                resetSelect(modelSel, 'اختر الموديل');
                if (!brandId) {
                    modelSel.disabled = true;
                    return [];
                }
                const url = window.ROUTES.MODELS_BY_BRAND.replace('id_placeholder', brandId);
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                (data.models || []).forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.id;
                    opt.textContent = m.name;
                    modelSel.appendChild(opt);
                });
                modelSel.disabled = (data.models || []).length === 0;
                return data.models || [];
            };
            const fetchTermsByModel = async (modelId, termSel) => {
                resetSelect(termSel, 'اختر الفئة');
                if (!modelId) {
                    termSel.disabled = true;
                    return [];
                }
                const url = window.ROUTES.TERMS_BY_CAR.replace('id_placeholder', modelId);
                const res = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await res.json();
                (data.terms || []).forEach(t => {
                    const opt = document.createElement('option');
                    opt.value = t.id;
                    opt.textContent = t.term_name;
                    termSel.appendChild(opt);
                });
                termSel.disabled = (data.terms || []).length === 0;
                return data.terms || [];
            };
            const wireGroup = (group) => {
                const brandSel = group.querySelector('.js-brand');
                const modelSel = group.querySelector('.js-model');
                const termSel = group.querySelector('.js-term');
                const resetState = () => {
                    resetSelect(modelSel, 'اختر الموديل');
                    resetSelect(termSel, 'اختر الفئة');
                    modelSel.disabled = true;
                    termSel.disabled = true;
                };
                resetState();
                brandSel.addEventListener('change', async () => {
                    resetSelect(termSel, 'اختر الفئة');
                    termSel.disabled = true;
                    await fetchModelsByBrand(brandSel.value, modelSel);
                });
                modelSel.addEventListener('change', async () => {
                    await fetchTermsByModel(modelSel.value, termSel);
                });
            };
            groups.forEach(wireGroup);
            (async function prefillAll() {
                (window.PREFILL || []).forEach(async (row, idx) => {
                    const group = groups[idx];
                    if (!group) return;
                    const brandSel = group.querySelector('.js-brand');
                    const modelSel = group.querySelector('.js-model');
                    const termSel = group.querySelector('.js-term');
                    if (row.brand_id) {
                        brandSel.value = row.brand_id;
                        await fetchModelsByBrand(row.brand_id, modelSel);
                        if (row.model_id) {
                            modelSel.value = row.model_id;
                            await fetchTermsByModel(row.model_id, termSel);
                            if (row.term_id) {
                                termSel.value = row.term_id;
                            }
                        }
                    }
                });
            })();
        });
    </script>
@endpush
