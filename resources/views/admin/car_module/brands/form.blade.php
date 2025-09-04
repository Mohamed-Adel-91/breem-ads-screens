@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.brands.update', $data->id) : route('admin.brands.store') }}"
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
                                        {{ __('admin.forms.brand_entity') }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">{{ __('admin.forms.name') }}</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    value="{{ isset($data) ? $data->name : '' }}">
                                                @if ($errors->has('name'))
                                                    <span class="text-danger">{{ $errors->first('name') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="show_status">{{ __('admin.forms.show_status') }}</label>
                                                <select class="form-control" id="show_status" name="show_status">
                                                    <option value="1"
                                                        {{ isset($data) && $data->show_status == 1 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.yes') }}
                                                    </option>
                                                    <option value="0"
                                                        {{ isset($data) && $data->show_status == 0 ? 'selected' : '' }}>
                                                        {{ __('admin.forms.no') }}
                                                    </option>
                                                </select>
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
                                        <!-- Image Upload Section -->
                                        @include('admin.layouts.components.media-upload', [
                                            'label' => __('admin.forms.logo'),
                                            'name' => 'logo',
                                            'oldFile' => $data->logo ?? null,
                                            'previewPath' => $data->logo_path ?? null,
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
                                        <div class="col-12">
                                            <div id="slider-images-container" class="row gutters">
                                                @if (isset($data) && $data->slider_images)
                                                    @foreach ($data->slider_images as $index => $image)
                                                        <div class="slider-image-block col-md-6">
                                                            @include(
                                                                'admin.layouts.components.media-upload',
                                                                [
                                                                    'label' => __('admin.forms.slider_image'),
                                                                    'name' => 'slider_images[]',
                                                                    'oldFile' => $image,
                                                                    'previewPath' =>
                                                                        $data->slider_images_paths[$index] ?? null,
                                                                    'inputId' => 'slider_image_' . $index,
                                                                    'class' => 'form-group',
                                                                ]
                                                            )
                                                            <button type="button"
                                                                class="btn btn-danger mt-2 remove-slider-image"
                                                                data-old-file="{{ $image }}">{{ __('admin.forms.delete_button') }}</button>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-secondary mt-2"
                                                id="add-slider-image">{{ __('admin.forms.add_slider_image_button') }}</button>
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
@push('custom-js-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let sliderIndex = {{ isset($data) && $data->slider_images ? count($data->slider_images) : 0 }};

            document.getElementById('add-slider-image').addEventListener('click', function() {
                const index = sliderIndex++;
                const container = document.getElementById('slider-images-container');
                const div = document.createElement('div');
                div.className = 'slider-image-block col-md-6';
                div.innerHTML = `
                    <div class="form-group">
                        <label for="slider_image_${index}">{{ __('admin.forms.slider_image') }}</label>
                        <input type="file" class="form-control-file" id="slider_image_${index}" name="slider_images[]" accept="image/*">
                        <div id="preview_slider_image_${index}" class="mt-2">
                            <p>No media selected</p>
                            <img src="" class="img-thumbnail" style="max-height: 200px; width: auto; display: none;">
                        </div>
                        <button type="button" class="btn btn-danger mt-2 remove-slider-image">{{ __('admin.forms.delete_button') }}</button>
                    </div>`;
                container.appendChild(div);
            });

            document.getElementById('slider-images-container').addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-slider-image')) {
                    const block = e.target.closest('.slider-image-block');
                    const oldFile = e.target.getAttribute('data-old-file');
                    if (oldFile) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'delete_slider_images[]';
                        input.value = oldFile;
                        this.appendChild(input);
                    }
                    const deleteUrl = e.target.getAttribute('data-delete-url');
                    if (oldFile && deleteUrl) {
                        axios.post(deleteUrl, {
                            filename: oldFile
                        }).catch(function(error) {
                            console.error(error);
                        });
                    }
                    block.remove();
                }
            });

            document.getElementById('slider-images-container').addEventListener('change', function(e) {
                if (e.target.type === 'file') {
                    const file = e.target.files[0];
                    const previewContainer = document.getElementById('preview_' + e.target.id);
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            const img = previewContainer.querySelector('img');
                            img.src = ev.target.result;
                            img.style.display = 'block';
                            const p = previewContainer.querySelector('p');
                            if (p) p.style.display = 'none';
                        };
                        reader.readAsDataURL(file);
                    } else {
                        const img = previewContainer.querySelector('img');
                        if (img) img.style.display = 'none';
                        const p = previewContainer.querySelector('p');
                        if (p) p.style.display = 'block';
                    }
                }
            });
        });
    </script>
@endpush
