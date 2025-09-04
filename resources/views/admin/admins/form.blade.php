@extends('admin.layouts.master')
@section('content')
    <div class="page-wrapper">
        @include('admin.layouts.sidebar')
        <div class="page-content">
            @include('admin.layouts.page-header')
            <div class="main-container">
                @include('admin.layouts.alerts')
                <form method="POST"
                    action="{{ isset($data) ? route('admin.admins.update', $data->id) : route('admin.admins.store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    @if (isset($data))
                        @method('PUT')
                    @endif
                    <div class="row gutters">
                        <div class="col-12">
                            <div class="card h-100">
                                <div class="card-header">
                                    <div class="card-title">{{ isset($data) ? __('admin.forms.edit') : __('admin.forms.create') }} {{ __('admin.forms.admin') }}</div>
                                </div>
                                <div class="card-body">
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="first_name">{{ __('admin.forms.first_name') }}</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name"
                                                    value="{{ old('first_name', $data->first_name ?? '') }}" required>
                                                @error('first_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="last_name">{{ __('admin.forms.last_name') }}</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name"
                                                    value="{{ old('last_name', $data->last_name ?? '') }}" required>
                                                @error('last_name')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">{{ __('admin.forms.email') }}</label>
                                                <input type="email" class="form-control" id="email" name="email"
                                                    value="{{ old('email', $data->email ?? '') }}" required>
                                                @error('email')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="mobile">{{ __('admin.forms.mobile') }}</label>
                                                <input type="text" class="form-control" id="mobile" name="mobile"
                                                    value="{{ old('mobile', $data->mobile ?? '') }}">
                                                @error('mobile')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row gutters">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password">{{ __('admin.forms.password') }}</label>
                                                <input type="password" name="password" id="password" class="form-control"
                                                    autocomplete="new-password" />
                                                @error('password')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="password_confirmation">{{ __('admin.forms.confirm_password') }}</label>
                                                <input type="password" name="password_confirmation"
                                                    id="password_confirmation" class="form-control"
                                                    autocomplete="new-password" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row gutters">

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="role">{{ __('admin.forms.role') }}</label>
                                                <select class="form-control" id="role" name="role" required>
                                                    <option value="">{{ __('admin.forms.choose_role') }}</option>
                                                    @foreach ($roles as $key => $name)
                                                        @if (in_array($key, [4, 5]))
                                                            @continue
                                                        @endif
                                                        <option value="{{ $key }}"
                                                            {{ old('role', $data->role ?? '') == $key ? 'selected' : '' }}>
                                                            {{ $name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('role')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    @if (isset($data))
                                        <div class="row gutters">
                                            <div class="form-group col-6">
                                                @include('admin.layouts.components.media-upload', [
                                                    'label' => __('admin.forms.profile_picture'),
                                                    'name' => 'profile_picture',
                                                    'oldFile' => $data->profile_picture,
                                                    'previewPath' => $data->image_path,
                                                    'sizeHint' => '(680×440)',
                                                ])
                                            </div>
                                        </div>
                                    @endif
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
