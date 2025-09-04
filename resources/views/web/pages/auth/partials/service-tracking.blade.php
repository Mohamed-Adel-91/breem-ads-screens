<div class="tab-content services content">
    <div class="max-w-4xl mx-auto text-center">
        <!-- Title -->
        <h2 class="services_title">طلب تقسيط سيارة</h2>
        <p class="services_secTitle">رقم الطلب الخاص بك</p>
        <p class="services_thirdTitle">TX-716705434</p>

        <!-- Info Cards -->
        <div class="grid lg:grid-cols-2 gap-6">
            <!-- بيانات العميل -->
            <div class="customerData">
                <h2 class="customerData_title">بيانات العميل</h2>
                <div class="grid grid-cols-2 gap-y-2 text-right">
                    <div class="mb-2">
                        <p class="font-medium mb-2 text-black">الإسم</p>
                        <p class="customerData_desc">{{ $user->full_name }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-medium mb-2 text-black">نوع الهويه الشخصيه</p>
                        <p class="customerData_desc">بطاقة رقم قومي</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-medium mb-2 text-black">رقم المحمول</p>
                        <p class="customerData_desc">{{ $user->mobile }}</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-medium mb-2 text-black">رقم الهويه الشخصيه</p>
                        <p class="customerData_desc">************</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-medium mb-2 text-black">تاريخ الميلاد</p>
                        <p class="customerData_desc">1980-01-13</p>
                    </div>
                    <div class="mb-2">
                        <p class="font-medium mb-2 text-black">الإيميل</p>
                        <p class="customerData_desc">{{ $user->email }}</p>
                    </div>
                </div>
            </div>

            <!-- بيانات السيارة -->
            <div class="customerData">
                <h2 class="customerData_title">بيانات السيارة</h2>
                <div class="grid grid-cols-2 gap-y-2 text-right">
                    <div>
                        <p class="font-medium  mb-2 text-black">جيتور T1</p>
                        <p class="customerData_desc">موديل 2025</p>
                    </div>
                    <div>
                        <p class="font-medium  mb-2 text-black">سعر السيارة</p>
                        <p class="customerData_desc">1,725,000 جنيه</p>
                    </div>
                    <div>
                        <p class="font-medium  mb-2 text-black">مقدم</p>
                        <p class="customerData_desc">80,000 جنيه</p>
                    </div>
                    <div>
                        <p class="font-medium  mb-2 text-black">عدد الشهور</p>
                        <p class="customerData_desc">60 شهر</p>
                    </div>
                    <div>
                        <p class="font-medium  mb-2 text-black">اللون المفضل الاول</p>
                        <p class="customerData_desc">اسود</p>
                    </div>
                    <div>
                        <p class="font-medium  mb-2 text-black">اللون المفضل الثاني</p>
                        <p class="customerData_desc">أبيض</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
