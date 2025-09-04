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
                                $extraFilters = view('admin.finance_and_payment.installment_programs.partials.filters', [
                                    'filters' => $filters,
                                ])->render();
                            @endphp
                            @include('admin.partials.filter-form', [
                                'action' => route('admin.installment_programs.index'),
                                'resetUrl' => route('admin.installment_programs.index'),
                                'exportUrl' => '',
                                'filters' => $filters,
                                'extraFilters' => $extraFilters,
                            ])
                            <div class="col-mb-12 p-0" style="margin: 15px;">
                                <div class="row d-flex justify-content-end p-0">
                                    <div class="col-md-2 d-flex justify-content-end p-0">
                                        <div class="col-md-6 d-flex justify-content-end  p-0">
                                            <button type="button" class="btn btn-primary" style="margin-top: 20px;">
                                                <a href="{{ route('admin.installment_programs.create') }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i>{{ __('admin.table.new') }}</a></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => __('admin.sidebar.installment_programs'),
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الاسم</th>
                                            <th>تاريخ الإنشاء</th>
                                            <th>تاريخ التحديث</th>
                                            <th>الخيارات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        {{ $item->name ?? '-' }}
                                                    </td>
                                                    <td>{{ $item->created_at ?? '-' }}</td>
                                                    <td>{{ $item->updated_at ?? '-' }}</td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="{{ route('admin.installment_programs.edit', $item->id) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="Edit Row">
                                                                <i class="icon-edit"></i>
                                                            </a>
                                                            <form method="POST" id="delete_form_{{ $item->id }}" class="d-inline delete_form" action="{{ route('admin.installment_programs.destroy', ['installment_program' => $item->id]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="icon red" data-toggle="tooltip" data-placement="top" title="Delete Row" onclick="checker(event, {{ $item->id }})">
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
