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
        <section class="banner">
            <img alt="Banner Image serviceBanner" src="img/service-center/bg.jpg" />
        </section>
        <section class="serviceCenter">
            <div class="container">
                <h2 class="custom_title">
                    <span>مراكز الخدمة</span>
                </h2>
                <div class="serviceCenter_brands">
                    @foreach ($brands as $brand)
                        <div class="serviceCenter_brands_brand">
                            <button class="serviceCenter_brands_brand_button" data-brand-id="{{ $brand->id }}">
                                <img alt="{{ $brand->name }}" src="{{ $brand->logo_path }}" />
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="branchesSection">
            <div class="container">
                <h5 class="branchesSection_title">اختر اقرب فرع:</h5>
                <div class="branchesSection_map">
                    <div class="md:flex block mb-4">

                        <div class="branchesSection_map_select">
                            <div class="custom-select-wrapper">
                                <select id="citySelect" class="custom-select" disabled>
                                    <option selected disabled value="">- اختر المحافظة -</option>
                                </select>
                            </div>
                        </div>
                        <div class="branchesSection_map_select">
                            <div class="custom-select-wrapper">
                                <select id="branchSelect" class="custom-select" disabled>
                                    <option selected disabled value="">- اختر الفرع -</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="branchPlaceholder" class="w-full text-center py-10">
                        يرجى اختيار الماركة والمحافظة ثم الفرع لعرض تفاصيل مركز الخدمة.
                    </div>
                    <div id="branchDetails" class="hidden flex md:flex-row flex-col">
                        <div class="branchesSection_map_locations" id="map"></div>
                        <div class="allLocations">
                            <div class="header">
                                <h3 id="branchName" class="mb-2"></h3>
                            </div>
                            <div class="items">
                                <div class="item">
                                    <a href="#" target="_blank" class="center1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="21"
                                            viewBox="0 0 13 21" fill="none">
                                            <path
                                                d="M6.14648 0.078125C2.93738 0.078125 0.335938 3.08457 0.335938 6.79315C0.335938 10.5018 6.14648 20.4471 6.14648 20.4471C6.14648 20.4471 11.9568 10.5018 11.9568 6.79315C11.9568 3.08457 9.35547 0.078125 6.14648 0.078125ZM6.14648 10.7262C4.17517 10.7262 2.57715 8.87944 2.57715 6.60129C2.57715 4.32314 4.17517 2.4764 6.14648 2.4764C8.11768 2.4764 9.71582 4.32314 9.71582 6.60129C9.71582 8.87944 8.11768 10.7262 6.14648 10.7262Z"
                                                fill="#D03B37" />
                                            <path
                                                d="M6.14539 20.5859L5.86768 20.13C5.85315 20.1059 4.39124 17.6992 2.9491 14.8881C0.992188 11.0738 0 8.35824 0 6.81678C0 5.89669 0.162476 5.00395 0.483032 4.16328C0.792603 3.35152 1.2356 2.62256 1.79993 1.99657C2.36414 1.37072 3.02136 0.879263 3.75317 0.535939C4.51099 0.180294 5.3158 0 6.14539 0C6.97473 0 7.77966 0.180294 8.53748 0.535939C9.26929 0.879263 9.92639 1.37072 10.4907 1.99657C11.0551 2.62256 11.498 3.35152 11.8076 4.16328C12.1282 5.00395 12.2906 5.89669 12.2906 6.81678C12.2906 8.35824 11.2983 11.0738 9.34167 14.8881C7.89941 17.6992 6.43738 20.1059 6.42297 20.13L6.14539 20.5859ZM6.14539 0.743044C3.1261 0.743044 0.6698 3.4677 0.6698 6.81678C0.6698 8.2074 1.65857 10.8699 3.52917 14.5166C4.58655 16.5779 5.65491 18.4202 6.14539 19.2495C6.63586 18.4202 7.7041 16.5779 8.7616 14.5166C10.6321 10.8699 11.6207 8.2074 11.6207 6.81678C11.6207 3.4677 9.16443 0.743044 6.14539 0.743044ZM6.14539 10.9633C5.61853 10.9633 5.10706 10.8488 4.62561 10.6229C4.16052 10.4047 3.74304 10.0925 3.38464 9.69491C3.02625 9.29729 2.74475 8.8342 2.5481 8.31837C2.34436 7.78432 2.24109 7.21711 2.24109 6.63262C2.24109 6.04814 2.34436 5.48092 2.5481 4.94688C2.74475 4.43105 3.02625 3.96802 3.38464 3.57033C3.74304 3.17271 4.16052 2.86047 4.62561 2.64239C5.10706 2.41647 5.61853 2.30198 6.14539 2.30198C6.67224 2.30198 7.18359 2.41647 7.66504 2.64239C8.13013 2.86047 8.54749 3.17271 8.90601 3.57033C9.2644 3.96802 9.5459 4.43105 9.74243 4.94688C9.94617 5.48092 10.0494 6.04814 10.0494 6.63262C10.0494 7.21711 9.94617 7.78432 9.74243 8.31837C9.5459 8.8342 9.2644 9.29729 8.90601 9.69491C8.54749 10.0925 8.13013 10.4047 7.66504 10.6229C7.18359 10.8488 6.67224 10.9633 6.14539 10.9633ZM6.14539 3.04496C4.36182 3.04496 2.91101 4.6544 2.91101 6.63262C2.91101 8.61091 4.36182 10.2203 6.14539 10.2203C7.92883 10.2203 9.37976 8.61091 9.37976 6.63262C9.37976 4.6544 7.92883 3.04496 6.14539 3.04496Z"
                                                fill="#D03B37" />
                                        </svg>
                                        <span class="text">
                                            <p id="branchAddress" class="mb-1"></p>
                                        </span>
                                    </a>
                                    <a href="tel:" class="center1">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="19"
                                            viewBox="0 0 13 19" fill="none">
                                            <path
                                                d="M1.00204 16.567L2.22909 13.9391C2.41293 13.5454 2.8121 13.2913 3.24618 13.2913C3.3536 13.2913 3.46102 13.307 3.56454 13.3375L5.02328 13.7217C5.23324 13.5408 5.90487 12.8181 7.10214 10.2542C8.30624 7.67505 8.42514 6.70654 8.42758 6.43542L7.19491 5.56262C6.73568 5.25769 6.56552 4.65259 6.79891 4.15259L8.02572 1.52441C8.21688 1.11572 8.63217 0.851562 9.08407 0.851562C9.30795 0.851562 9.5267 0.91626 9.71591 1.03882L10.4498 1.51331C11.1012 1.91553 11.5604 2.5271 11.8145 3.33044C12.0382 4.03687 12.1034 4.89612 12.0086 5.88464C11.8492 7.54712 11.2215 9.61926 10.2408 11.7196C9.39218 13.5369 8.36337 15.1831 7.34335 16.3547C6.04306 17.849 4.76278 18.6068 3.53866 18.6068C3.20175 18.6068 2.86752 18.5479 2.54599 18.4313L1.71469 18.1746C1.39242 18.0751 1.12924 17.8412 0.992518 17.533C0.856044 17.2246 0.859462 16.8727 1.00204 16.567Z"
                                                fill="#D03B37" />
                                        </svg>
                                        <span class="text">
                                            <p id="branchPhone" class="mb-1"></p>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
@push('scripts-js')
    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB2oKnoBeDDRxl3hqwuoyx7k8lKrjPRf0w&callback=initMap&libraries=&v=weekly"
        async></script>
    <script>
        window.APP_BASE = @json(rtrim(url('/'), '/'));

        const API = {
            cities: (brandId) => `${APP_BASE}/api/service-centers/brands/${brandId}/cities`,
            branches: (brandId, cityId) =>
                `${APP_BASE}/api/service-centers/brands/${brandId}/cities/${cityId}/branches`,
        };
    </script>
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
        const brandButtons = document.querySelectorAll('.serviceCenter_brands_brand_button');
        const citySelect = document.getElementById('citySelect');
        const branchSelect = document.getElementById('branchSelect');
        const branchDetails = document.getElementById('branchDetails');
        const branchPlaceholder = document.getElementById('branchPlaceholder');
        const branchName = document.getElementById('branchName');
        const branchAddress = document.getElementById('branchAddress');
        const branchPhone = document.getElementById('branchPhone');
        let map, marker, branchesData = [],
            selectedBrandId;

        brandButtons.forEach(btn => {
            btn.addEventListener('click', async function() {
                const brandId = this.dataset.brandId;
                selectedBrandId = brandId;
                citySelect.innerHTML = '<option selected disabled value="">- اختر المحافظة -</option>';
                citySelect.disabled = true;
                branchSelect.innerHTML = '<option selected disabled value="">- اختر الفرع -</option>';
                branchSelect.disabled = true;
                branchPlaceholder.classList.remove('hidden');
                branchDetails.classList.add('hidden');
                if (!brandId) return;

                const res = await fetch(API.cities(brandId), {
                    credentials: 'same-origin'
                });
                const data = await res.json();
                (data.data || []).forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.name;
                    citySelect.appendChild(opt);
                });
                citySelect.disabled = false;
            });
        });

        citySelect.addEventListener('change', async function() {
            const brandId = selectedBrandId;
            const cityId = this.value;
            branchSelect.innerHTML = '<option selected disabled value="">- اختر الفرع -</option>';
            branchSelect.disabled = true;
            branchPlaceholder.classList.remove('hidden');
            branchDetails.classList.add('hidden');
            if (!brandId || !cityId) return;

            const res = await fetch(API.branches(brandId, cityId), {
                credentials: 'same-origin'
            });
            const data = await res.json();
            branchesData = data.data || [];
            branchesData.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                branchSelect.appendChild(opt);
            });
            branchSelect.disabled = false;
        });

        branchSelect.addEventListener('change', function() {
            const branchId = this.value;
            const branch = branchesData.find(b => b.id == branchId);
            if (!branch) return;
            branchPlaceholder.classList.add('hidden');
            branchDetails.classList.remove('hidden');
            branchName.textContent = branch.name;
            branchAddress.textContent = branch.address;
            branchPhone.textContent = branch.phone;
            if (branch.latitude && branch.longitude && window.google) {
                updateMap(branch.latitude, branch.longitude, branch.location);
            }
        });

        function initMap(lat = 30.0444, lng = 31.2357) {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 15,
                center: {
                    lat: parseFloat(lat),
                    lng: parseFloat(lng)
                },
            });
        }

        function updateMap(lat, lng, url) {
            const position = {
                lat: parseFloat(lat),
                lng: parseFloat(lng)
            };
            if (!map) {
                initMap(lat, lng);
            } else {
                map.setCenter(position);
                map.setZoom(15);
            }
            if (marker) {
                marker.setMap(null);
            }
            marker = new google.maps.Marker({
                position: position,
                map: map,
                icon: 'img/global/pin.png'
            });
            if (url) {
                marker.addListener('click', () => window.open(url));
            }
        }
    </script>
@endpush
