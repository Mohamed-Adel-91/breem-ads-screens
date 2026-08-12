{{--
    Shared Place form body. Every input name (`name[en]`, `name[ar]`, `address[en]`,
    `address[ar]`, `type`) is emitted verbatim so the existing StorePlaceRequest /
    UpdatePlaceRequest contract is untouched.
--}}
@php
    $nameTranslations = $place->getTranslations('name');
    $addressTranslations = $place->getTranslations('address');
@endphp

<div class="card">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.places.form.heading') }}</h2>
    </div>
    <div class="card-body">
        {{-- Arabic inputs stay dir="rtl" and English dir="ltr" regardless of dashboard locale. --}}
        <x-admin.translatable-field
            label-key="admin.places.form.name_locale"
            id-prefix="place_name"
            :help="__('admin.places.form.name_help')"
            :names="['en' => 'name[en]', 'ar' => 'name[ar]']"
            :values="[
                'en' => old('name.en', data_get($nameTranslations, 'en')),
                'ar' => old('name.ar', data_get($nameTranslations, 'ar')),
            ]" />

        <x-admin.translatable-field
            label-key="admin.places.form.address_locale"
            id-prefix="place_address"
            :help="__('admin.places.form.address_help')"
            :names="['en' => 'address[en]', 'ar' => 'address[ar]']"
            :values="[
                'en' => old('address.en', data_get($addressTranslations, 'en')),
                'ar' => old('address.ar', data_get($addressTranslations, 'ar')),
            ]" />

        <div class="form-row">
            <div class="col-12 col-lg-6">
                <div class="form-group">
                    <label for="type">
                        {{ __('admin.places.form.type') }}
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="type"
                            name="type"
                            required
                            @class(['form-control', 'is-invalid' => $errors->has('type')])>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('type', $place->type?->value ?? 'cafe') === $value)>
                                {{ \App\Support\Lang::t('admin.places.types.' . $value, $label) }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>
