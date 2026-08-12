@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $indexUrl = route('admin.places.index', ['lang' => $lang]);

        $placeName = data_get($place->getTranslations('name'), app()->getLocale())
            ?: __('admin.places.unnamed', ['id' => $place->id]);
        $placeAddress = data_get($place->getTranslations('address'), app()->getLocale()) ?: null;
        $placeType = $place->type
            ? \App\Support\Lang::t('admin.places.types.' . $place->type->value, ucfirst($place->type->value))
            : null;

        // `screens` is already eager-loaded (with schedules_count / ads_count) by
        // PlaceController::show — nothing extra is queried here.
        $screens = $place->screens;
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $placeName,
            'subtitle' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.ads_system')],
                ['label' => __('admin.sidebar.places'), 'url' => $indexUrl],
                ['label' => $placeName],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.places.actions.back_to_list'),
                'icon' => 'arrow-left',
            ],
            'primaryAction' => auth('admin')->user()?->can('places.edit')
                ? [
                    'href' => route('admin.places.edit', ['lang' => $lang, 'place' => $place->id]),
                    'label' => __('admin.places.actions.edit'),
                    'icon' => 'edit-2',
                ]
                : null,
        ])

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.places.show.information') }}</h2>
                @can('places.delete')
                    <x-admin.group-btn>
                        <x-admin.btn
                            :href="route('admin.places.destroy', ['lang' => $lang, 'place' => $place->id])"
                            method="DELETE"
                            variant="outline-danger"
                            size="sm"
                            icon="trash-2"
                            :confirm="__('admin.places.actions.delete_confirm')">
                            {{ __('admin.places.actions.delete') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                @endcan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 admin-detail-table">
                        <tbody>
                            <tr>
                                <th scope="row">{{ __('admin.places.table.name') }}</th>
                                <td>{{ $placeName }}</td>
                            </tr>
                            <tr>
                                <th scope="row">{{ __('admin.places.table.type') }}</th>
                                <td>
                                    @if ($placeType)
                                        <x-admin.badge variant="light">{{ $placeType }}</x-admin.badge>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">{{ __('admin.places.table.address') }}</th>
                                <td>{{ $placeAddress ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">{{ __('admin.places.table.screens_count') }}</th>
                                <td>
                                    <x-admin.badge :variant="$screens->count() > 0 ? 'primary' : 'secondary'" pill>
                                        {{ $screens->count() }}
                                    </x-admin.badge>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">{{ __('admin.places.show.created_at') }}</th>
                                <td>{{ optional($place->created_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th scope="row">{{ __('admin.places.show.updated_at') }}</th>
                                <td>{{ optional($place->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @can('places.delete')
                    <p class="text-muted small mb-0 mt-3">{{ __('admin.places.actions.delete_hint') }}</p>
                @endcan
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h2 class="card-title mb-0">{{ __('admin.places.show.screens_heading') }}</h2>
                @can('screens.create')
                    <x-admin.btn
                        :href="route('admin.screens.create', ['lang' => $lang])"
                        variant="outline-primary"
                        size="sm"
                        icon="plus">
                        {{ __('admin.places.show.add_screen') }}
                    </x-admin.btn>
                @endcan
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.screens.table.code') }}</th>
                                <th scope="col">{{ __('admin.screens.table.status') }}</th>
                                <th scope="col">{{ __('admin.screens.table.device_uid') }}</th>
                                <th scope="col">{{ __('admin.screens.table.last_heartbeat') }}</th>
                                <th scope="col">{{ __('admin.screens.table.schedules') }}</th>
                                <th scope="col">{{ __('admin.screens.table.ads_attached') }}</th>
                                <th scope="col">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($screens as $screen)
                                <tr>
                                    <td><code>{{ $screen->code }}</code></td>
                                    <td>
                                        @include('admin.screens.partials.status-badge', ['status' => $screen->status])
                                    </td>
                                    <td>
                                        @if ($screen->device_uid)
                                            <code>{{ $screen->device_uid }}</code>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @include('admin.screens.partials.heartbeat', ['heartbeat' => $screen->last_heartbeat])
                                    </td>
                                    <td>{{ $screen->schedules_count }}</td>
                                    <td>{{ $screen->ads_count }}</td>
                                    <td>
                                        <x-admin.group-btn>
                                            @can('screens.view')
                                                <x-admin.btn
                                                    :href="route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id])"
                                                    variant="outline-secondary"
                                                    size="sm"
                                                    icon="eye">
                                                    {{ __('admin.screens.actions.view') }}
                                                </x-admin.btn>
                                            @endcan
                                            @can('screens.edit')
                                                <x-admin.btn
                                                    :href="route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id])"
                                                    variant="outline-primary"
                                                    size="sm"
                                                    icon="edit-2">
                                                    {{ __('admin.screens.actions.edit') }}
                                                </x-admin.btn>
                                            @endcan
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 7,
                                    'message' => __('admin.places.show.screens_empty'),
                                    'icon' => 'monitor',
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
