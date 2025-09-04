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
                            <div class="col-mb-12 p-0" style="margin: 15px;">
                                <div class="row d-flex justify-content-end p-0">
                                    <div class="col-md-2 d-flex justify-content-end p-0">
                                        <div class="col-md-6 d-flex justify-content-end p-0">
                                            <button type="button" class="btn btn-primary" style="margin-top: 20px;">
                                                <a href="{{ route('admin.know_more.create') }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i> جديد
                                                </a>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => 'سجل',
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الموديل</th>
                                            <th>العنوان</th>
                                            <th>فيديو رئيسي</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $knowMore)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $knowMore->carModel?->name }}</td>
                                                <td>{{ $knowMore->title }}</td>
                                                <td>{{ $knowMore->hero_section ? 'نعم' : 'لا' }}</td>
                                                <td>{{ $knowMore->created_at }}</td>
                                                <td>
                                                    <div class="td-actions">
                                                        <a href="{{ route('admin.know_more.edit', $knowMore->id) }}"
                                                            class="icon bg-info" data-toggle="tooltip" title="Edit">
                                                            <i class="icon-edit"></i>
                                                        </a>
                                                        <form method="POST" id="delete_form_{{ $knowMore->id }}"
                                                            class="d-inline delete_form"
                                                            action="{{ route('admin.know_more.destroy', $knowMore->id) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="icon red" data-toggle="tooltip"
                                                                title="Delete"
                                                                onclick="checker(event, {{ $knowMore->id }})">
                                                                <i class="icon-cancel"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">
                                                    <div class="alert alert-danger">
                                                        لا توجد سجلات.
                                                    </div>
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
