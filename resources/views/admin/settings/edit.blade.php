@extends('admin.layouts.master')

@section('title', $title ?? \App\Support\Lang::t('admin.pages.settings.edit', $pageName ?? 'Settings'))

@section('content')
    @php
        $heading = \App\Support\Lang::t('admin.pages.settings.edit', $pageName ?? 'Settings');
        $formUrl = route('admin.settings.update', ['lang' => app()->getLocale()]);

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

        {{--
            THE SETTINGS SCREEN IS ORGANISED BY WHAT A VALUE IS FOR, NOT BY ITS KEY.

            It used to render one text input per row of the settings table, labelled with
            the raw key — "Sidebar Icons / sidebar.icons / Plain text value" — and grouped
            by the segment before the first dot. That asked an operator to know the storage
            schema, and it offered no way to tell a live setting from a dead one.

            Every value now sits under the heading that says what it does, with the control
            its type deserves: a file picker for a logo, a URL box per social network, a
            pair of address fields. Raw keys are not shown at all — they are an
            implementation detail.

            The generic card at the bottom survives ONLY for keys this page has no
            purpose-built control for. On a standard installation there are none and it
            does not render.
        --}}
        <form method="POST" action="{{ $formUrl }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- ------------------------------------------------ business information --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.settings.business.title') }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('admin.settings.business.intro') }}</p>

                    <div class="row">
                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label for="setting_email">{{ __('admin.settings.business.email') }}</label>
                                <input type="email" id="setting_email" name="email" dir="ltr"
                                       value="{{ old('email', $data['email']) }}"
                                       aria-describedby="setting_email_help"
                                       @class(['form-control', 'is-invalid' => $errors->has('email')])>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="setting_email_help" class="form-text text-muted">
                                    {{ __('admin.settings.business.email_help') }}
                                </small>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label for="setting_phone">{{ __('admin.settings.business.phone') }}</label>
                                {{--
                                    dir="ltr" on the control only. A phone number is a
                                    machine value; typed into an RTL field the leading `+`
                                    lands at the visual end and gets stored that way.
                                --}}
                                <input type="text" id="setting_phone" name="phone" dir="ltr"
                                       value="{{ old('phone', $data['phone']) }}"
                                       placeholder="+966500000000"
                                       aria-describedby="setting_phone_help"
                                       @class(['form-control', 'is-invalid' => $errors->has('phone')])>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="setting_phone_help" class="form-text text-muted">
                                    {{ __('admin.settings.business.phone_help') }}
                                </small>
                            </div>
                        </div>

                        @foreach (['ar' => __('admin.settings.business.address_ar'), 'en' => __('admin.settings.business.address_en')] as $locale => $addressLabel)
                            <div class="col-12 col-lg-6">
                                <div class="form-group">
                                    <label for="setting_address_{{ $locale }}">{{ $addressLabel }}</label>
                                    <input type="text"
                                           id="setting_address_{{ $locale }}"
                                           name="address[{{ $locale }}]"
                                           dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
                                           value="{{ old('address.' . $locale, $data['address'][$locale] ?? '') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('address.' . $locale)])>
                                    @error('address.' . $locale)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach

                        <div class="col-12">
                            <div class="form-group">
                                <label for="setting_location">{{ __('admin.settings.business.map') }}</label>
                                <input type="url" id="setting_location" name="location" dir="ltr"
                                       value="{{ old('location', $data['location']) }}"
                                       placeholder="https://www.google.com/maps/embed?pb=..."
                                       aria-describedby="setting_location_help"
                                       @class(['form-control', 'is-invalid' => $errors->has('location')])>
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small id="setting_location_help" class="form-text text-muted">
                                    {{ __('admin.settings.business.map_help') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------------ branding --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.settings.branding.title') }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('admin.settings.branding.intro') }}</p>

                    <div class="row">
                        @foreach ([
                            'header.logo' => ['field' => 'header_logo', 'label' => __('admin.settings.branding.header_logo'), 'help' => __('admin.settings.branding.header_logo_help')],
                            'footer.logo' => ['field' => 'footer_logo', 'label' => __('admin.settings.branding.footer_logo'), 'help' => __('admin.settings.branding.footer_logo_help')],
                        ] as $key => $logo)
                            <div class="col-12 col-lg-6">
                                {{--
                                    The project's existing uploader: preview, file picker,
                                    accepted types and size hint, and a client-side preview
                                    that degrades to a plain input without JavaScript. There
                                    is no path text box — an operator should never be asked
                                    to type `frontend/img/logo.png`.
                                --}}
                                <x-admin.image-uploader
                                    :name="$logo['field']"
                                    :label="$logo['label']"
                                    :preview-url="$data['logos'][$key]['src']"
                                    :help-text="$logo['help']"
                                    max-size-hint="5 MB"
                                    accepted-types-hint="JPG, PNG, GIF, WEBP"
                                    accept="image/jpeg,image/png,image/gif,image/webp" />

                                @unless ($data['logo_configured'][$key])
                                    <p class="text-muted small">
                                        {{ __('admin.settings.branding.using_default') }}
                                    </p>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- -------------------------------------------------------- social media --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.settings.business.social') }}</h2>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ __('admin.settings.business.social_help') }}</p>

                    <div class="row">
                        @foreach (\App\Support\SocialPlatforms::all() as $platform => $meta)
                            <div class="col-12 col-lg-6">
                                <div class="form-group">
                                    <label for="setting_{{ $platform }}">{{ $meta['label'] }}</label>
                                    <input type="url"
                                           id="setting_{{ $platform }}"
                                           name="{{ $platform }}"
                                           dir="ltr"
                                           value="{{ old($platform, $data['socials'][$platform] ?? '') }}"
                                           placeholder="{{ $meta['placeholder'] }}"
                                           @class(['form-control', 'is-invalid' => $errors->has($platform)])>
                                    @error($platform)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------ screen devices --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="card-title mb-0">{{ __('admin.settings.devices.title') }}</h2>
                </div>
                <div class="card-body">
                    {{--
                        Not website content. `site.lang_switch` is on the Device API's
                        allow-list (DeviceConfigService::ALLOWED_SETTING_KEYS), so it is
                        published to every paired screen. It was previously shown as an
                        unexplained "Site Lang Switch = EN" text box, which gave no hint
                        that editing it changed what the fleet receives.
                    --}}
                    <p class="text-muted">{{ __('admin.settings.devices.intro') }}</p>

                    <div class="row">
                        @foreach (['ar' => __('admin.settings.devices.lang_switch_ar'), 'en' => __('admin.settings.devices.lang_switch_en')] as $locale => $switchLabel)
                            <div class="col-12 col-lg-6">
                                <div class="form-group">
                                    <label for="setting_lang_switch_{{ $locale }}">{{ $switchLabel }}</label>
                                    <input type="text"
                                           id="setting_lang_switch_{{ $locale }}"
                                           name="lang_switch[{{ $locale }}]"
                                           dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}"
                                           value="{{ old('lang_switch.' . $locale, $data['lang_switch'][$locale] ?? '') }}"
                                           @class(['form-control', 'is-invalid' => $errors->has('lang_switch.' . $locale)])>
                                    @error('lang_switch.' . $locale)
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ------------------------------------------------------ anything else --}}
            @if ($settings->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                        <h2 class="card-title mb-0">{{ __('admin.settings.other.title') }}</h2>
                        <x-admin.badge variant="light">{{ $settings->count() }}</x-admin.badge>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">{{ __('admin.settings.other.intro') }}</p>

                        <div class="row">
                            @foreach ($settings as $setting)
                                @php
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
            @endif

            {{--
                Unconditional. This used to be gated on the generic list having rows, which
                was fine when that list was the whole form; the labelled sections above are
                always rendered, so gating the only submit control on someone else's data
                would leave the page unsavable on a standard installation.
            --}}
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
        </form>
    </div>
@endsection
