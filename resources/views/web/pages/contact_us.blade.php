@extends('web.layouts.master')

@push('meta')
    <!-- SEO Meta -->
    <!-- Basic Page Needs -->
    <meta charset="UTF-8" />
    <!-- description -->
    <meta name="description" content="description" />
    <!-- author -->
    <meta name="author" content="Icon Creations" />
    <!-- Mobile Specific Meta -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- IE Browser Support -->
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <!-- upper bar color for mobile -->
    <meta content="#627E90" name="theme-color" />

    <!-- Page Title -->
    <title>بريم</title>
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
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="contact_box" data-bs-toggle="modal" data-bs-target="#advertiseModal">
                            <div class="image">
                                <img class="imageone" src="img/pc.png" alt="">
                                <img class="imagetwo" src="img/pc2.png" alt="">
                            </div>
                            <div class="desc">
                                <p>اعرض إعلانك على شاشتنا</p>
                            </div>
                        </div>
                    </div>
                    <div class="modal fade" id="advertiseModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content p-4 position-relative">

                                <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                                    data-bs-dismiss="modal" aria-label="Close" style="z-index: 30;">X</button>

                                <div class="modal-header border-0">
                                    <h5 class="modal-title w-100 text-center text-teal fw-bold">
                                        طلب عرض إعلان
                                    </h5>
                                </div>

                                <!-- الفورم -->
                                <div class="modal-body">
                                    <form>
                                        <div class="mb-3">
                                            <label class="form-label">الاسم / الشركة <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                                            <input type="tel" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">البريد الإلكتروني<span
                                                    class="text-danger">*</span></label>
                                            <input type="email" class="form-control">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">هل تريد تصوير الإعلان قبل عرضه؟</label>
                                            <div class="d-flex gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="adOption"
                                                        id="option1">
                                                    <label class="form-check-label" for="option1">
                                                        تصوير إعلان من قبل شركتنا
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="adOption"
                                                        id="option2">
                                                    <label class="form-check-label" for="option2">
                                                        لدي إعلان أريد عرضه
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">عدد فروع شركتكم <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">مدة العرض <span
                                                        class="text-danger">*</span></label>
                                                <select class="form-select">
                                                    <option>اختر المدة</option>
                                                    <option>يوم</option>
                                                    <option>اسبوع</option>
                                                    <option>شهر</option>
                                                    <option>3 أشهر</option>
                                                    <option>6 أشهر</option>
                                                    <option>سنة</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">نوع النشاط</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">عدد العملاء المستهدفة</label>
                                                <select class="form-select">
                                                    <option>اختر العدد</option>
                                                    <option>50,000 الى 100,000</option>
                                                    <option>100,000 الى 500,000</option>
                                                    <option>500,000 الى 800,000</option>
                                                    <option>800,000 الى مليون</option>
                                                    <option>اكتر من مليون</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">الأماكن المطلوب عرض الإعلان بها</label>
                                            <select class="form-select" multiple="multiple" id="modelselect">
                                                <option value="مول">مول</option>
                                                <option value="كافيه">كافيه</option>
                                                <option value="نادي">نادي</option>
                                                <option value="مطعم">مطعم</option>
                                                <option value="محل تجاري">محل تجاري</option>
                                            </select>
                                        </div>
                                        <div class="mb-3 d-flex flex-column">
                                            <label class="form-label">الأماكن المطلوب عرض الإعلان بها</label>
                                            <textarea id="w3review" name="w3review" rows="4" cols="50"></textarea>
                                        </div>

                                        <div class="text-center mt-4">
                                            <button type="submit" class="btn px-5"
                                                style="background:#41A8A6; color:white; border-radius:10px;">
                                                إرسال
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="contact_box" data-bs-toggle="modal" data-bs-target="#advertiseModal2">
                            <div class="image">
                                <img class="imageone" src="img/25.png" alt="">
                                <img class="imagetwo" src="img/27.png" alt="">
                            </div>
                            <div class="desc">
                                <p>انضم الي شبكة شاشاتنا</p>
                            </div>
                        </div>
                        <div class="modal fade" id="advertiseModal2" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content p-4 position-relative">

                                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                                        data-bs-dismiss="modal" aria-label="Close" style="z-index: 30;">X</button>

                                    <div class="modal-header border-0">
                                        <h5 class="modal-title w-100 text-center text-teal fw-bold">
                                            انضم الي شبكة شاشاتنا
                                        </h5>
                                    </div>

                                    <!-- الفورم -->
                                    <div class="modal-body">
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">الاسم / الشركة <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">رقم الجوال <span
                                                        class="text-danger">*</span></label>
                                                <input type="tel" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">البريد الإلكتروني<span
                                                        class="text-danger">*</span></label>
                                                <input type="email" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">عدد الشاشات</label>
                                                <input type="text" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">هل تريد تصوير الإعلان قبل عرضه؟</label>
                                                <div class="d-flex gap-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="adOption"
                                                            id="option1">
                                                        <label class="form-check-label" for="option1">
                                                            لديك شاشات للعرض
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="adOption"
                                                            id="option2">
                                                        <label class="form-check-label" for="option2">
                                                            تحتاج شاشات
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">عدد فروع شركتكم <span
                                                            class="text-danger">*</span></label>
                                                    <input type="number" class="form-control">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">متوسط عدد العملاء اليومي <span
                                                            class="text-danger">*</span></label>
                                                    <select class="form-select">
                                                        <option>اختر العدد</option>
                                                        <option>50,000 الى 100,000</option>
                                                        <option>100,000 الى 500,000</option>
                                                        <option>500,000 الى 800,000</option>
                                                        <option>800,000 الى مليون</option>
                                                        <option>اكتر من مليون</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3 d-flex flex-column">
                                                <label class="form-label">تفاصيل</label>
                                                <textarea id="w3review" name="w3review" rows="4" cols="50"></textarea>
                                            </div>

                                            <div class="text-center mt-4">
                                                <button type="submit" class="btn px-5"
                                                    style="background:#41A8A6; color:white; border-radius:10px;">
                                                    إرسال
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="contact_box" data-bs-toggle="modal" data-bs-target="#advertiseModal3">
                            <div class="image">
                                <img class="imageone" src="img/screen.png" alt="">
                                <img class="imagetwo" src="img/screen2.png" alt="">
                            </div>
                            <div class="desc">
                                <p>تصوير اعلان</p>
                            </div>
                        </div>
                        <div class="modal fade" id="advertiseModal3" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content p-4 position-relative">

                                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                                        data-bs-dismiss="modal" aria-label="Close" style="z-index: 30;">X</button>

                                    <div class="modal-header border-0">
                                        <h5 class="modal-title w-100 text-center text-teal fw-bold">
                                            تصوير اعلان
                                        </h5>
                                    </div>

                                    <!-- الفورم -->
                                    <div class="modal-body">
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">الاسم / الشركة <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">رقم الجوال <span
                                                        class="text-danger">*</span></label>
                                                <input type="tel" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">البريد الإلكتروني<span
                                                        class="text-danger">*</span></label>
                                                <input type="email" class="form-control">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">نوع النشاط</label>
                                                <input type="text" class="form-control">
                                            </div>
                                            <div class="mb-3 d-flex flex-column">
                                                <label class="form-label">تفاصيل</label>
                                                <textarea id="w3review" name="w3review" rows="4" cols="50"></textarea>
                                            </div>
                                            <div class="text-center mt-4">
                                                <button type="submit" class="btn px-5"
                                                    style="background:#41A8A6; color:white; border-radius:10px;">
                                                    إرسال
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="contact_box" data-bs-toggle="modal" data-bs-target="#advertiseModal4">
                            <div class="image">
                                <img class="imageone" src="img/faqs.png" alt="">
                                <img class="imagetwo" src="img/faqs2.png" alt="">
                            </div>
                            <div class="desc">
                                <p>إرسال إستفسار</p>
                            </div>
                        </div>
                        <div class="modal fade" id="advertiseModal4" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content p-4 position-relative">

                                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3"
                                        data-bs-dismiss="modal" aria-label="Close" style="z-index: 30;">X</button>

                                    <div class="modal-header border-0">
                                        <h5 class="modal-title w-100 text-center text-teal fw-bold">
                                            تصوير اعلان
                                        </h5>
                                    </div>

                                    <div class="modal-body">
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">الاسم / الشركة <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">رقم الجوال <span
                                                        class="text-danger">*</span></label>
                                                <input type="tel" class="form-control">
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">البريد الإلكتروني<span
                                                        class="text-danger">*</span></label>
                                                <input type="email" class="form-control">
                                            </div>

                                            <div class="mb-3 d-flex flex-column">
                                                <label class="form-label">تفاصيل</label>
                                                <textarea id="w3review" name="w3review" rows="4" cols="50"></textarea>
                                            </div>

                                            <div class="text-center mt-4">
                                                <button type="submit" class="btn px-5"
                                                    style="background:#41A8A6; color:white; border-radius:10px;">
                                                    إرسال
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-multiselect/0.9.15/js/bootstrap-multiselect.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            $('#modelselect').multiselect({
                includeSelectAllOption: true,
                selectAllText: 'تحديد الكل',
                allSelectedText: 'تم تحديد الكل',
                nonSelectedText: 'اختر الأماكن',
                buttonWidth: '100%',
                buttonClass: 'btn btn-light form-select',
                maxHeight: 200,
                numberDisplayed: 5,
                enableHTML: true,
                buttonContainer: '<div class="btn-group w-100" />',
                templates: {
                    button: '<button type="button" class="multiselect dropdown-toggle" data-bs-toggle="dropdown"><span class="multiselect-selected-text"></span> <b class="caret"></b></button>'
                }
            });
        });
    </script>
@endpush
