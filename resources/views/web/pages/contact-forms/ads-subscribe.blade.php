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

            <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"
                aria-label="Close" style="z-index: 30;">X</button>

            <div class="modal-header border-0">
                <h5 class="modal-title w-100 text-center text-teal fw-bold">
                    طلب عرض إعلان
                </h5>
            </div>

            <div class="modal-body">
                <form method="POST" action="{{ route('web.contact.submit', ['lang' => app()->getLocale(), 'type' => 'ads']) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">الاسم / الشركة <span class="text-danger">*</span></label>
                        <input name="name" type="text" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">رقم الجوال <span class="text-danger">*</span></label>
                        <input name="phone" type="tel" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني<span class="text-danger">*</span></label>
                        <input name="email" type="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">هل تريد تصوير الإعلان قبل عرضه؟</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ad_production" value="produce" id="option1">
                                <label class="form-check-label" for="option1">
                                    تصوير إعلان من قبل شركتنا
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="ad_production" value="have" id="option2">
                                <label class="form-check-label" for="option2">
                                    لدي إعلان أريد عرضه
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">عدد فروع شركتكم <span class="text-danger">*</span></label>
                            <input name="branches_count" type="number" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">مدة العرض <span class="text-danger">*</span></label>
                            <select name="duration" class="form-select">
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
                            <input name="business_type" type="text" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">عدد العملاء المستهدفة</label>
                            <select name="target_customers" class="form-select">
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
                        <select name="places[]" multiple="multiple" id="modelselect">
                            <option value="مول">مول</option>
                            <option value="كافيه">كافيه</option>
                            <option value="نادي">نادي</option>
                            <option value="مطعم">مطعم</option>
                            <option value="محل تجاري">محل تجاري</option>
                        </select>
                    </div>
                    <div class="mb-3 d-flex flex-column">
                        <label class="form-label">اضافة تفاصيل</label>
                        <textarea id="w3review" name="details" rows="4" cols="50"></textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn px-5" style="background:#41A8A6; color:white; border-radius:10px;">إرسال</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

