<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Code') }}</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $screen->code) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Device UID') }}</label>
        <input type="text" name="device_uid" class="form-control" value="{{ old('device_uid', $screen->device_uid) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Place') }}</label>
        <select name="place_id" class="form-control">
            @foreach ($places as $place)
                <option value="{{ $place->id }}" @selected(old('place_id', $screen->place_id) == $place->id)>
                    {{ $place->getTranslation('name', app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-control">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', optional($screen->status)->value ?? 'offline') === $value)>{{ ucfirst($label) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Last heartbeat') }}</label>
        <input type="datetime-local" name="last_heartbeat" class="form-control" value="{{ old('last_heartbeat', optional($screen->last_heartbeat)->format('Y-m-d\\TH:i')) }}">
    </div>
</div>
