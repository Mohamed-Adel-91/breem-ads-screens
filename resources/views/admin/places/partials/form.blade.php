<div class="space-y-8">
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="name_en" class="block text-sm font-medium text-gray-700">{{ __('Name (English)') }}</label>
            <input id="name_en" type="text" name="name[en]" value="{{ old('name.en', data_get($place->getTranslations('name'), 'en')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name.en')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="name_ar" class="block text-sm font-medium text-gray-700">{{ __('Name (Arabic)') }}</label>
            <input id="name_ar" type="text" name="name[ar]" value="{{ old('name.ar', data_get($place->getTranslations('name'), 'ar')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('name.ar')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="address_en" class="block text-sm font-medium text-gray-700">{{ __('Address (English)') }}</label>
            <input id="address_en" type="text" name="address[en]" value="{{ old('address.en', data_get($place->getTranslations('address'), 'en')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('address.en')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="address_ar" class="block text-sm font-medium text-gray-700">{{ __('Address (Arabic)') }}</label>
            <input id="address_ar" type="text" name="address[ar]" value="{{ old('address.ar', data_get($place->getTranslations('address'), 'ar')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('address.ar')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="type" class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
        <select id="type" name="type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:max-w-xs">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $place->type?->value ?? 'cafe') === $value)>{{ ucfirst(__($label)) }}</option>
            @endforeach
        </select>
        @error('type')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
