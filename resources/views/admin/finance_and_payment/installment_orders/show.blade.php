@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')

        <div class="page-content">
            @include('admin.layouts.page-header')

            <div class="main-container">
                @include('admin.layouts.alerts')

                <div class="row gutters">
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                        <div class="table-container">

                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                                    <div class="mb-2">
                                        <h5 class="mb-1">
                                            طلب رقم:
                                            <span class="text-monospace">{{ $data['ref'] }}</span>
                                        </h5>
                                        <div class="text-muted small">
                                            <span class="me-3">أُنشئ:
                                                {{ optional($data['created_at'])->format('Y-m-d H:i') }}</span>
                                            <span>آخر تحديث: {{ optional($data['updated_at'])->format('Y-m-d H:i') }}</span>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge {{ $data['status_class'] }} fs-6">{{ $data['status_label'] }}</span>
                                        <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary ms-2">
                                            <i class="icon-arrow-left"></i> رجوع للقائمة
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="row gutters">
                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header"><strong>بيانات العميل</strong></div>
                                        <div class="card-body">
                                            <dl class="row mb-0">
                                                <dt class="col-5">الاسم</dt>
                                                <dd class="col-7">{{ $data['customer_name'] }}</dd>
                                                <dt class="col-5">الهاتف</dt>
                                                <dd class="col-7">{{ $data['customer_phone'] }}</dd>
                                                <dt class="col-5">الفرع</dt>
                                                <dd class="col-7">{{ $data['branch_name'] }}</dd>

                                                @if ((int) ($order->payment_type?->value ?? $order->payment_type) === \App\Enums\PaymentTypeEnum::CASH)
                                                    <dt class="col-5">البريد</dt>
                                                    <dd class="col-7">{{ $order->cash_individual_email ?: '-' }}</dd>
                                                    <dt class="col-5">الرقم القومي</dt>
                                                    <dd class="col-7">{{ $order->cash_national_id ?: '-' }}</dd>
                                                @else
                                                    @if ((int) ($order->customer_type?->value ?? $order->customer_type) === \App\Enums\ApplicableToEnum::INDIVIDUAL)
                                                        <dt class="col-5">البريد</dt>
                                                        <dd class="col-7">
                                                            {{ $order->installment_individual_email ?: '-' }}</dd>
                                                        <dt class="col-5">الرقم القومي</dt>
                                                        <dd class="col-7">{{ $order->installment_national_id ?: '-' }}
                                                        </dd>
                                                    @else
                                                        <dt class="col-5">الشركة</dt>
                                                        <dd class="col-7">{{ $order->installment_company_name ?: '-' }}
                                                        </dd>
                                                        <dt class="col-5">بريد الشركة</dt>
                                                        <dd class="col-7">{{ $order->installment_company_email ?: '-' }}
                                                        </dd>
                                                        <dt class="col-5">السجل التجاري</dt>
                                                        <dd class="col-7">
                                                            {{ $order->installment_commercial_registration_number ?: '-' }}
                                                        </dd>
                                                        <dt class="col-5">البطاقة الضريبية</dt>
                                                        <dd class="col-7">{{ $order->installment_tax_card_number ?: '-' }}
                                                        </dd>
                                                    @endif
                                                @endif
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header"><strong>بيانات السيارة</strong></div>
                                        <div class="card-body">
                                            <dl class="row mb-0">
                                                <dt class="col-5">السيارة</dt>
                                                <dd class="col-7">{{ $data['car_info'] ?: '-' }}</dd>
                                                <dt class="col-5">اللون الأول</dt>
                                                <dd class="col-7">{{ $data['color1'] }}</dd>
                                                <dt class="col-5">اللون الثاني</dt>
                                                <dd class="col-7">{{ $data['color2'] }}</dd>
                                                <dt class="col-5">السعر</dt>
                                                <dd class="col-7">{{ $data['price_fmt'] }}</dd>
                                                <dt class="col-5">المقدم (حجز)</dt>
                                                <dd class="col-7">{{ $data['reservation_fmt'] }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header"><strong>بيانات الدفع</strong></div>
                                        <div class="card-body">
                                            <dl class="row mb-0">
                                                <dt class="col-5">نظام الدفع</dt>
                                                <dd class="col-7">{{ $data['payment_label'] }}</dd>
                                                <dt class="col-5">نوع العميل</dt>
                                                <dd class="col-7">{{ $data['customer_type_label'] }}</dd>
                                                <dt class="col-5">البنك</dt>
                                                <dd class="col-7">{{ $data['bank_name'] }}</dd>
                                                <dt class="col-5">المدة</dt>
                                                <dd class="col-7">{{ $data['tenor'] }}</dd>
                                                <dt class="col-5">المقدم</dt>
                                                <dd class="col-7">{{ $data['down_payment_amount_fmt'] }}
                                                    ({{ $data['down_payment_percent_fmt'] }})</dd>
                                                <dt class="col-5">الفائدة</dt>
                                                <dd class="col-7">{{ $data['interest_rate_fmt'] }}</dd>
                                                <dt class="col-5">القسط الشهري</dt>
                                                <dd class="col-7">{{ $data['monthly_installment_fmt'] }}</dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            @if ($data['uploads']->isNotEmpty())
                                <div class="card mt-3">
                                    <div class="card-header"><strong>المرفقات</strong></div>
                                    <div class="card-body">
                                        <div class="row">
                                            @foreach ($data['uploads'] as $file)
                                                <div class="col-md-4 mb-3">
                                                    <div class="border rounded p-2 h-100 d-flex">
                                                        @if ($file['is_image'])
                                                            <img src="{{ $file['url'] }}" alt="{{ $file['label'] }}"
                                                                class="me-2"
                                                                style="width:64px;height:64px;object-fit:cover;border-radius:.25rem;">
                                                        @else
                                                            <div class="me-2 d-flex align-items-center justify-content-center"
                                                                style="width:64px;height:64px;background:#f1f3f5;border-radius:.25rem;">
                                                                <i class="icon-file-text"></i>
                                                            </div>
                                                        @endif
                                                        <div class="flex-grow-1">
                                                            <div class="fw-bold">{{ $file['label'] }}</div>
                                                            <div class="small text-muted">{{ $file['filename'] }}</div>
                                                            <div class="mt-1">
                                                                <a href="{{ $file['url'] }}"
                                                                    class="btn btn-sm btn-primary" target="_blank">فتح /
                                                                    تحميل</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="mt-3">
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">
                                    <i class="icon-arrow-left"></i> رجوع للقائمة
                                </a>
                            </div>

                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
