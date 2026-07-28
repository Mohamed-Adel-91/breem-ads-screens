@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $isEditing = isset($data);
        $indexUrl = route('admin.permissions.index', ['lang' => app()->getLocale()]);
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.permissions'), 'url' => $indexUrl],
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
                  ? route('admin.permissions.update', [
                      'permission' => $data->id,
                      'lang' => app()->getLocale(),
                  ])
                  : route('admin.permissions.store', ['lang' => app()->getLocale()]) }}">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title mb-0">
                        {{ $isEditing ? __('admin.forms.edit') : __('admin.forms.create') }}
                        {{ __('admin.sidebar.permissions') }}
                    </h2>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 col-lg-6">
                            <div class="form-group">
                                <label for="name">
                                    {{ __('admin.forms.route') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <select id="name"
                                        name="name"
                                        required
                                        @class(['form-control', 'is-invalid' => $errors->has('name')])>
                                    <option value="">{{ __('admin.forms.choose_route') }}</option>
                                    @foreach ($routes as $route)
                                        <option value="{{ $route }}"
                                            @selected(old('name', $data->name ?? '') === $route)>
                                            {{ $route }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
