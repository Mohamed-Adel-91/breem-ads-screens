@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $isEditing = isset($data);
        $indexUrl = route('admin.admins.index', ['lang' => app()->getLocale()]);
        $selectedRoles = old('roles', $isEditing ? $data->roles->pluck('id')->all() : []);
        $selectedPermissions = old(
            'permissions',
            $isEditing ? $data->permissions->pluck('id')->all() : [],
        );
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.admins'), 'url' => $indexUrl],
                ['label' => $isEditing ? __('admin.forms.edit') : __('admin.forms.create')],
            ],
            'secondaryAction' => [
                'href' => $indexUrl,
                'label' => __('admin.buttons.close'),
                'icon' => 'arrow-left',
            ],
        ])

        <form method="POST"
              action="{{ $isEditing
                  ? route('admin.admins.update', [
                      'admin' => $data->id,
                      'lang' => app()->getLocale(),
                  ])
                  : route('admin.admins.store', ['lang' => app()->getLocale()]) }}"
              enctype="multipart/form-data">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">
                        {{ $isEditing ? __('admin.forms.edit') : __('admin.forms.create') }}
                        {{ __('admin.forms.admin') }}
                    </h2>
                </div>
                <div class="card-body">
                    <p class="admin-section-title">{{ __('admin.forms.profile_information') }}</p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">
                                    {{ __('admin.forms.first_name') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       id="first_name"
                                       name="first_name"
                                       required
                                       autocomplete="given-name"
                                       value="{{ old('first_name', $data->first_name ?? '') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('first_name')])>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">
                                    {{ __('admin.forms.last_name') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       id="last_name"
                                       name="last_name"
                                       required
                                       autocomplete="family-name"
                                       value="{{ old('last_name', $data->last_name ?? '') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('last_name')])>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">
                                    {{ __('admin.forms.email') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="email"
                                       id="email"
                                       name="email"
                                       required
                                       autocomplete="email"
                                       value="{{ old('email', $data->email ?? '') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('email')])>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mobile">{{ __('admin.forms.mobile') }}</label>
                                <input type="text"
                                       id="mobile"
                                       name="mobile"
                                       autocomplete="tel"
                                       value="{{ old('mobile', $data->mobile ?? '') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('mobile')])>
                                @error('mobile')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    <p class="admin-section-title">{{ __('admin.forms.password') }}</p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">
                                    {{ __('admin.forms.password') }}
                                    @unless ($isEditing)
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    @endunless
                                </label>
                                <input type="password"
                                       id="password"
                                       name="password"
                                       autocomplete="new-password"
                                       aria-describedby="password_help"
                                       @required(!$isEditing)
                                       @class(['form-control', 'is-invalid' => $errors->has('password')])>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="password_help" class="form-text text-muted">
                                    {{ __('admin.forms.password_min_hint') }}
                                    @if ($isEditing)
                                        {{ __('admin.forms.password_optional_hint') }}
                                    @endif
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation">
                                    {{ __('admin.forms.confirm_password') }}
                                    @unless ($isEditing)
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    @endunless
                                </label>
                                <input type="password"
                                       id="password_confirmation"
                                       name="password_confirmation"
                                       autocomplete="new-password"
                                       @required(!$isEditing)
                                       class="form-control">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <p class="admin-section-title">{{ __('admin.forms.roles') }}</p>

                    <div class="row">
                        <div class="col-12">
                            <x-admin.checkbox-group
                                name="roles"
                                :options="$availableRoles"
                                :selected="$selectedRoles"
                                :legend="__('admin.forms.roles')"
                                :help-text="__('admin.forms.roles_help')"
                                :columns="4" />
                        </div>
                    </div>

                    <hr>

                    <p class="admin-section-title">{{ __('admin.forms.permissions') }}</p>

                    <div class="row">
                        <div class="col-12">
                            <x-admin.checkbox-group
                                name="permissions"
                                :options="$availablePermissions"
                                :selected="$selectedPermissions"
                                :legend="__('admin.forms.permissions')"
                                :help-text="__('admin.forms.permissions_help')"
                                :group-by="true"
                                :columns="3" />
                        </div>
                    </div>

                    @if ($isEditing)
                        <hr>

                        <p class="admin-section-title">{{ __('admin.forms.profile_picture') }}</p>

                        <div class="row">
                            <div class="col-md-6">
                                <x-admin.image-uploader
                                    name="profile_picture"
                                    :label="__('admin.forms.profile_picture')"
                                    :preview-url="$data->image_path"
                                    :old-file="$data->profile_picture"
                                    size-hint="(680×440)" />
                            </div>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <x-admin.group-btn class="justify-content-end">
                        <x-admin.btn :href="$indexUrl" variant="light" icon="x">
                            {{ __('admin.buttons.close') }}
                        </x-admin.btn>
                        <x-admin.btn type="submit" icon="save">
                            {{ __('admin.forms.save_button') }}
                        </x-admin.btn>
                    </x-admin.group-btn>
                </div>
            </div>
        </form>
    </div>
@endsection
