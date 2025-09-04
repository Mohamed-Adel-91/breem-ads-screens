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
                                $extraFilters = view('admin.car_module.service_centers.partials.filters', [
                                    'filters' => $filters,
                                ])->render();
                            @endphp
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.service_centers.index'),
                                'resetUrl' => route('admin.service_centers.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'extraFilters' => $extraFilters,
                            ])
                            <div class="col-mb-12 p-0" style="margin: 15px;">
                                <div class="row d-flex justify-content-end p-0">
                                    <div class="col-md-2 d-flex justify-content-end p-0">
                                        <div class="col-md-6 d-flex justify-content-end  p-0">
                                            <button type="text" class="btn btn-primary" style="margin-top: 20px;">
                                                <a href="{{ route('admin.service_centers.create') }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i>New</a></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'مركز خدمة',
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>الماركة</th>
                                            <th>الموقع</th>
                                            <th>العنوان</th>
                                            <th>الهاتف</th>
                                            <th>المدينة</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->name ?? '-' }}</td>
                                                    <td>{{ $item->brand->name ?? '-' }}</td>
                                                    <td>
                                                        @if (!empty($item->location))
                                                            <iframe src="{{ $item->location }}" width="200"
                                                                height="120" style="border:0;" allowfullscreen
                                                                loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                                                            </iframe>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td>{{ $item->address ?? '-' }}</td>
                                                    <td>{{ $item->phone ?? '-' }}</td>
                                                    <td>{{ \App\Enums\CityEnum::getDescription($item->city) }}</td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="{{ route('admin.service_centers.edit', $item->id) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="Edit Row">
                                                                <i class="icon-edit"></i>
                                                            </a>
                                                            <form method="POST" id="delete_form_{{ $item->id }}"
                                                                class="d-inline delete_form"
                                                                action="{{ route('admin.service_centers.destroy', ['service_center' => $item->id]) }}">
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
                                                        لا توجد سجلات لمراكز الخدمة مطابقة للمعايير.
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
