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
                                'action' => route('admin.insurances.index'),
                                'resetUrl' => route('admin.insurances.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'checkboxes' => ['today' => 'نتائج اليوم فقط'],
                            ])
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'برامج التأمين',
                            ])
                            <div class="mb-3 text-right">
                                <a href="{{ route('admin.insurances.create') }}" class="btn btn-primary">
                                    <i class="icon-plus-circle mr-1"></i> {{ __('admin.forms.create') }}
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>شركة التأمين</th>
                                            <th>اسم البرنامج</th>
                                            <th>نسبة التغطية</th>
                                            <th>السعر السنوي</th>
                                            <th>القسط الشهري</th>
                                            <th>مناسب لـ</th>
                                            <th>تاريخ الإضافة</th>
                                            <th>إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->insurance_company }}</td>
                                                <td>{{ $item->program_name }}</td>
                                                <td>{{ $item->coverage_rate }}</td>
                                                <td>{{ $item->annual_price }}</td>
                                                <td>{{ $item->monthly_payment }}</td>
                                                <td>{{ \App\Enums\ApplicableToEnum::getDescription($item->applicable_to) }}
                                                </td>
                                                <td>{{ $item->created_at }}</td>
                                                <td>
                                                    <div class="td-actions">
                                                        <a href="{{ route('admin.insurances.edit', $item->id) }}"
                                                            class="icon bg-info" data-toggle="tooltip" title="Edit">
                                                            <i class="icon-edit"></i>
                                                        </a>
                                                        <form method="POST"
                                                            action="{{ route('admin.insurances.destroy', $item->id) }}"
                                                            class="d-inline delete_form">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="icon red" data-toggle="tooltip"
                                                                title="Delete"
                                                                onclick="checker(event, {{ $item->id }})">
                                                                <i class="icon-cancel"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">
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
