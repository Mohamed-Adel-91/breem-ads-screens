@extends('admin.layouts.master')

@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header', ['pageName' => $page->name])
            <div class="main-container">
                @include('admin.layouts.alerts')

                <form method="POST" action="{{ route('admin.cms.who.update', ['lang' => app()->getLocale()]) }}"
                    enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @php
                        $banner = $sections->get('second_banner');
                        $bannerData = $banner?->section_data ?? [];
                        $who = $sections->get('who_we');
                        $whoData = $who?->section_data ?? [];
                        $features = $who?->items ?? collect();
                        $port = $sections->get('port_image');
                        $portData = $port?->section_data ?? [];
                    @endphp

                    {{-- Banner --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Banner</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'banner_image',
                                    'inputId' => 'banner_image',
                                    'label' => 'Banner Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($bannerData, 'en.image_path'),
                                    'previewPath' => data_get($bannerData, 'en.image_path'),
                                ])
                            </div>
                        </div>
                    </div>

                    {{-- Who We Are --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Who We Are</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                <div class="form-group col-md-6">
                                    <label for="who_title_ar">Title (AR)</label>
                                    <input type="text" class="form-control" id="who_title_ar" name="who_title_ar"
                                        value="{{ old('who_title_ar', data_get($whoData, 'ar.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="who_title_en">Title (EN)</label>
                                    <input type="text" class="form-control" id="who_title_en" name="who_title_en"
                                        value="{{ old('who_title_en', data_get($whoData, 'en.title')) }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="who_description_ar">Description (AR)</label>
                                    <textarea class="form-control" id="who_description_ar" name="who_description_ar" rows="4">{{ old('who_description_ar', data_get($whoData, 'ar.description')) }}</textarea>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="who_description_en">Description (EN)</label>
                                    <textarea class="form-control" id="who_description_en" name="who_description_en" rows="4">{{ old('who_description_en', data_get($whoData, 'en.description')) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Key Features</h5>
                        </div>
                        <div class="card-body">
                            @forelse ($features as $item)
                                @php
                                    $itemData = $item->data ?? [];
                                    $bulletsAr = implode("\n", data_get($itemData, 'ar.bullets', []));
                                    $bulletsEn = implode("\n", data_get($itemData, 'en.bullets', []));
                                @endphp
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="mb-3">Feature #{{ $item->order }}</h6>
                                    <div class="row gutters">
                                        <div class="form-group col-md-6">
                                            <label for="feature_title_ar_{{ $item->id }}">Title (AR)</label>
                                            <input type="text" class="form-control"
                                                id="feature_title_ar_{{ $item->id }}"
                                                name="features[{{ $item->id }}][title_ar]"
                                                value="{{ old('features.' . $item->id . '.title_ar', data_get($itemData, 'ar.title')) }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="feature_title_en_{{ $item->id }}">Title (EN)</label>
                                            <input type="text" class="form-control"
                                                id="feature_title_en_{{ $item->id }}"
                                                name="features[{{ $item->id }}][title_en]"
                                                value="{{ old('features.' . $item->id . '.title_en', data_get($itemData, 'en.title')) }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="feature_text_ar_{{ $item->id }}">Text (AR)</label>
                                            <textarea class="form-control" id="feature_text_ar_{{ $item->id }}"
                                                name="features[{{ $item->id }}][text_ar]" rows="3">{{ old('features.' . $item->id . '.text_ar', data_get($itemData, 'ar.text')) }}</textarea>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="feature_text_en_{{ $item->id }}">Text (EN)</label>
                                            <textarea class="form-control" id="feature_text_en_{{ $item->id }}"
                                                name="features[{{ $item->id }}][text_en]" rows="3">{{ old('features.' . $item->id . '.text_en', data_get($itemData, 'en.text')) }}</textarea>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="feature_bullets_ar_{{ $item->id }}">Bullets (AR)</label>
                                            <textarea class="form-control" id="feature_bullets_ar_{{ $item->id }}"
                                                name="features[{{ $item->id }}][bullets_ar]" rows="3"
                                                placeholder="كل سطر يمثل نقطة جديدة">{{ old('features.' . $item->id . '.bullets_ar', $bulletsAr) }}</textarea>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="feature_bullets_en_{{ $item->id }}">Bullets (EN)</label>
                                            <textarea class="form-control" id="feature_bullets_en_{{ $item->id }}"
                                                name="features[{{ $item->id }}][bullets_en]" rows="3"
                                                placeholder="Enter each bullet on a new line">{{ old('features.' . $item->id . '.bullets_en', $bulletsEn) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted">No features configured.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- Portfolio Image --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Portfolio Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="row gutters">
                                @include('admin.layouts.components.media-upload', [
                                    'name' => 'port_image',
                                    'inputId' => 'port_image',
                                    'label' => 'Image',
                                    'acceptedTypes' => 'image/*',
                                    'oldFile' => data_get($portData, 'en.image_path'),
                                    'previewPath' => data_get($portData, 'en.image_path'),
                                ])
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
