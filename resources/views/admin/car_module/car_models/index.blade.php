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
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.car_models.index'),
                                'resetUrl' => route('admin.car_models.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'extraFilters' => '',
                            ])
                            <div class="col-mb-12 p-0" style="margin: 15px;">
                                <div class="row d-flex justify-content-end p-0">
                                    <div class="col-md-2 d-flex justify-content-end p-0">
                                        <div class="col-md-6 d-flex justify-content-end  p-0">
                                            <button type="text" class="btn btn-primary" style="margin-top: 20px;">
                                                <a href="{{ route('admin.car_models.create') }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i>جديد</a></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'موديلات السيارات',
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الشكل</th>
                                            <th>الماركة - الاسم</th>
                                            <th>الصورة</th>
                                            <th>سعر البداية</th>
                                            <th>الحالة</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->shape->name ?? '-' }}</td>
                                                    <td>{{ $item->brand->name . ' - ' . $item->name ?? '-' }}</td>
                                                    <td>
                                                        @if ($item->image_path ?? false)
                                                            @include(
                                                                'admin.layouts.components.image-cell',
                                                                [
                                                                    'src' => asset($item->image_path),
                                                                    'dataSrc' => asset($item->image_path),
                                                                ]
                                                            )
                                                        @endif
                                                    </td>
                                                    <td>{{ $item->start_price ?? '-' }}</td>
                                                    <td>
                                                        <div class="form-check form-switch">
                                                            <span class="badge {{ $item->show_status ? 'badge-success' : 'badge-danger' }}">
                                                                {{ $item->show_status ? 'معروض علي الموقع' : 'غير معروض' }}
                                                            </span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="{{ route('admin.car_models.edit', $item->id) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="Edit Row">
                                                                <i class="icon-edit"></i>
                                                            </a>
                                                            <form method="POST" id="delete_form_{{ $item->id }}"
                                                                class="d-inline delete_form"
                                                                action="{{ route('admin.car_models.destroy', ['car_model' => $item->id]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="icon red"
                                                                    data-toggle="tooltip" data-placement="top"
                                                                    title="Delete Row"
                                                                    onclick="checker(event, {{ $item->id }})">
                                                                    <i class="icon-cancel"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-danger">
                                                        لا توجد سجلات للموديلات مطابقة للمعايير.
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
