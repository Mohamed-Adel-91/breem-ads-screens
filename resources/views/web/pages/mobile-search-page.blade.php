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
        <section class=" comparesection custom-section">
            <div class="container">
                <h2 class="custom_title">
                    <span>بحث</span>
                </h2>
                <form class="w-full form" action="{{ route('web.search-result') }}" method="GET" role="search"
                    aria-label="البحث عن السيارات" enctype="multipart/form-data">
                    @csrf
                    <div class="flex flex-wrap w-full -mx-2">
                        <div class="w-1/2 md:w-3/12 px-2 form_group">
                            <select id="mobileBrand" name="brandId" aria-label="اختر ماركة السيارة"
                                class="mb-4 form_group_select">
                                <option disabled value="" selected>الماركة</option>
                                @foreach ($navbarBrands ?? [] as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="w-1/2 md:w-3/12 px-2 form_group">
                            <select id="mobileModel" name="modelId"  class="mb-4 form_group_select" disabled>
                                <option value="">الموديل</option>
                            </select>
                        </div>
                        <div class="w-1/2 md:w-3/12 px-2 form_group">
                            <select id="mobileTerm" name="categoryId" class="mb-4 form_group_select" disabled>
                                <option value="">الفئة</option>
                            </select>
                        </div>
                        <div class="form_group w-1/2 md:w-3/12 px-2">
                            <input type="number" placeholder="السعر من" inputmode="numeric" name="priceFrom"
                                class="form_group_control">
                        </div>
                        <div class="form_group w-1/2 md:w-3/12 px-2">
                            <input type="number" placeholder="السعر إلي" inputmode="numeric" name="priceTo"
                                class="form_group_control">
                        </div>
                        <div class="flex justify-center mt-3 w-full">
                            <button type="submit" class="form_button">بحث</button>
                        </div>
                    </div>
                </form>
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
    <script>
        window.ROUTES = {
            MODELS_BY_BRAND: @json(route('web.brands.models', ['brand' => 'id_placeholder'])),
            TERMS_BY_CAR: @json(route('web.models.terms', ['car' => 'id_placeholder'])),
        };

        document.addEventListener('DOMContentLoaded', function() {
            const brandSel = document.getElementById('mobileBrand');
            const modelSel = document.getElementById('mobileModel');
            const termSel = document.getElementById('mobileTerm');

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
