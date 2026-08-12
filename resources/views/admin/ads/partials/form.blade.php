{{--
    Shared Ad form body. Every input name (`title[en]`, `title[ar]`,
    `description[en]`, `description[ar]`, `creative`, `duration_seconds`, `status`,
    `created_by`, `approved_by`, `start_date`, `end_date`, `screens[]`,
    `play_order[{screen}]`) is emitted verbatim so the existing StoreAdRequest /
    UpdateAdRequest contract is untouched.

    `creativeRequired` mirrors the request rules: required on store, nullable on
    update. The `accept` attribute is a browser hint only — server-side mimetype
    validation is unchanged and is still the authority.
--}}
@php
    $creativeRequired = $creativeRequired ?? false;

    $titleTranslations = $ad->getTranslations('title');
    $descriptionTranslations = $ad->getTranslations('description');

    // Mirrors the `mimetypes:` rule in Store/UpdateAdRequest. Hint only.
    $creativeAccept = 'video/mp4,video/x-m4v,video/quicktime,video/x-msvideo,'
        . 'video/x-ms-wmv,video/mpeg,video/webm,image/jpeg,image/png,image/gif';

    $selectedScreens = collect(old('screens', $ad->screens?->pluck('id')->all() ?? []));

    $screenLabel = function ($screen) {
        $label = $screen->code;

        if ($screen->place) {
            $placeName = data_get($screen->place->getTranslations('name'), app()->getLocale());

            if ($placeName) {
                $label .= ' — ' . $placeName;
            }
        }

        return $label;
    };
@endphp

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.ads.form.content_heading') }}</h2>
    </div>
    <div class="card-body">
        {{-- Arabic inputs stay dir="rtl" and English dir="ltr" regardless of dashboard locale. --}}
        <x-admin.translatable-field
            label-key="admin.ads.form.title_locale"
            id-prefix="ad_title"
            :help="__('admin.ads.form.title_help')"
            :names="['en' => 'title[en]', 'ar' => 'title[ar]']"
            :values="[
                'en' => old('title.en', data_get($titleTranslations, 'en')),
                'ar' => old('title.ar', data_get($titleTranslations, 'ar')),
            ]" />

        <x-admin.translatable-field
            type="textarea"
            :rows="4"
            label-key="admin.ads.form.description_locale"
            id-prefix="ad_description"
            :names="['en' => 'description[en]', 'ar' => 'description[ar]']"
            :values="[
                'en' => old('description.en', data_get($descriptionTranslations, 'en')),
                'ar' => old('description.ar', data_get($descriptionTranslations, 'ar')),
            ]" />
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.ads.form.creative_heading') }}</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="creative">
                        {{ __('admin.ads.form.creative') }}
                        @if ($creativeRequired)
                            <span class="text-danger" aria-hidden="true">*</span>
                        @endif
                    </label>

                    @if ($ad->file_path)
                        <div class="mb-2">
                            @include('admin.ads.partials.creative-preview', [
                                'ad' => $ad,
                                'caption' => __('admin.ads.form.current_creative'),
                            ])
                        </div>
                    @endif

                    <input type="file"
                           id="creative"
                           name="creative"
                           accept="{{ $creativeAccept }}"
                           aria-describedby="creative_help"
                           @if ($creativeRequired) required @endif
                           @class(['form-control-file', 'is-invalid' => $errors->has('creative')])>
                    @error('creative')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <small id="creative_help" class="form-text text-muted">
                        {{ __('admin.ads.form.creative_help') }}
                        @if (!$creativeRequired)
                            {{ __('admin.ads.form.creative_help_edit') }}
                        @endif
                    </small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="duration_seconds">{{ __('admin.ads.form.duration_seconds') }}</label>
                    <input type="number"
                           id="duration_seconds"
                           name="duration_seconds"
                           min="0"
                           dir="ltr"
                           aria-describedby="duration_seconds_help"
                           value="{{ old('duration_seconds', $ad->duration_seconds) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('duration_seconds')])>
                    @error('duration_seconds')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small id="duration_seconds_help" class="form-text text-muted">
                        {{ __('admin.ads.form.duration_help') }}
                    </small>
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="status">{{ __('admin.ads.form.status') }}</label>
                    <select id="status"
                            name="status"
                            @class(['form-control', 'is-invalid' => $errors->has('status')])>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('status', optional($ad->status)->value ?? array_key_first($statuses)) === $value)>
                                {{ \App\Support\Lang::t('admin.ads.statuses.' . $value, $label) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.ads.form.ownership_heading') }}</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="created_by">
                        {{ __('admin.ads.form.owner') }}
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="created_by"
                            name="created_by"
                            required
                            @class(['form-control', 'is-invalid' => $errors->has('created_by')])>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected(old('created_by', $ad->created_by) == $owner->id)>
                                {{ $owner->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('created_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="approved_by">{{ __('admin.ads.form.approved_by') }}</label>
                    <select id="approved_by"
                            name="approved_by"
                            @class(['form-control', 'is-invalid' => $errors->has('approved_by')])>
                        <option value="">{{ __('admin.ads.form.not_set') }}</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected(old('approved_by', $ad->approved_by) == $owner->id)>
                                {{ $owner->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('approved_by')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.ads.form.schedule_heading') }}</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="start_date">{{ __('admin.ads.form.start_date') }}</label>
                    <input type="date"
                           id="start_date"
                           name="start_date"
                           dir="ltr"
                           value="{{ old('start_date', optional($ad->start_date)->format('Y-m-d')) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('start_date')])>
                    @error('start_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="end_date">{{ __('admin.ads.form.end_date') }}</label>
                    <input type="date"
                           id="end_date"
                           name="end_date"
                           dir="ltr"
                           value="{{ old('end_date', optional($ad->end_date)->format('Y-m-d')) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('end_date')])>
                    @error('end_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.ads.form.assignment_heading') }}</h2>
    </div>
    <div class="card-body">
        @if ($screens->isEmpty())
            <p class="text-muted mb-0">{{ __('admin.ads.form.no_screens') }}</p>
        @else
            <div class="row">
                <div class="col-lg-5">
                    <div class="form-group">
                        <label for="screens">{{ __('admin.ads.form.screens') }}</label>
                        {{-- Native multi-select: no Select2 is loaded for this module. --}}
                        <select id="screens"
                                name="screens[]"
                                multiple
                                size="8"
                                aria-describedby="screens_help"
                                @class(['form-control', 'is-invalid' => $errors->has('screens')])>
                            @foreach ($screens as $screen)
                                <option value="{{ $screen->id }}" @selected($selectedScreens->contains($screen->id))>
                                    {{ $screenLabel($screen) }}
                                </option>
                            @endforeach
                        </select>
                        @error('screens')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <small id="screens_help" class="form-text text-muted">
                            {{ __('admin.ads.form.screens_help') }}
                        </small>
                    </div>
                </div>

                <div class="col-lg-7">
                    <h3 class="admin-section-title">{{ __('admin.ads.form.play_order_heading') }}</h3>
                    <p class="text-muted small">{{ __('admin.ads.form.play_order_help') }}</p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('admin.ads.form.screens') }}</th>
                                    <th scope="col">{{ __('admin.ads.show.play_order') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($screens as $screen)
                                    @php($pivot = $ad->screens?->firstWhere('id', $screen->id))
                                    <tr>
                                        <td>
                                            <label class="mb-0" for="play_order_{{ $screen->id }}">
                                                {{ $screenLabel($screen) }}
                                            </label>
                                        </td>
                                        <td>
                                            <input type="number"
                                                   id="play_order_{{ $screen->id }}"
                                                   name="play_order[{{ $screen->id }}]"
                                                   min="0"
                                                   dir="ltr"
                                                   value="{{ old('play_order.' . $screen->id, optional($pivot?->pivot)->play_order ?? 0) }}"
                                                   class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
