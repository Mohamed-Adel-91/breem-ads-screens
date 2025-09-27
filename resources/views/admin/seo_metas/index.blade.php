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
                                        <div class="col-md-6 d-flex justify-content-end p-0">
                                            <button type="button" class="btn btn-primary" style="margin-top: 20px;">
                                                <a href="{{ route('admin.seo_metas.create', ['lang' => app()->getLocale()]) }}" style="color: #fff;">
                                                    <i class="icon-plus-circle mr-1"></i> @t('admin.table.new')
                                                </a>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @include('admin.partials.results-summary', [
                                'data' => $data,
                                'label' => \App\Support\Lang::t('admin.seo_metas.results_label', 'record(s)'),
                            ])
                            <div class="table-responsive">
                                <table class="table custom-table m-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>@t('admin.seo_metas.table.title_en')</th>
                                            <th>@t('admin.seo_metas.table.title_ar')</th>
                                            <th>@t('admin.table.created_at')</th>
                                            <th>@t('admin.table.updated_at')</th>
                                            <th>@t('admin.table.options')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($data) && $data->count() > 0)
                                            @foreach ($data as $seoMeta)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $seoMeta->getTranslation('title', 'en') }}</td>
                                                    <td>{{ $seoMeta->getTranslation('title', 'ar') }}</td>
                                                    <td>{{ $seoMeta->created_at }}</td>
                                                    <td>{{ $seoMeta->updated_at }}</td>
                                                    <td>
                                                        <div class="td-actions">
                                                            <a href="{{ route('admin.seo_metas.edit', ['lang' => app()->getLocale(), 'seo_meta' => $seoMeta->id]) }}"
                                                                class="icon bg-info" data-toggle="tooltip"
                                                                data-placement="top" title="@t('admin.seo_metas.actions.edit')">
                                                                <i class="icon-edit"></i>
                                                            </a>
                                                            <form method="POST" id="delete_form_{{ $seoMeta->id }}"
                                                                class="d-inline delete_form"
                                                                action="{{ route('admin.seo_metas.destroy', ['lang' => app()->getLocale(), 'seo_meta' => $seoMeta->id]) }}">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="icon red"
                                                                    data-toggle="tooltip" data-placement="top"
                                                                    title="@t('admin.seo_metas.actions.delete')"
                                                                    onclick="checker(event, {{ $seoMeta->id }})">
                                                                    <i class="icon-cancel"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="11" class="text-center">
                                                    <div class="alert alert-danger">
                                                        @t('admin.seo_metas.messages.empty')
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                            @include('admin.partials.pagination')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
