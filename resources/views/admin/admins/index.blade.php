@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $currentAdmin = Auth::guard('admin')->user();
        $canEdit = $currentAdmin->hasRole('super-admin') || $currentAdmin->can('admins.edit');
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.admins_management')],
                ['label' => __('admin.sidebar.admins')],
            ],
            'primaryAction' => [
                'href' => route('admin.admins.create', ['lang' => app()->getLocale()]),
                'label' => __('admin.table.new'),
                'icon' => 'user-plus',
            ],
        ])

        @include('admin.partials.results-summary', [
            'data' => $data,
            'label' => __('admin.sidebar.admins'),
        ])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.sidebar.admins') }}</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.table.image') }}</th>
                                <th scope="col">{{ __('admin.table.first_name') }}</th>
                                <th scope="col">{{ __('admin.table.last_name') }}</th>
                                <th scope="col">{{ __('admin.table.mobile') }}</th>
                                <th scope="col">{{ __('admin.table.email') }}</th>
                                <th scope="col">{{ __('admin.table.role') }}</th>
                                <th scope="col">{{ __('admin.table.created_at') }}</th>
                                <th scope="col">{{ __('admin.table.updated_at') }}</th>
                                <th scope="col" class="text-right">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <th scope="row">{{ ($data->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>
                                        <x-admin.media-preview
                                            :url="$item->image_path"
                                            :alt="trim($item->first_name . ' ' . $item->last_name)"
                                            :fallback="asset('admin-assets/assets/images/default-avatar.jpg')"
                                            :linkable="false"
                                            max-height="40px" />
                                    </td>
                                    <td>{{ $item->first_name }}</td>
                                    <td>{{ $item->last_name }}</td>
                                    <td>{{ $item->mobile ?: '-' }}</td>
                                    <td>
                                        <a href="mailto:{{ $item->email }}">{{ $item->email }}</a>
                                    </td>
                                    <td>
                                        @forelse ($item->getRoleNames() as $roleName)
                                            <x-admin.badge variant="primary" class="mr-1">
                                                {{ $roleName }}
                                            </x-admin.badge>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>
                                    <td>{{ optional($item->created_at)->format('Y-m-d') ?? '-' }}</td>
                                    <td>{{ optional($item->updated_at)->format('Y-m-d') ?? '-' }}</td>
                                    <td>
                                        <x-admin.group-btn class="justify-content-end">
                                            @if ($canEdit)
                                                <x-admin.btn
                                                    :href="route('admin.admins.edit', [
                                                        'lang' => app()->getLocale(),
                                                        'admin' => $item->id,
                                                    ])"
                                                    variant="outline-info"
                                                    size="sm"
                                                    icon="edit-2">
                                                    {{ __('admin.tooltips.edit_row') }}
                                                </x-admin.btn>
                                            @endif

                                            @if ($currentAdmin->id != $item->id && !$item->hasRole('super-admin'))
                                                <x-admin.btn
                                                    :href="route('admin.admins.destroy', [
                                                        'lang' => app()->getLocale(),
                                                        'admin' => $item->id,
                                                    ])"
                                                    method="DELETE"
                                                    variant="outline-danger"
                                                    size="sm"
                                                    icon="trash-2"
                                                    :confirm="__('admin.sweet_alert.delete_text')">
                                                    {{ __('admin.tooltips.delete_row') }}
                                                </x-admin.btn>
                                            @endif
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 10,
                                    'message' => __('admin.table.no_entries'),
                                    'icon' => 'user-x',
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
