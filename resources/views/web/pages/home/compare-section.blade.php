<!-- Compare Section -->
<section class="comparesection custom-section">
    <div class="container">
        <div class="hidden sm:block carimg">
            <span>
                <img src="img/homepage/car2.png" alt="car image" />
            </span>
        </div>
        <h2 class="custom_title">
            <span>مقارنة السيارات</span>
        </h2>
        <form class="w-full  sm:w-3/4 form" action="{{ route('web.comparison') }}" method="GET" enctype="multipart/form-data">
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
<!-- Advertisement Label -->
<section class="ads w-full py-6 lg:block hidden">
    <!-- Centered Image -->
    <div class="largeImg">
        <p class="text-xs text-gray-400 text-right">
            ADVERTISEMENT
        </p>
        <!-- Home First_banner [async] Bridgestone-->
        <script type="text/javascript">
            if (!window.AdButler) {
                (function() {
                    var s = document.createElement("script");
                    s.async = true;
                    s.type = "text/javascript";
                    s.src = 'https://servedby.adfyre.co/app.js';
                    var n = document.getElementsByTagName("script")[0];
                    n.parentNode.insertBefore(s, n);
                }());
            }
        </script>
        <script type="text/javascript">
            var AdButler = AdButler || {};
            AdButler.ads = AdButler.ads || [];
            var abkw = window.abkw || '';
            var plc994381 = window.plc994381 || 0;
            document.write('<' + 'div id="placement_994381_' + plc994381 + '"></' + 'div>');
            AdButler.ads.push({
                handler: function(opt) {
                    AdButler.register(188745, 994381, [970, 250], 'placement_994381_' + opt.place, opt);
                },
                opt: {
                    place: plc994381++,
                    keywords: abkw,
                    domain: 'servedby.adfyre.co',
                    click: 'CLICK_MACRO_PLACEHOLDER'
                }
            });
        </script>
        <!-- <img src="img/ads/5.png" alt="Ad Image" class="max-w-full h-auto" /> -->
    </div>

</section>

@push('scripts-js')
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
                                g.classList.add('ring-2', 'ring-red-100','rounded-md');
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
