@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        // Route targets preserved exactly as the legacy view used them: this module has no
        // dedicated route of its own, so the filter/export endpoints stay untouched.
        $filterAction = route('admin.logs.index', ['lang' => $lang]);
        $resetUrl = route('admin.logs.index', ['lang' => $lang]);
        $exportUrl = route('admin.logs.download', array_merge(['lang' => $lang], request()->query()));

        // Semantic badge derived from the description already stored on the activity row.
        $eventVariant = function (?string $description): string {
            $verb = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::before((string) $description, ' '));

            return match ($verb) {
                'created' => 'success',
                'updated' => 'info',
                'deleted' => 'danger',
                'downloaded', 'exported' => 'secondary',
                default => 'light',
            };
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.header.activity_logs')],
            ],
        ])

        @include('admin.partials.filter-form', [
            'variant' => 'static',
            'action' => $filterAction,
            'resetUrl' => $resetUrl,
            'exportUrl' => $exportUrl,
            'checkboxes' => ['today' => __('admin.activity_logs.filters.today_only')],
            'filters' => $filters,
        ])

        @include('admin.partials.results-summary', [
            'data' => $data,
            'label' => \App\Support\Lang::t('admin.activity_logs.results_label', 'log(s)'),
        ])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.header.activity_logs') }}</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.activity_logs.table.description') }}</th>
                                <th scope="col">{{ __('admin.activity_logs.table.causer') }}</th>
                                <th scope="col" class="w-50">{{ __('admin.activity_logs.table.properties') }}</th>
                                <th scope="col">{{ __('admin.table.created_at') }}</th>
                                <th scope="col">{{ __('admin.table.updated_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $item)
                                <tr>
                                    <th scope="row">{{ ($data->firstItem() ?? 1) + $loop->index }}</th>
                                    <td>
                                        @if ($item->description)
                                            <x-admin.badge :variant="$eventVariant($item->description)">
                                                {{ $item->description }}
                                            </x-admin.badge>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ __('admin.activity_logs.labels.user') }}</strong>
                                            {{ trim(
                                                (optional($item->causer)->first_name ?? '') . ' ' .
                                                (optional($item->causer)->last_name ?? '')
                                            ) ?: '-' }}
                                        </div>
                                        <div>
                                            <strong>{{ __('admin.activity_logs.labels.email') }}</strong>
                                            {{ optional($item->causer)->email ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($item->properties && $item->properties->count())
                                            <ul class="list-unstyled mb-0">
                                                @foreach ($item->properties as $key => $value)
                                                    <li>
                                                        <strong>{{ \Illuminate\Support\Str::headline($key) }}:</strong>
                                                        @if (is_array($value) || $value instanceof \Illuminate\Support\Collection)
                                                            {{ implode(', ', array_map(
                                                                fn ($entry) => is_scalar($entry) || is_null($entry)
                                                                    ? (string) $entry
                                                                    : json_encode($entry, JSON_UNESCAPED_UNICODE),
                                                                (array) $value,
                                                            )) }}
                                                        @else
                                                            {{ $value }}
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">
                                                {{ __('admin.activity_logs.labels_extra.no_properties') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>{{ optional($item->updated_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 6,
                                    'message' => __('admin.activity_logs.messages.empty'),
                                    'icon' => 'activity',
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
