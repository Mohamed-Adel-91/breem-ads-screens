<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Name (English)') }}</label>
        <input type="text" name="name[en]" class="form-control" value="{{ old('name.en', data_get($place->getTranslations('name'), 'en')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Name (Arabic)') }}</label>
        <input type="text" name="name[ar]" class="form-control" value="{{ old('name.ar', data_get($place->getTranslations('name'), 'ar')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Address (English)') }}</label>
        <input type="text" name="address[en]" class="form-control" value="{{ old('address.en', data_get($place->getTranslations('address'), 'en')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Address (Arabic)') }}</label>
        <input type="text" name="address[ar]" class="form-control" value="{{ old('address.ar', data_get($place->getTranslations('address'), 'ar')) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Type') }}</label>
        <select name="type" class="form-control">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $place->type?->value ?? 'cafe') === $value)>{{ ucfirst(__($label)) }}</option>
            @endforeach
        </select>
    </div>
</div>
