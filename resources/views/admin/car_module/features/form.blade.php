@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.features.update', $data->id) : route('admin.features.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }} {{ __('admin.forms.feature') }}
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="feature_category_id">{{ __('admin.forms.category') }}</label>
                                                <select class="form-control" id="feature_category_id" name="feature_category_id">
                                                    <option value="">{{ __('admin.forms.choose_category') }}</option>
                                                    @foreach ($categories as $category)
                                                        <option value="{{ $category->id }}" {{ old('feature_category_id', $data->feature_category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('feature_category_id')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="name">{{ __('admin.forms.name') }}</label>
                                                <input type="text" class="form-control" id="name" name="name"
                                                    value="{{ old('name', $data->name ?? '') }}">
                                                @error('name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6 d-flex align-items-center">
                                            <div class="form-group mt-4">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input" id="status" name="status" value="1" {{ old('status', $data->status ?? false) ? 'checked' : '' }}>
                                                    <label class="custom-control-label" for="status">{{ __('admin.forms.active') }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">{{ __('admin.forms.save_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
