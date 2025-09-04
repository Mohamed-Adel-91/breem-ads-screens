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
                        <form method="POST" enctype="multipart/form-data"
                            action="{{ isset($data) ? route('admin.car_interiors.update', $data->id) : route('admin.car_interiors.store') }}">
                            @csrf
                            @if (isset($data))
                                @method('PUT')
                            @endif
                            <div class="card">
                                <div class="card-body">
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
                                    </div>
                                    <div class="form-group">
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.images'),
                                            'name' => 'image',
                                            'oldFile' => $data->image ?? null,
                                            'previewPath' => $data->image_path ?? null,
                                            'required' => true,
                                        ])
                                    </div>
                                    <div class="form-group">
                                        <div class="form-group">
                                            <label for="hero_section">{{ __('admin.forms.hero_section') }}</label>
                                            <input type="checkbox" id="hero_section" name="hero_section" value="1"
                                                @if (old('hero_section', $data->hero_section ?? false)) checked @endif>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
