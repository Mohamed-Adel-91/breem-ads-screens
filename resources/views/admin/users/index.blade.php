@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.users_management')],
            ],
            'primaryAction' => [
                'href' => route('admin.users.create', ['lang' => $lang]),
                'label' => __('admin.table.new'),
                'icon' => 'user-plus',
            ],
        ])

        @include('admin.partials.filter-form', [
            'variant' => 'static',
            'action' => route('admin.users.index', ['lang' => $lang]),
            'resetUrl' => route('admin.users.index', ['lang' => $lang]),
            'exportUrl' => null,
            'filters' => $filters,
            'checkboxes' => ['today' => __('users.today_results_only')],
        ])

        @include('admin.partials.results-summary', [
            'data' => $data,
            'label' => __('users.label'),
        ])

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('users.full_name') }}</th>
                                <th scope="col">{{ __('users.nickname') }}</th>
                                <th scope="col">{{ __('users.email') }}</th>
                                <th scope="col">{{ __('users.mobile') }}</th>
                                <th scope="col">{{ __('users.created_at') }}</th>
                                <th scope="col">{{ __('users.updated_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <th scope="row">{{ ($data->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>{{ $item->full_name ?? '-' }}</td>
                                    <td>{{ $item->nickname ?? '-' }}</td>
                                    <td>
                                        <a href="mailto:{{ $item->email }}">{{ $item->email ?? '-' }}</a>
                                    </td>
                                    <td>{{ $item->mobile ?? '-' }}</td>
                                    <td>{{ $item->created_at ?? '-' }}</td>
                                    <td>{{ $item->updated_at ?? '-' }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 7,
                                    'message' => __('users.no_records'),
                                    'icon' => 'users',
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
