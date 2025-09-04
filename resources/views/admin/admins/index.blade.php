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
                                                <a href="{{ route('admin.admins.create') }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i>{{ __('admin.table.new') }}</a></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => __('admin.sidebar.users_management'),
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('admin.table.first_name') }}</th>
                                            <th>{{ __('admin.table.last_name') }}</th>
                                            <th>{{ __('admin.table.mobile') }}</th>
                                            <th>{{ __('admin.table.email') }}</th>
                                            <th>{{ __('admin.table.role') }}</th>
                                            <th>{{ __('admin.table.created_at') }}</th>
                                            <th>{{ __('admin.table.updated_at') }}</th>
                                            <th>{{ __('admin.table.options') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && count($data) > 0)
                                            @foreach ($data as $item)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $item->first_name }}</td>
                                                    <td>{{ $item->last_name }}</td>
                                                    <td>{{ $item->mobile }}</td>
                                                    <td>{{ $item->email }}</td>
                                                    <td>{{ \App\Enums\RolesEnum::getDescription($item->role) }}</td>
                                                    <td>{{ $item->created_at->format('Y-m-d') }}</td>
                                                    <td>{{ $item->updated_at->format('Y-m-d') }}</td>
                                                    <td>
                                                        <div class="td-actions">
                                                            {{-- <a href="{{ route('admin.admins.edit', $item->id) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="Edit Row">
                                                                <i class="icon-edit"></i>
                                                            </a> --}}
                                                            @if (Auth::guard('admin')->user()->id != $item->id)
                                                                @if ($item->role != 1)
                                                                    <form method="POST"
                                                                        id="delete_form_{{ $item->id }}"
                                                                        class="d-inline delete_form"
                                                                        action="{{ route('admin.admins.destroy', ['admin' => $item->id]) }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="icon red"
                                                                            data-toggle="tooltip" data-placement="top"
                                                                            title="Delete Row"
                                                                            onclick="checker(event, {{ $item->id }})">
                                                                            <i class="icon-cancel"></i>
                                                                        </button>
                                                                    </form>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-warning">
                                                        {{ __('admin.table.no_entries') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
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
