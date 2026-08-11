@extends('admin.layouts.master')

@section('title', __('admin.contact_submissions.title'))

@section('content')
    @php
        $heading = $pageName ?? __('admin.contact_submissions.title');
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $heading,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.contact_submissions')],
            ],
        ])

        @include('admin.partials.results-summary', [
            'data' => $data,
            'label' => \App\Support\Lang::t('admin.contact_submissions.results_label', 'submission(s)'),
        ])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.sidebar.all_submissions') }}</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.contact_submissions.table.id') }}</th>
                                <th scope="col">{{ __('admin.contact_submissions.table.type') }}</th>
                                <th scope="col">{{ __('admin.contact_submissions.table.name') }}</th>
                                <th scope="col">{{ __('admin.contact_submissions.table.phone') }}</th>
                                <th scope="col">{{ __('admin.contact_submissions.table.email') }}</th>
                                <th scope="col">{{ __('admin.contact_submissions.table.created_at') }}</th>
                                <th scope="col" class="text-right">{{ __('admin.contact_submissions.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <th scope="row">{{ $item->id }}</th>
                                    <td>
                                        <x-admin.badge variant="primary">{{ $item->type }}</x-admin.badge>
                                    </td>
                                    <td>{{ $item->name ?: '-' }}</td>
                                    <td>
                                        @if ($item->phone)
                                            <a href="tel:{{ $item->phone }}" dir="ltr">{{ $item->phone }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->email)
                                            <a href="mailto:{{ $item->email }}">{{ $item->email }}</a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>
                                        <x-admin.group-btn class="justify-content-end">
                                            <button type="button"
                                                    class="btn btn-outline-info btn-sm"
                                                    data-toggle="modal"
                                                    data-target="#submission_{{ $item->id }}"
                                                    aria-label="{{ __('admin.contact_submissions.view_details', ['id' => $item->id]) }}">
                                                <i class="fe fe-eye" aria-hidden="true"></i>
                                                <span>{{ __('admin.contact_submissions.actions.view') }}</span>
                                            </button>
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 7,
                                    'message' => __('admin.contact_submissions.messages.empty'),
                                    'icon' => 'inbox',
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

    {{--
        Payload detail is rendered server-side into a static Bootstrap 4 modal.
        No SweetAlert, no client-side JSON parsing, no HTML built from user input.
    --}}
    @foreach ($data as $item)
        @include('admin.contact_submissions.partials.detail-modal', ['submission' => $item])
    @endforeach
@endsection
