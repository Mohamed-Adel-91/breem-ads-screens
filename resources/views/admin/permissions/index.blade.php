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
                            @include('admin.partials.results-summary', ['data' => $data, 'label' => __('admin.sidebar.permissions')])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('admin.forms.name') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($data as $permission)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $permission->name }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">
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
