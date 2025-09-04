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
                            @php
                                $extraFilters = view('admin.finance_and_payment.booking-car-orders.partials.filters', [
                                    'filters' => $filters,
                                ])->render();
                            @endphp
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.orders.index'),
                                'resetUrl' => route('admin.orders.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'extraFilters' => $extraFilters,
                            ])
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => __('admin.sidebar.orders'),
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>رقم المرجع</th>
                                            <th>العميل</th>
                                            <th>السيارة</th>
                                            <th>الألوان</th>
                                            <th>الدفع / النوع</th>
                                            <th>البنك / المدة</th>
                                            <th>السعر / المقدم</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}
                                                    </td>
                                                    <td><span class="badge badge-light">{{ $item->ref }}</span></td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <strong>{{ $item->customer_name }}</strong>
                                                            <small class="text-muted">{{ $item->customer_phone }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span>{{ $item->car_info ?: '-' }}</span>
                                                            @if ($item->branch_name)
                                                                <small class="text-muted">الفرع:
                                                                    {{ $item->branch_name }}</small>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span>الأول: {{ $item->color1_name }}</span>
                                                            <span>الثاني: {{ $item->color2_name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span
                                                                class="badge {{ $item->payment_label === 'نقدي' ? 'badge-primary' : 'badge-info' }}">
                                                                {{ $item->payment_label }}
                                                            </span>
                                                            <small
                                                                class="text-muted">{{ $item->customer_type_label }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span>{{ $item->bank_name }}</span>
                                                            <small class="text-muted">{{ $item->tenor_label }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span>السعر: {{ $item->price_fmt }}</span>
                                                            <small class="text-muted">المقدم:
                                                                {{ $item->reservation_fmt }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge {{ $item->status_class }}">{{ $item->status_label }}</span>
                                                    </td>
                                                    <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="{{ route('admin.orders.show', ['order' => $item->id]) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="عرض الطلب">
                                                                <i class="icon-eye"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-danger">
                                                        {{ __('admin.table.no_entries') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
