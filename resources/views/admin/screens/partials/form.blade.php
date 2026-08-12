{{--
    Shared Screen form body. Every input name (`place_id`, `code`, `device_uid`,
    `status`, `last_heartbeat`) is emitted verbatim so the existing
    StoreScreenRequest / UpdateScreenRequest contract is untouched. No code is
    generated here — the value the admin types is the value that is submitted.
--}}
@php
    $placeName = fn ($place) => data_get($place->getTranslations('name'), app()->getLocale())
        ?: __('admin.screens.unnamed_place', ['id' => $place->id]);
@endphp

<div class="card">
    <div class="card-header">
        <h2 class="card-title mb-0">{{ __('admin.screens.form.heading') }}</h2>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="place_id">
                        {{ __('admin.screens.form.place') }}
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    {{-- Native Bootstrap select: the place list is modest, so no Select2 is loaded. --}}
                    <select id="place_id"
                            name="place_id"
                            required
                            @class(['form-control', 'is-invalid' => $errors->has('place_id')])>
                        <option value="">{{ __('admin.screens.form.choose_place') }}</option>
                        @foreach ($places as $place)
                            <option value="{{ $place->id }}"
                                @selected(old('place_id', $screen->place_id) == $place->id)>
                                {{ $placeName($place) }}
                            </option>
                        @endforeach
                    </select>
                    @error('place_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if ($places->isEmpty())
                        <small class="form-text text-muted">{{ __('admin.screens.form.no_places') }}</small>
                    @endif
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="code">
                        {{ __('admin.screens.form.code') }}
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           required
                           dir="ltr"
                           aria-describedby="code_help"
                           value="{{ old('code', $screen->code) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('code')])>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small id="code_help" class="form-text text-muted">{{ __('admin.screens.form.code_help') }}</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="device_uid">{{ __('admin.screens.form.device_uid') }}</label>
                    <input type="text"
                           id="device_uid"
                           name="device_uid"
                           dir="ltr"
                           aria-describedby="device_uid_help"
                           value="{{ old('device_uid', $screen->device_uid) }}"
                           @class(['form-control', 'is-invalid' => $errors->has('device_uid')])>
                    @error('device_uid')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small id="device_uid_help" class="form-text text-muted">
                        {{ __('admin.screens.form.device_uid_help') }}
                    </small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="status">
                        {{ __('admin.screens.form.status') }}
                        <span class="text-danger" aria-hidden="true">*</span>
                    </label>
                    <select id="status"
                            name="status"
                            required
                            @class(['form-control', 'is-invalid' => $errors->has('status')])>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('status', $screen->status?->value ?? 'offline') === $value)>
                                {{ \App\Support\Lang::t('admin.screens.statuses.' . $value, $label) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small id="status_help" class="form-text text-muted">
                        {{ __('admin.screens.form.status_help') }}
                    </small>
                </div>
            </div>

            <div class="col-md-6">
                {{--
                    last_heartbeat is read-only operational evidence: it records
                    when the server accepted a signed heartbeat. It used to be an
                    editable datetime-local input, which let an administrator
                    forge connectivity freshness and blanked a live screen's
                    heartbeat whenever the field was left empty.
                --}}
                <div class="form-group">
                    <label>{{ __('admin.screens.form.last_heartbeat') }}</label>
                    <p class="form-control-static mb-0" dir="ltr">
                        {{ optional($screen->last_heartbeat)->format('Y-m-d H:i')
                            ?? __('admin.screens.form.last_heartbeat_never') }}
                    </p>
                    <small class="form-text text-muted">
                        {{ __('admin.screens.form.last_heartbeat_help') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
