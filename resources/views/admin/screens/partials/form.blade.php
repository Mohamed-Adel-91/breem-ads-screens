<div class="space-y-8">
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="place_id" class="block text-sm font-medium text-gray-700">{{ __('Place') }}</label>
            <select id="place_id" name="place_id"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($places as $place)
                    <option value="{{ $place->id }}" @selected(old('place_id', $screen->place_id) == $place->id)>
                        {{ $place->getTranslation('name', app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}
                    </option>
                @endforeach
            </select>
            @error('place_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="code" class="block text-sm font-medium text-gray-700">{{ __('Code') }}</label>
            <input id="code" type="text" name="code" value="{{ old('code', $screen->code) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('code')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="device_uid" class="block text-sm font-medium text-gray-700">{{ __('Device UID') }}</label>
            <input id="device_uid" type="text" name="device_uid" value="{{ old('device_uid', $screen->device_uid) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('device_uid')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
            <select id="status" name="status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $screen->status?->value ?? 'offline') === $value)>{{ ucfirst(__($label)) }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="last_heartbeat" class="block text-sm font-medium text-gray-700">{{ __('Last heartbeat') }}</label>
            <input id="last_heartbeat" type="datetime-local" name="last_heartbeat"
                   value="{{ old('last_heartbeat', optional($screen->last_heartbeat)->format('Y-m-d\TH:i')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('last_heartbeat')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
