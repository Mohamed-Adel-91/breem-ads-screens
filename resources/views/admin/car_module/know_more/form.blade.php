@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.know_more.update', $data->id) : route('admin.know_more.store') }}"
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
                                        {{ __('admin.forms.know_more_entity') }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="car_model_id">{{ __('admin.forms.model') }}</label>
                                                <select class="form-control" id="car_model_id" name="car_model_id" required>
                                                    <option value="">{{ __('admin.forms.choose_model') }}</option>
                                                    @foreach (\App\Models\CarModel::orderBy('name')->get() as $carModel)
                                                        <option value="{{ $carModel->id }}"
                                                            @if (old('car_model_id', $data->car_model_id ?? '') == $carModel->id) selected @endif>
                                                            {{ $carModel->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="title">{{ __('admin.forms.title') }}</label>
                                                <input type="text" class="form-control" id="title" name="title"
                                                    value="{{ old('title', $data->title ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="description">{{ __('admin.forms.description') }}</label>
                                                <textarea class="form-control" id="description" name="description" rows="4">{{ old('description', $data->description ?? '') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            {{-- Source selector --}}
                                            @php
                                                $isYoutube = old('video_source')
                                                    ? old('video_source') === 'youtube'
                                                    : isset($data) &&
                                                        $data->video &&
                                                        preg_match(
                                                            '/^(https?:)?\/\/(www\.)?(youtube\.com|youtu\.be)/i',
                                                            $data->video,
                                                        );
                                                $selectedSource = $isYoutube ? 'youtube' : 'upload';
                                            @endphp

                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label
                                                        class="d-block">{{ __('admin.forms.video_source') ?: 'Video Source' }}</label>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            id="video_source_upload" name="video_source" value="upload"
                                                            {{ old('video_source', $selectedSource) === 'upload' ? 'checked' : '' }}>
                                                        <label class="form-check-label"
                                                            for="video_source_upload">{{ __('admin.forms.upload_file') ?: 'Upload file' }}</label>
                                                    </div>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                            id="video_source_youtube" name="video_source" value="youtube"
                                                            {{ old('video_source', $selectedSource) === 'youtube' ? 'checked' : '' }}>
                                                        <label class="form-check-label"
                                                            for="video_source_youtube">{{ __('admin.forms.youtube_link') ?: 'YouTube link' }}</label>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Upload file pane --}}
                                            <div class="col-md-12" id="upload_video_wrap"
                                                style="{{ old('video_source', $selectedSource) === 'youtube' ? 'display:none' : '' }}">
                                                <div class="form-group">
                                                    @include('admin.layouts.components.media-upload', [
                                                        'label' => __('admin.forms.video'),
                                                        'name' => 'video',
                                                        'oldFile' => $data->video ?? null,
                                                        'previewPath' => $data->video_path ?? null,
                                                        'required' => false,
                                                        'acceptedTypes' => 'video/mp4',
                                                    ])
                                                </div>
                                            </div>

                                            {{-- YouTube link pane --}}
                                            <div class="col-md-12" id="youtube_link_wrap"
                                                style="{{ old('video_source', $selectedSource) === 'youtube' ? '' : 'display:none' }}">
                                                <div class="form-group">
                                                    <label
                                                        for="video_link">{{ __('admin.forms.youtube_link') ?: 'YouTube link' }}</label>
                                                    <input type="url" class="form-control" id="video_link"
                                                        name="video" {{-- same field name; we toggle disabled below --}}
                                                        placeholder="https://www.youtube.com/watch?v=xxxxxxx"
                                                        value="{{ old('video', $isYoutube ? $data->video ?? '' : '') }}">
                                                    @error('video')
                                                        <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>

                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                @include('admin.layouts.components.media-upload', [
                                                    'label' => __('admin.forms.image'),
                                                    'name' => 'image',
                                                    'oldFile' => $data->image ?? null,
                                                    'previewPath' => $data->image_path ?? null,
                                                    'required' => false,
                                                ])
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="hero_section">{{ __('admin.forms.hero_section') }}</label>
                                                <input type="checkbox" id="hero_section" name="hero_section" value="1"
                                                    @if (old('hero_section', $data->hero_section ?? false)) checked @endif>
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
@push('custom-js-scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadRadio = document.getElementById('video_source_upload');
            const youtubeRadio = document.getElementById('video_source_youtube');
            const uploadWrap = document.getElementById('upload_video_wrap');
            const youtubeWrap = document.getElementById('youtube_link_wrap');

            const fileInput = uploadWrap ? uploadWrap.querySelector('input[type="file"][name="video"]') : null;
            const textInput = youtubeWrap ? youtubeWrap.querySelector('input[name="video"]') : null;

            function apply(source) {
                if (source === 'upload') {
                    if (uploadWrap) uploadWrap.style.display = '';
                    if (youtubeWrap) youtubeWrap.style.display = 'none';
                    if (fileInput) {
                        fileInput.disabled = false;
                        fileInput.required = false;
                    }
                    if (textInput) {
                        textInput.disabled = true;
                        textInput.required = false;
                    }
                } else {
                    if (uploadWrap) uploadWrap.style.display = 'none';
                    if (youtubeWrap) youtubeWrap.style.display = '';
                    if (fileInput) {
                        fileInput.disabled = true;
                        fileInput.required = false;
                    }
                    if (textInput) {
                        textInput.disabled = false;
                        textInput.required = false;
                    }
                }
            }

            function current() {
                return youtubeRadio && youtubeRadio.checked ? 'youtube' : 'upload';
            }

            if (uploadRadio) uploadRadio.addEventListener('change', () => apply('upload'));
            if (youtubeRadio) youtubeRadio.addEventListener('change', () => apply('youtube'));

            apply(current());
        });
    </script>
@endpush
