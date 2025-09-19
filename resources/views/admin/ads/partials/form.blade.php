<div class="space-y-10">
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="title_en" class="block text-sm font-medium text-gray-700">{{ __('Title (English)') }}</label>
            <input id="title_en" type="text" name="title[en]"
                   value="{{ old('title.en', $ad->getTranslation('title', 'en', false)) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('title.en')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="title_ar" class="block text-sm font-medium text-gray-700">{{ __('Title (Arabic)') }}</label>
            <input id="title_ar" type="text" name="title[ar]"
                   value="{{ old('title.ar', $ad->getTranslation('title', 'ar', false)) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('title.ar')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="description_en" class="block text-sm font-medium text-gray-700">{{ __('Description (English)') }}</label>
            <textarea id="description_en" name="description[en]" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description.en', $ad->getTranslation('description', 'en', false)) }}</textarea>
            @error('description.en')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="description_ar" class="block text-sm font-medium text-gray-700">{{ __('Description (Arabic)') }}</label>
            <textarea id="description_ar" name="description[ar]" rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description.ar', $ad->getTranslation('description', 'ar', false)) }}</textarea>
            @error('description.ar')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="creative" class="block text-sm font-medium text-gray-700">{{ __('Creative file') }}</label>
            <input id="creative" type="file" name="creative"
                   class="mt-1 block w-full cursor-pointer rounded-md border border-dashed border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-2 text-xs text-gray-500">{{ __('Supported images, GIFs, or videos. Leave empty to keep the current creative.') }}</p>
            @if ($ad->file_path)
                <p class="mt-2 text-sm">
                    <a href="{{ $ad->file_url }}" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                        {{ __('View current file') }}
                    </a>
                </p>
            @endif
            @error('creative')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label for="duration_seconds" class="block text-sm font-medium text-gray-700">{{ __('Duration (seconds)') }}</label>
                <input id="duration_seconds" type="number" name="duration_seconds" min="0"
                       value="{{ old('duration_seconds', $ad->duration_seconds) }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('duration_seconds')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                <select id="status" name="status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', optional($ad->status)->value ?? array_key_first($statuses)) === $value)>
                            {{ ucfirst(__($label)) }}
                        </option>
                    @endforeach
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <label for="created_by" class="block text-sm font-medium text-gray-700">{{ __('Owner') }}</label>
            <select id="created_by" name="created_by"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}" @selected(old('created_by', $ad->created_by) == $owner->id)>{{ $owner->name }}</option>
                @endforeach
            </select>
            @error('created_by')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="approved_by" class="block text-sm font-medium text-gray-700">{{ __('Approved by (optional)') }}</label>
            <select id="approved_by" name="approved_by"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">-- {{ __('Not set') }} --</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner->id }}" @selected(old('approved_by', $ad->approved_by) == $owner->id)>{{ $owner->name }}</option>
                @endforeach
            </select>
            @error('approved_by')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-3">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">{{ __('Start date') }}</label>
            <input id="start_date" type="date" name="start_date"
                   value="{{ old('start_date', optional($ad->start_date)->format('Y-m-d')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('start_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">{{ __('End date') }}</label>
            <input id="end_date" type="date" name="end_date"
                   value="{{ old('end_date', optional($ad->end_date)->format('Y-m-d')) }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @error('end_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="sm:col-span-1">
            @php($selectedScreens = collect(old('screens', $ad->screens?->pluck('id')->all() ?? [])))
            <label for="screens" class="block text-sm font-medium text-gray-700">{{ __('Screens') }}</label>
            <select id="screens" name="screens[]" multiple size="6"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($screens as $screen)
                    <option value="{{ $screen->id }}" @selected($selectedScreens->contains($screen->id))>
                        {{ $screen->code }}
                        @if ($screen->place)
                            — {{ $screen->place->getTranslation('name', app()->getLocale()) }}
                        @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-xs text-gray-500">{{ __('Hold CTRL (or CMD on macOS) to select multiple screens.') }}</p>
            @error('screens')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Playback order per screen') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('Optional: set the play order for each attached screen. Lower numbers play earlier.') }}</p>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($screens as $screen)
                @php($pivot = $ad->screens?->firstWhere('id', $screen->id))
                <div class="rounded-lg border border-dashed border-gray-200 p-4">
                    <label for="play_order_{{ $screen->id }}" class="block text-sm font-medium text-gray-700">{{ $screen->code }}</label>
                    <input id="play_order_{{ $screen->id }}" type="number" min="0" name="play_order[{{ $screen->id }}]"
                           value="{{ old('play_order.' . $screen->id, optional($pivot?->pivot)->play_order ?? 0) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            @endforeach
        </div>
    </div>
</div>
