@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.videos.update', $data->id) : route('admin.videos.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">
                                        {{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }}
                                        {{ __('admin.forms.video') }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="title">{{ __('admin.forms.title') }}</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    value="{{ old('title', $data->title ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="added_date">{{ __('admin.forms.added_date') }}</label>
                                                <input type="date" class="form-control" id="added_date" name="added_date"
                                                    value="{{ old('added_date', isset($data->added_date) ? \Illuminate\Support\Carbon::parse($data->added_date)->format('Y-m-d') : '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="short_desc">{{ __('admin.forms.description') }}</label>
                                                <textarea class="form-control" id="short_desc" name="short_desc" rows="3">{{ old('short_desc', $data->short_desc ?? '') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="link">{{ __('admin.forms.link') }}</label>
                                                <input type="text" class="form-control" id="link" name="link"
                                                    value="{{ old('link', $data->link ?? '') }}">
                                            </div>
                                        </div>
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => 'صورة مصغرة',
                                            'name' => 'thumb_image',
                                            'oldFile' => $data->thumb_image ?? null,
                                            'previewPath' => $data->thumb_image_path ?? null,
                                        ])
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="home">{{ __('admin.forms.home') }}</label>
                                                <select class="form-control" id="home" name="home">
                                                    <option value="1"
                                                        {{ old('home', $data->home ?? '') == 1 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.yes') }}</option>
                                                    <option value="0"
                                                        {{ old('home', $data->home ?? '') == 0 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.no') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="hidden">{{ __('admin.forms.hidden') }}</label>
                                                <select class="form-control" id="hidden" name="hidden">
                                                    <option value="1"
                                                        {{ old('hidden', $data->hidden ?? '') == 1 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.yes') }}</option>
                                                    <option value="0"
                                                        {{ old('hidden', $data->hidden ?? '') == 0 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.no') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ord">{{ __('admin.forms.order') }}</label>
                                                <input type="number" class="form-control" id="ord" name="ord"
                                                    value="{{ old('ord', $data->ord ?? '') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit"
                                        class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
