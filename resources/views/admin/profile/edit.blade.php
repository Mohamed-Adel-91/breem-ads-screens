@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ __('admin.forms.update_profile') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="form-group col-6">
                                            <label for="first_name">{{ __('admin.forms.first_name') }}</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name"
                                                value="{{ old('first_name', $admin->first_name) }}">
                                            @error('first_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-6">
                                            <label for="last_name">{{ __('admin.forms.last_name') }}</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name"
                                                value="{{ old('last_name', $admin->last_name) }}">
                                            @error('last_name')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-6">
                                            @include('admin.layouts.components.media-upload', [
                                                'label' => __('admin.forms.profile_picture'),
                                                'name' => 'profile_picture',
                                                'oldFile' => $admin->profile_picture,
                                                'previewPath' => $admin->image_path,
                                                'sizeHint' => '(680×440)',
                                            ])
                                        </div>
                                        <div class="form-group col-6">
                                            <label for="mobile">{{ __('admin.forms.mobile') }}</label>
                                            <input type="text" class="form-control" id="mobile" name="mobile"
                                                value="{{ old('mobile', $admin->mobile) }}">
                                            @error('mobile')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
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
                <form method="POST" action="{{ route('admin.profile.updatePassword') }}" class="mt-4" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ __('admin.forms.change_password_button') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="form-group col-md-4">
                                            <label for="current_password">{{ __('admin.forms.current_password') }}</label>
                                            <input type="password" class="form-control" id="current_password"
                                                name="current_password">
                                            @error('current_password')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="password">{{ __('admin.forms.new_password') }}</label>
                                            <input type="password" class="form-control" id="password" name="password">
                                            @error('password')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="password_confirmation">{{ __('admin.forms.confirm_new_password') }}</label>
                                            <input type="password" class="form-control" id="password_confirmation"
                                                name="password_confirmation">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">{{ __('admin.forms.change_password_button') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
