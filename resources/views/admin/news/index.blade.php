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
                                'action' => route('admin.news.index'),
                                'resetUrl' => route('admin.news.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'checkboxes' => ['today' => 'نتائج اليوم فقط'],
                            ])
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'الأخبار',
                            ])
                            <div class="mb-3 text-right">
                                <a href="{{ route('admin.news.create') }}" class="btn btn-primary">
                                    <i class="icon-plus-circle mr-1"></i> {{ __('admin.forms.create') }}
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>العنوان</th>
                                            <th>الكاتب</th>
                                            <th>تاريخ الإضافة</th>
                                            <th>إجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->title }}</td>
                                                <td>{{ $item->writer->name ?? '-' }}</td>
                                                <td>{{ $item->added_date }}</td>
                                                <td>
                                                    <div class="td-actions">
                                                        <a href="{{ route('admin.news.edit', $item->id) }}"
                                                            class="icon bg-info" data-toggle="tooltip" title="Edit">
                                                            <i class="icon-edit"></i>
                                                        </a>
                                                        <form method="POST"
                                                            action="{{ route('admin.news.destroy', $item->id) }}"
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
                                                <td colspan="5" class="text-center">
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
