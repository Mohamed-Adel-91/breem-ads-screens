@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.car_models.update', $data->id) : route('admin.car_models.store') }}"
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
                                        {{ __('admin.forms.car_model') }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="brand_id">{{ __('admin.forms.brand') }}</label>
                                                <select class="form-control" id="brand_id" name="brand_id">
                                                    <option value="">{{ __('admin.forms.choose_brand') }}</option>
                                                    @foreach ($brands as $brand)
                                                        <option value="{{ $brand->id }}"
                                                            {{ isset($data) && $data->brand_id == $brand->id ? 'selected' : '' }}>
                                                            {{ $brand->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('brand_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="shape_id">{{ __('admin.forms.shape') }}</label>
                                                <select class="form-control" id="shape_id" name="shape_id">
                                                    <option value="">{{ __('admin.forms.choose_shape') }}</option>
                                                    @foreach ($shapes as $shape)
                                                        <option value="{{ $shape->id }}"
                                                            {{ isset($data) && $data->shape_id == $shape->id ? 'selected' : '' }}>
                                                            {{ $shape->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('shape_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">{{ __('admin.forms.name') }}</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    value="{{ isset($data) ? $data->name : '' }}">
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="description">{{ __('admin.forms.description') }}</label>
                                                <textarea class="form-control" id="description" name="description" rows="5">{{ isset($data) ? $data->description : '' }}</textarea>
                                                @if ($errors->has('description'))
                                                    <span class="text-danger">{{ $errors->first('description') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="start_price">{{ __('admin.forms.start_price') }}</label>
                                                <input type="number" class="form-control" id="start_price"
                                                    name="start_price" value="{{ isset($data) ? $data->start_price : '' }}"
                                                    step="any">
                                                @error('start_price')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="year">{{ __('admin.forms.year') }}</label>
                                                <input type="number" class="form-control" id="year" name="year"
                                                    value="{{ isset($data) ? $data->year : '' }}">
                                                @error('year')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="horse_power">{{ __('admin.forms.horse_power') }}</label>
                                                <input type="number" class="form-control" id="horse_power"
                                                    name="horse_power"
                                                    value="{{ isset($data) ? $data->horse_power : '' }}">
                                                @error('horse_power')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="engine">{{ __('admin.forms.engine') }}</label>
                                                <input type="text" class="form-control" id="engine" name="engine"
                                                    value="{{ isset($data) ? $data->engine : '' }}">
                                                @error('engine')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            @php $engineTypes = $engineTypes ?? \App\Enums\EngineTypeEnum::asArrayWithDescriptions(); @endphp
                                            <div class="form-group">
                                                <label for="engine_type">{{ __('admin.forms.engine_type') }}</label>
                                                <select class="form-control" id="engine_type" name="engine_type">
                                                    <option value="">-- اختر نوع المحرك --</option>
                                                    @foreach ($engineTypes as $key => $label)
                                                        <option value="{{ $key }}"
                                                            {{ isset($data) && $data->engine_type && $data->engine_type->value == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('engine_type')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="torque">{{ __('admin.forms.torque') ?: 'Torque' }}</label>
                                                <input type="text" class="form-control" id="torque" name="torque"
                                                    value="{{ isset($data) ? $data->torque : old('torque') }}">
                                                @error('torque')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label
                                                    for="gear_box">{{ __('admin.forms.gear_box') ?: 'Gear Box' }}</label>
                                                <input type="text" class="form-control" id="gear_box"
                                                    name="gear_box"
                                                    value="{{ isset($data) ? $data->gear_box : old('gear_box') }}">
                                                @error('gear_box')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="show_status">{{ __('admin.forms.show_status') }}</label>
                                                <select class="form-control" id="show_status" name="show_status">
                                                    <option value="1"
                                                        {{ isset($data) && $data->show_status == 1 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.yes') }}</option>
                                                    <option value="0"
                                                        {{ isset($data) && $data->show_status == 0 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.no') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label
                                                    for="is_home">{{ __('admin.forms.is_home') ?: 'Show on Home' }}</label>
                                                <select class="form-control" id="is_home" name="is_home">
                                                    <option value="0"
                                                        {{ (isset($data) && $data->is_home == 0) || old('is_home') === '0' ? 'selected' : '' }}>
                                                        {{ __('admin.forms.no') }}
                                                    </option>
                                                    <option value="1"
                                                        {{ (isset($data) && $data->is_home == 1) || old('is_home') === '1' ? 'selected' : '' }}>
                                                        {{ __('admin.forms.yes') }}
                                                    </option>
                                                </select>
                                                @error('is_home')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row gutters">
                                        <div class="form-group">
                                            <label for="colors">{{ __('admin.forms.colors') }}</label>
                                            <select class="form-control" id="colors" name="colors[]"
                                                multiple="multiple">
                                                @foreach ($colors as $color)
                                                    <option value="{{ $color->id }}"
                                                        {{ in_array($color->id, old('colors', isset($data) ? $data->colors->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                                                        {{ $color->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="row gutters" id="color-images-wrapper">
                                            @foreach ($colors as $color)
                                                @php
                                                    $pivot = isset($data) ? $data->colors->find($color->id) : null;
                                                    $pivotImage = $pivot && $pivot->pivot ? $pivot->pivot->image : null;
                                                @endphp
                                                <div class="col-md-6">
                                                    <div class="color-image-input" data-color="{{ $color->id }}"
                                                        style="display: none;">
                                                        @include('admin.layouts.components.media-upload', [
                                                            'label' =>
                                                                __('admin.forms.color_image') .
                                                                ' - ' .
                                                                $color->name,
                                                            'name' => 'color_images[' . $color->id . ']',
                                                            'inputId' => 'color_image_' . $color->id,
                                                            'oldFile' => $pivotImage,
                                                            'previewPath' =>
                                                                $pivotImage && $pivot->pivot
                                                                    ? $pivot->pivot->image_path
                                                                    : null,
                                                            'class' => 'form-group col-md-6',
                                                        ])
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.image'),
                                            'name' => 'image',
                                            'oldFile' => $data->image ?? null,
                                            'previewPath' => $data->image_path ?? null,
                                        ])
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.banner'),
                                            'name' => 'banner',
                                            'oldFile' => $data->banner ?? null,
                                            'previewPath' => $data->banner_path ?? null,
                                        ])

                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.banner_tablet') ?: 'Banner (Tablet)',
                                            'name' => 'banner_tablet',
                                            'oldFile' => $data->banner_tablet ?? null,
                                            'previewPath' => $data->banner_tablet_path ?? null,
                                        ])

                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.banner_mobile') ?: 'Banner (Mobile)',
                                            'name' => 'banner_mobile',
                                            'oldFile' => $data->banner_mobile ?? null,
                                            'previewPath' => $data->banner_mobile_path ?? null,
                                        ])

                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.catalog'),
                                            'name' => 'catalog',
                                            'oldFile' => $data->catalog ?? null,
                                            'previewPath' => $data->catalog_path ?? null,
                                            'acceptedTypes' => 'application/pdf',
                                        ])
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.maintenance_schedule_pdf'),
                                            'name' => 'maintenance_schedule_pdf',
                                            'oldFile' => $data->maintenance_schedule_pdf ?? null,
                                            'previewPath' => $data->maintenance_schedule_pdf_path ?? null,
                                            'acceptedTypes' => 'application/pdf',
                                        ])
                                    </div>
                                    <div class="row gutters">
                                        <div class="form-group col-md-6">
                                            <label for="view_360_degree">{{ __('admin.forms.view_360_degree') }}</label>
                                            <input type="text" class="form-control" id="view_360_degree"
                                                name="view_360_degree"
                                                value="{{ isset($data) ? $data->view_360_degree : '' }}">
                                            @error('view_360_degree')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
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

@push('custom-css-scripts')
    <style>
        .multiselect-container>li>a>label {
            display: flex;
            justify-content: space-between;
        }
    </style>
@endpush
@push('custom-js-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const select = document.getElementById('colors');
            const wrapper = document.getElementById('color-images-wrapper');

            function toggleInputs() {
                const selected = Array.from(select.selectedOptions).map(o => o.value);
                wrapper.querySelectorAll('.color-image-input').forEach(div => {
                    if (selected.includes(div.dataset.color)) {
                        div.style.display = 'block';
                    } else {
                        div.style.display = 'none';
                    }
                });
            }
            $('#colors').multiselect({
                onChange: toggleInputs
            });
            toggleInputs();
        });
    </script>
@endpush
