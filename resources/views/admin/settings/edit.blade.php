@extends('admin.layouts.master')

@section('title', $title ?? \App\Support\Lang::t('admin.pages.settings.edit', $pageName ?? 'Settings'))

@section('content')
    @php
        $heading = \App\Support\Lang::t('admin.pages.settings.edit', $pageName ?? 'Settings');
        $formUrl = route('admin.settings.update', ['lang' => app()->getLocale()]);

        // Presentation-only grouping: cluster the existing setting keys by the
        // segment that precedes the first dot. Keys and values are untouched.
        $groupedSettings = collect($settings)->groupBy(function ($setting) {
            return str_contains($setting->key, '.')
                ? \Illuminate\Support\Str::before($setting->key, '.')
                : '__general__';
        })->sortKeys();

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $heading,
            'subtitle' => __('admin.settings.intro'),
            'breadcrumbs' => [
                ['label' => $heading],
            ],
        ])

        <form method="POST" action="{{ $formUrl }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @forelse ($groupedSettings as $groupKey => $groupSettings)
                @php
                    $isGeneral = $groupKey === '__general__';
                    $groupTitle = $isGeneral
                        ? __('admin.forms.general_data')
                        : \Illuminate\Support\Str::headline((string) $groupKey);
                @endphp

                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ $groupTitle }}</h2>
                        <x-admin.badge variant="light">
                            {{ $groupSettings->count() }}
                        </x-admin.badge>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($groupSettings as $setting)
                                @php
                                    // Read/write expressions preserved exactly from the legacy view.
                                    $raw = $setting->getAttribute('value');
                                    $isArray = is_array($raw);
                                    $display = $isArray
                                        ? json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                        : (string) $raw;
                                    $fieldId = 'setting_' . str_replace(['.', ' ', '/'], '_', $setting->key);
                                    $fieldName = 'settings[' . $setting->key . ']';
                                    $errorKey = 'settings.' . $setting->key;
                                    $hasError = $errors->has($errorKey);
                                    $looksLikeImage = !$isArray
                                        && $display !== ''
                                        && \Illuminate\Support\Str::endsWith(
                                            \Illuminate\Support\Str::lower(parse_url($display, PHP_URL_PATH) ?? $display),
                                            array_map(fn ($ext) => '.' . $ext, $imageExtensions),
                                        );
                                    $label = \Illuminate\Support\Str::headline(
                                        str_replace('.', ' ', $setting->key),
                                    );
                                @endphp

                                <div @class(['col-12', 'col-lg-6' => !$isArray])>
                                    <div class="form-group">
                                        <label for="{{ $fieldId }}">
                                            {{ $label }}
                                            <code class="ml-1">{{ $setting->key }}</code>
                                        </label>

                                        @if ($isArray)
                                            <textarea id="{{ $fieldId }}"
                                                      name="{{ $fieldName }}"
                                                      rows="6"
                                                      spellcheck="false"
                                                      aria-describedby="{{ $fieldId }}_help"
                                                      @class(['form-control', 'is-invalid' => $hasError])>{{ $display }}</textarea>
                                        @else
                                            <input type="text"
                                                   id="{{ $fieldId }}"
                                                   name="{{ $fieldName }}"
                                                   value="{{ $display }}"
                                                   aria-describedby="{{ $fieldId }}_help"
                                                   @class(['form-control', 'is-invalid' => $hasError])>
                                        @endif

                                        @error($errorKey)
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror

                                        <small id="{{ $fieldId }}_help" class="form-text text-muted">
                                            {{ $isArray ? __('admin.settings.json_help') : __('admin.settings.text_help') }}
                                        </small>

                                        @if ($looksLikeImage)
                                            <x-admin.media-preview
                                                :url="\Illuminate\Support\Str::startsWith($display, ['http://', 'https://', '/']) ? $display : asset($display)"
                                                :alt="__('admin.media.preview_alt', ['label' => $label])"
                                                :caption="__('admin.media.current_image')"
                                                class="mt-2" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="admin-empty-state">
                            <i class="fe fe-sliders" aria-hidden="true"></i>
                            <span>{{ __('admin.settings.no_settings') }}</span>
                        </div>
                    </div>
                </div>
            @endforelse

            @if ($groupedSettings->isNotEmpty())
                <div class="card">
                    <div class="card-body">
                        <x-admin.group-btn class="justify-content-end">
                            <x-admin.btn
                                :href="route('admin.dashboard', ['lang' => app()->getLocale()])"
                                variant="light"
                                icon="arrow-left">
                                {{ __('admin.buttons.back') }}
                            </x-admin.btn>
                            <x-admin.btn type="submit" id="submit" icon="save">
                                {{ __('admin.forms.save_button') }}
                            </x-admin.btn>
                        </x-admin.group-btn>
                    </div>
                </div>
            @endif
        </form>
    </div>
@endsection
