@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $isEditing = isset($data);
        $indexUrl = route('admin.roles.index', ['lang' => app()->getLocale()]);
        $selectedPermissions = old(
            'permissions',
            $isEditing ? $data->permissions->pluck('id')->all() : [],
        );
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.roles'), 'url' => $indexUrl],
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
                  ? route('admin.roles.update', [
                      'role' => $data->id,
                      'lang' => app()->getLocale(),
                  ])
                  : route('admin.roles.store', ['lang' => app()->getLocale()]) }}">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">
                        {{ $isEditing ? __('admin.forms.edit') : __('admin.forms.create') }}
                        {{ __('admin.sidebar.roles') }}
                    </h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 col-lg-6">
                            <div class="form-group">
                                <label for="name">
                                    {{ __('admin.forms.name') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <input type="text"
                                       id="name"
                                       name="name"
                                       required
                                       value="{{ old('name', $data->name ?? '') }}"
                                       @class(['form-control', 'is-invalid' => $errors->has('name')])>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-12">
                            <x-admin.checkbox-group
                                name="permissions"
                                :options="$permissions"
                                :selected="$selectedPermissions"
                                :legend="__('admin.forms.permissions')"
                                :help-text="__('admin.forms.permissions_help')"
                                :group-by="true"
                                :columns="3" />
                        </div>
                    </div>
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
