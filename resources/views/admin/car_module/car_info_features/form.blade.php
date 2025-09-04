@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.car_info_features.update', $data->id) : route('admin.car_info_features.store') }}"
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
                                        {{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }} Car Info
                                        Feature</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="car_model_id">{{ __('admin.forms.model') }}</label>
                                                <select name="car_model_id" id="car_model_id" class="form-control">
                                                    <option value="">{{ __('admin.forms.choose_model') }}</option>
                                                    @foreach ($models as $model)
                                                        <option value="{{ $model->id }}"
                                                            {{ old('car_model_id', $data->car_model_id ?? '') == $model->id ? 'selected' : '' }}>
                                                            {{ $model->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('car_model_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="title">{{ __('admin.forms.title') }}</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    value="{{ old('title', $data->title ?? '') }}">
                                                @error('title')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="description">{{ __('admin.forms.description') }}</label>
                                                <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $data->description ?? '') }}</textarea>
                                                @error('description')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="rank">Rank</label>
                                                <input type="number" class="form-control" id="rank" name="rank"
                                                    value="{{ old('rank', $data->rank ?? '') }}">
                                                @error('rank')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                @include('admin.layouts.components.media-upload', [
                                                    'label' => __('admin.forms.image'),
                                                    'name' => 'image',
                                                    'oldFile' => $data->image ?? null,
                                                    'previewPath' => $data->image_path ?? null,
                                                    'required' => true,
                                                ])
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
