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
                                        <div class="col-md-6 d-flex justify-content-end  p-0">
                                            <button type="text" class="btn btn-primary" style="margin-top: 20px;">
                                                <a href="{{ route('admin.roles.create', ['lang' => app()->getLocale()]) }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i>{{ __('admin.table.new') }}</a>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', ['data' => $data, 'label' => __('admin.sidebar.roles')])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('admin.forms.name') }}</th>
                                            <th>{{ __('admin.table.options') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($data as $role)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $role->name }}</td>
                                                <td>
                                                    <div class="td-actions">
                                                        <a href="{{ route('admin.roles.edit', ['lang' => app()->getLocale(), 'role' => $role->id]) }}" class="icon bg-info" data-toggle="tooltip" data-placement="top" title="Edit Row">
                                                            <i class="icon-edit"></i>
                                                        </a>
                                                        <form method="POST" id="delete_form_{{ $role->id }}" class="d-inline delete_form" action="{{ route('admin.roles.destroy', ['lang' => app()->getLocale(), 'role' => $role->id]) }}">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="icon red" data-toggle="tooltip" data-placement="top" title="Delete Row" onclick="checker(event, {{ $role->id }})">
                                                                <i class="icon-cancel"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center">
                                                    <div class="alert alert-warning">
                                                        {{ __('admin.table.no_records') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination', ['data' => $data])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
