@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.admins_management')],
                ['label' => __('admin.sidebar.roles')],
            ],
            'primaryAction' => [
                'href' => route('admin.roles.create', ['lang' => app()->getLocale()]),
                'label' => __('admin.table.new'),
                'icon' => 'plus-circle',
            ],
        ])

        @include('admin.partials.results-summary', [
            'data' => $data,
            'label' => __('admin.sidebar.roles'),
        ])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.sidebar.roles') }}</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.forms.name') }}</th>
                                <th scope="col">{{ __('admin.forms.guard') }}</th>
                                <th scope="col" class="text-right">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $role)
                                <tr>
                                    <th scope="row">{{ ($data->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        <x-admin.badge variant="primary">
                                            {{ $role->guard_name }}
                                        </x-admin.badge>
                                    </td>
                                    <td>
                                        <x-admin.group-btn class="justify-content-end">
                                            <x-admin.btn
                                                :href="route('admin.roles.edit', [
                                                    'lang' => app()->getLocale(),
                                                    'role' => $role->id,
                                                ])"
                                                variant="outline-info"
                                                size="sm"
                                                icon="edit-2">
                                                {{ __('admin.tooltips.edit_row') }}
                                            </x-admin.btn>

                                            <x-admin.btn
                                                :href="route('admin.roles.destroy', [
                                                    'lang' => app()->getLocale(),
                                                    'role' => $role->id,
                                                ])"
                                                method="DELETE"
                                                variant="outline-danger"
                                                size="sm"
                                                icon="trash-2"
                                                :confirm="__('admin.sweet_alert.delete_text')">
                                                {{ __('admin.tooltips.delete_row') }}
                                            </x-admin.btn>
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 4,
                                    'message' => __('admin.table.no_records'),
                                    'icon' => 'shield',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white">
                @include('admin.partials.pagination', ['data' => $data, 'variant' => 'static'])
            </div>
        </div>
    </div>
@endsection
