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
                            <div class="mb-3 text-right">
                                <a href="{{ route('admin.writers.create') }}" class="btn btn-primary">
                                    <i class="icon-plus-circle mr-1"></i> {{ __('admin.forms.create') ?? 'إضافة' }}
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>تاريخ التحديث</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($data as $item)
                                            <tr>
                                                <td>{{ $item->id }}</td>
                                                <td>{{ $item->name }}</td>
                                                <td>{{ $item->updated_at }}</td>
                                                <td>
                                                    <div class="td-actions">
                                                        <a href="{{ route('admin.writers.edit', $item->id) }}"
                                                            class="icon bg-info" data-toggle="tooltip" title="Edit">
                                                            <i class="icon-edit"></i>
                                                        </a>
                                                        {{-- <form method="POST"
                                                            action="{{ route('admin.writers.destroy', $item->id) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="icon red" data-toggle="tooltip"
                                                                title="Delete" onclick="return confirm('Are you sure?')">
                                                                <i class="icon-cancel"></i>
                                                            </button>
                                                        </form> --}}
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">
                                                    <div class="alert alert-danger">
                                                        لا توجد سجلات مطابقة للمعايير.
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
