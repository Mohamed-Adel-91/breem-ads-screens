@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <div class="table-container">
                    @include('admin.partials.filter-form', [
                        'action' => route('admin.videos.index'),
                        'resetUrl' => route('admin.videos.index'),
                        'exportUrl' => '',
                        'filters' => $filters,
                        'checkboxes' => ['today' => 'نتائج اليوم فقط'],
                    ])
                    @include('admin.partials.results-summary', [
                        'data' => $data,
                        'label' => 'فيديو',
                    ])
                    <div class="col-mb-12 p-0" style="margin: 15px;">
                        <div class="row d-flex justify-content-end p-0">
                            <div class="col-md-2 d-flex justify-content-end p-0">
                                <div class="col-md-6 d-flex justify-content-end  p-0">
                                    <button type="text" class="btn btn-primary" style="margin-top: 20px;">
                                        <a href="{{ route('admin.videos.create') }}" style="color: #fff;">
                                            <i class="icon-plus-circle mr-1"></i>
                                            {{ __('admin.buttons.new') }}
                                        </a>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table custom-table m-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>العنوان</th>
                                    <th>تاريخ الاضافة</th>
                                    <th>رئيسية</th>
                                    <th>حالة الظهور</th>
                                    <th>الترتيب</th>
                                    <th>الخيارات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->title }}</td>
                                        <td>{{ $item->added_date }}</td>
                                        <td>{{ $item->home ? __('admin.forms.yes') : __('admin.forms.no') }}</td>
                                        <td>{{ $item->hidden ? __('admin.forms.yes') : __('admin.forms.no') }}</td>
                                        <td>{{ $item->ord }}</td>
                                        <td>
                                            <div class="td-actions">
                                                <a href="{{ route('admin.videos.edit', $item->id) }}" class="icon bg-info"
                                                    data-toggle="tooltip" data-placement="top" title="Edit Row">
                                                    <i class="icon-edit"></i>
                                                </a>
                                                <form method="POST" class="d-inline delete_form"
                                                    action="{{ route('admin.videos.destroy', $item->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="icon red" data-toggle="tooltip"
                                                        data-placement="top" title="Delete Row"
                                                        onclick="checker(event, {{ $item->id }})">
                                                        <i class="icon-cancel"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
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
@endsection
