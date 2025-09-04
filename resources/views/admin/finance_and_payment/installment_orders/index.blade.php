@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <div class="row gutters">
                    <div class="col-12">
                        <div class="table-container">
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.insurance_orders.index'),
                                'resetUrl' => route('admin.insurance_orders.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'checkboxes' => ['today' => 'نتائج اليوم فقط'],
                            ])
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'طلبات التأمين',
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>المستخدم</th>
                                            <th>السيارة</th>
                                            <th>البرنامج</th>
                                            <th>السعر الإجمالي</th>
                                            <th>الحالة</th>
                                            <th>تاريخ الإضافة</th>
                                            <th>إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->user->full_name ?? '-' }}</td>
                                                <td>{{ $item->carTerm->term_name ?? '-' }}</td>
                                                <td>{{ $item->insurance->program_name ?? '-' }}</td>
                                                <td>{{ $item->car_price }}</td>
                                                <td>{{ \App\Enums\ServicesOrderStatusEnum::getDescription($item->status) }}</td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>
                                                    <div class="td-actions">
                                                        <a href="{{ route('admin.insurance_orders.show', $item->id) }}"
                                                            class="icon bg-info" data-toggle="tooltip" title="Show">
                                                            <i class="icon-eye"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">
                                                    <div class="alert alert-danger">لا توجد سجلات مطابقة للمعايير.</div>
                                                </td>
                                            </tr>
                                        @endforelse
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
