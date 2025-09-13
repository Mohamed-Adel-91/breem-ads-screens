@extends('web.layouts.master')

<!-- SEO Meta -->
@push('meta')
    <!-- Page Title -->
    <title>بريم | تواصل معنا</title>
    <!-- description -->
    <meta name="description" content="description" />
@endpush

@section('content')
    <!-- Main Content -->
    <main>
        <section class="second_banner">
            <div class="banner_image">
                <img src="img/contact.png" alt="">
            </div>
        </section>

        <section class="contact_us mt-5">
            <div class="container">
                <h2>تواصل معنا</h2>
                <p>اختار الخدمة التى تناسبك.</p>
                <div class="row">
                    @include('web.pages.contact-forms.ads-subscribe')

                    @include('web.pages.contact-forms.screens-subscribe')

                    @include('web.pages.contact-forms.ads-creating-subscribe')

                    @include('web.pages.contact-forms.faqs')
                </div>
            </div>
        </section>

        <section class="map">
            <div class="back_image">
                <img class="bann" src="img/map.png" alt="">
                <div class="overlay"></div>
                <div class="map_content">
                    <h3> موقعنا</h3>
                    <p><img src="img/loc.png" alt="">
                        <span>
                            شارع بني تميم متفرع من الملك فهد – حي المروج، مبنى رقم 2174، الدور الخامس الرمز البريدي 12282 –
                            الرياض،
                            المملكة العربية السعودية.

                        </span>
                    </p>
                    <p class="contact mt-5">
                        <span>
                            <img src="img/phone.png" alt="">
                            رقم جوال : ۹۹٦٥٤۳۳٤+</span>
                    </p>
                    <p class="contact">
                        <span><img src="img/whats.png" alt=""> رقم الواتس اب : ۹۹٦٥٤۳۳٤+</span>
                    </p>
                </div>
            </div>
        </section>


        <section class="w-100">
            <img class="w-100" src="img/banner_sa.png" alt="">
        </section>
    </main>
@endsection

@push('scripts-js')
    <script>
        $(function() {
            if (typeof $.fn.multiselect !== 'function') {
                console.error('bootstrap-multiselect لم يتم تحميله.');
                return;
            }

            $('#modelselect').multiselect({
                includeSelectAllOption: true,
                selectAllText: 'تحديد الكل',
                allSelectedText: 'تم تحديد الكل',
                nonSelectedText: 'اختر الأماكن',
                buttonWidth: '100%',
                buttonClass: 'btn btn-light w-100 text-start',
                maxHeight: 200,
                numberDisplayed: 3,
                buttonContainer: '<div class="btn-group w-100" />',
                templates: {
                    button: '<button type="button" class="multiselect dropdown-toggle w-100 text-start" data-bs-toggle="dropdown">' +
                        '<span class="multiselect-selected-text"></span> <b class="caret"></b>' +
                        '</button>'
                }
            });
        });
    </script>
@endpush
