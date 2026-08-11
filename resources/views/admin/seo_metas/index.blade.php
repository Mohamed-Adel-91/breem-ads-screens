@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.website_cms')],
                ['label' => __('admin.sidebar.seo_metas')],
            ],
            'primaryAction' => [
                'href' => route('admin.seo_metas.create', ['lang' => app()->getLocale()]),
                'label' => __('admin.table.new'),
                'icon' => 'plus-circle',
            ],
        ])

        @include('admin.partials.results-summary', [
            'data' => $data,
            'label' => \App\Support\Lang::t('admin.seo_metas.results_label', 'record(s)'),
        ])

        <div class="card">
            <div class="card-header">
                <h2 class="card-title mb-0">{{ __('admin.sidebar.seo_metas') }}</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.seo_metas.table.page') }}</th>
                                <th scope="col">{{ __('admin.seo_metas.table.title_en') }}</th>
                                <th scope="col">{{ __('admin.seo_metas.table.title_ar') }}</th>
                                <th scope="col">{{ __('admin.table.created_at') }}</th>
                                <th scope="col">{{ __('admin.table.updated_at') }}</th>
                                <th scope="col" class="text-right">{{ __('admin.table.options') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $seoMeta)
                                <tr>
                                    <th scope="row">{{ ($data->firstItem() ?? 1) + $loop->index }}</th>
                                    <td><code>{{ $seoMeta->page }}</code></td>
                                    <td>{{ $seoMeta->getTranslation('title', 'en', false) ?: '-' }}</td>
                                    <td>{{ $seoMeta->getTranslation('title', 'ar', false) ?: '-' }}</td>
                                    <td>{{ optional($seoMeta->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>{{ optional($seoMeta->updated_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                    <td>
                                        <x-admin.group-btn class="justify-content-end">
                                            <x-admin.btn
                                                :href="route('admin.seo_metas.edit', [
                                                    'lang' => app()->getLocale(),
                                                    'seo_meta' => $seoMeta->id,
                                                ])"
                                                variant="outline-info"
                                                size="sm"
                                                icon="edit-2">
                                                {{ __('admin.seo_metas.actions.edit') }}
                                            </x-admin.btn>

                                            <x-admin.btn
                                                :href="route('admin.seo_metas.destroy', [
                                                    'lang' => app()->getLocale(),
                                                    'seo_meta' => $seoMeta->id,
                                                ])"
                                                method="DELETE"
                                                variant="outline-danger"
                                                size="sm"
                                                icon="trash-2"
                                                :confirm="__('admin.sweet_alert.delete_text')">
                                                {{ __('admin.seo_metas.actions.delete') }}
                                            </x-admin.btn>
                                        </x-admin.group-btn>
                                    </td>
                                </tr>
                            @empty
                                @include('admin.partials.empty-state', [
                                    'colspan' => 7,
                                    'message' => __('admin.seo_metas.messages.empty'),
                                    'icon' => 'search',
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
