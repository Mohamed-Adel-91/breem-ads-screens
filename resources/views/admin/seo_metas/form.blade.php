@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                @php
                    $languages = ['en', 'ar'];
                @endphp
                <form method="POST"
                    action="{{ isset($seoMeta) ? route('admin.seo_metas.update', $seoMeta->id) : route('admin.seo_metas.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($seoMeta))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }} {{ __('admin.forms.seo_meta') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <!-- Page Identifier Dropdown -->
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="page">{{ __('admin.forms.page_identifier') }}</label>
                                                <select class="form-control" id="page" name="page" required>
                                                    <option value="">{{ __('admin.forms.choose_page') }}</option>
                                                    @foreach ($pagesRoutes as $routeName => $routeUrl)
                                                        <option value="{{ $routeName }}"
                                                            @if ((isset($seoMeta) && $seoMeta->page == $routeName) || old('page') == $routeName) selected @endif>
                                                            {{ $routeUrl }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @foreach ($languages as $lang)
                                            <div class="col-md-6">
                                                <h4 class="mt-4">Content for {{ strtoupper($lang) }}</h4>
                                                <!-- Title -->
                                                <div class="form-group">
                                                    <label for="title_{{ $lang }}">{{ __('admin.forms.title') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" class="form-control"
                                                        id="title_{{ $lang }}" name="title[{{ $lang }}]"
                                                        value="{{ isset($seoMeta) ? $seoMeta->getTranslation('title', $lang, false) : old("title.$lang") }}"
                                                        required>
                                                </div>
                                                <!-- Description -->
                                                <div class="form-group">
                                                    <label for="description_{{ $lang }}">{{ __('admin.forms.description') }}
                                                        ({{ strtoupper($lang) }})</label>
                                                    <textarea class="form-control" id="description_{{ $lang }}" rows="8" name="description[{{ $lang }}]">{{ isset($seoMeta) ? $seoMeta->getTranslation('description', $lang, false) : old("description.$lang") }}</textarea>
                                                </div>
                                                <!-- Keywords -->
                                                <div class="form-group">
                                                    <label for="keywords_{{ $lang }}">{{ __('admin.forms.keywords') }}
                                                        ({{ strtoupper($lang) }})</label>
                                                    <input type="text" class="form-control"
                                                        id="keywords_{{ $lang }}"
                                                        name="keywords[{{ $lang }}]"
                                                        value="{{ isset($seoMeta) ? $seoMeta->getTranslation('keywords', $lang, false) : old("keywords.$lang") }}">
                                                </div>
                                                <!-- Open Graph Title -->
                                                <div class="form-group">
                                                    <label for="og_title_{{ $lang }}">{{ __('admin.forms.og_title') }}
                                                        ({{ strtoupper($lang) }})</label>
                                                    <input type="text" class="form-control"
                                                        id="og_title_{{ $lang }}"
                                                        name="og_title[{{ $lang }}]"
                                                        value="{{ isset($seoMeta) ? $seoMeta->getTranslation('og_title', $lang, false) : old("og_title.$lang") }}">
                                                </div>
                                                <!-- Open Graph Description -->
                                                <div class="form-group">
                                                    <label for="og_description_{{ $lang }}">{{ __('admin.forms.og_description') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <textarea class="form-control" id="og_description_{{ $lang }}" rows="8" name="og_description[{{ $lang }}]">{{ isset($seoMeta) ? $seoMeta->getTranslation('og_description', $lang, false) : old("og_description.$lang") }}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                        <!-- Canonical URL -->
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="canonical">{{ __('admin.forms.canonical') }}</label>
                                                <input type="text" class="form-control" id="canonical" name="canonical"
                                                    value="{{ isset($seoMeta) ? $seoMeta->canonical : old('canonical') }}">
                                            </div>
                                        </div>
                                        <div class="card-footer">
                                            <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
