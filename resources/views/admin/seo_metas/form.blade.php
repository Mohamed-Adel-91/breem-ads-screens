@extends('admin.layouts.master')

@section('title', $pageName)

@section('content')
    @php
        $languages = ['en', 'ar'];
        $record = $seoMeta ?? null;
        $isEditing = $record !== null;
        $indexUrl = route('admin.seo_metas.index', ['lang' => app()->getLocale()]);

        $localized = function (string $field, string $lang) use ($record) {
            return old(
                $field . '.' . $lang,
                $record ? $record->getTranslation($field, $lang, false) : '',
            );
        };
    @endphp

    <div class="container-fluid">
        @include('admin.layouts.page-header', [
            'title' => $pageName,
            'breadcrumbs' => [
                ['label' => __('admin.sidebar.seo_metas'), 'url' => $indexUrl],
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
                  ? route('admin.seo_metas.update', [
                      'lang' => app()->getLocale(),
                      'seo_meta' => $record->id,
                  ])
                  : route('admin.seo_metas.store', ['lang' => app()->getLocale()]) }}">
            @csrf
            @if ($isEditing)
                @method('PUT')
            @endif

            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">
                        {{ $isEditing ? __('admin.forms.edit') : __('admin.forms.create') }}
                        {{ __('admin.forms.seo_meta') }}
                    </h2>
                </div>
                <div class="card-body">
                    <p class="admin-section-title">{{ __('admin.seo_metas.sections.target') }}</p>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="page">
                                    {{ __('admin.forms.page_identifier') }}
                                    <span class="text-danger" aria-hidden="true">*</span>
                                </label>
                                <select id="page"
                                        name="page"
                                        required
                                        aria-describedby="page_help"
                                        @class(['form-control', 'is-invalid' => $errors->has('page')])>
                                    <option value="">{{ __('admin.forms.choose_page') }}</option>
                                    @foreach ($pagesRoutes as $routeName => $routeUrl)
                                        <option value="{{ $routeName }}"
                                            @selected(old('page', $record ? $record->page : '') === $routeName)>
                                            {{ $routeUrl }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('page')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="page_help" class="form-text text-muted">
                                    {{ __('admin.seo_metas.help.page') }}
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="canonical">{{ __('admin.forms.canonical') }}</label>
                                <input type="text"
                                       id="canonical"
                                       name="canonical"
                                       inputmode="url"
                                       value="{{ old('canonical', $record ? $record->canonical : '') }}"
                                       aria-describedby="canonical_help"
                                       @class(['form-control', 'is-invalid' => $errors->has('canonical')])>
                                @error('canonical')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="canonical_help" class="form-text text-muted">
                                    {{ __('admin.seo_metas.help.canonical') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                @foreach ($languages as $lang)
                    <div class="col-lg-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header">
                                <h2 class="card-title mb-0">
                                    {{ __('admin.seo_metas.sections.content', ['locale' => strtoupper($lang)]) }}
                                </h2>
                            </div>
                            <div class="card-body" @if ($lang === 'ar') dir="rtl" @else dir="ltr" @endif>
                                <div class="form-group">
                                    <label for="title_{{ $lang }}">
                                        {{ __('admin.forms.title') }} ({{ strtoupper($lang) }})
                                        <span class="text-danger" aria-hidden="true">*</span>
                                    </label>
                                    <input type="text"
                                           id="title_{{ $lang }}"
                                           name="title[{{ $lang }}]"
                                           required
                                           lang="{{ $lang }}"
                                           value="{{ $localized('title', $lang) }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('title.' . $lang)])>
                                    @error('title.' . $lang)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="description_{{ $lang }}">
                                        {{ __('admin.forms.description') }} ({{ strtoupper($lang) }})
                                    </label>
                                    <textarea id="description_{{ $lang }}"
                                              name="description[{{ $lang }}]"
                                              rows="5"
                                              lang="{{ $lang }}"
                                              @class(['form-control', 'is-invalid' => $errors->has('description.' . $lang)])>{{ $localized('description', $lang) }}</textarea>
                                    @error('description.' . $lang)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label for="keywords_{{ $lang }}">
                                        {{ __('admin.forms.keywords') }} ({{ strtoupper($lang) }})
                                    </label>
                                    <input type="text"
                                           id="keywords_{{ $lang }}"
                                           name="keywords[{{ $lang }}]"
                                           lang="{{ $lang }}"
                                           value="{{ $localized('keywords', $lang) }}"
                                           aria-describedby="keywords_{{ $lang }}_help"
                                           @class(['form-control', 'is-invalid' => $errors->has('keywords.' . $lang)])>
                                    @error('keywords.' . $lang)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small id="keywords_{{ $lang }}_help" class="form-text text-muted">
                                        {{ __('admin.seo_metas.help.keywords') }}
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label for="og_title_{{ $lang }}">
                                        {{ __('admin.forms.og_title') }} ({{ strtoupper($lang) }})
                                    </label>
                                    <input type="text"
                                           id="og_title_{{ $lang }}"
                                           name="og_title[{{ $lang }}]"
                                           lang="{{ $lang }}"
                                           value="{{ $localized('og_title', $lang) }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('og_title.' . $lang)])>
                                    @error('og_title.' . $lang)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group mb-0">
                                    <label for="og_description_{{ $lang }}">
                                        {{ __('admin.forms.og_description') }} ({{ strtoupper($lang) }})
                                    </label>
                                    <textarea id="og_description_{{ $lang }}"
                                              name="og_description[{{ $lang }}]"
                                              rows="5"
                                              lang="{{ $lang }}"
                                              @class(['form-control', 'is-invalid' => $errors->has('og_description.' . $lang)])>{{ $localized('og_description', $lang) }}</textarea>
                                    @error('og_description.' . $lang)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="card">
                <div class="card-body">
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
