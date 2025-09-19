<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Assign screens to ad') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</p>
            </div>
            <a href="{{ route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]) }}"
               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                {{ __('Back to ad') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @include('admin.layouts.alerts')

                @php($assignAction = $assignAction ?? null)
                @php($assignMethod = $assignMethod ?? 'POST')

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Screen assignments') }}</h2>
                        @unless($assignAction)
                            <p class="mt-1 text-sm text-gray-500">{{ __('No assignment endpoint was provided. The form below is displayed in read-only mode.') }}</p>
                        @endunless
                    </div>
                    <form method="POST" action="{{ $assignAction ?? '#' }}" class="px-6 py-6 {{ $assignAction ? '' : 'pointer-events-none opacity-60' }}">
                        @csrf
                        @if (strtoupper($assignMethod) !== 'POST')
                            @method($assignMethod)
                        @endif
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="screen_ids" class="block text-sm font-medium text-gray-700">{{ __('Available screens') }}</label>
                                <select id="screen_ids" name="screens[]" multiple size="8"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($availableScreens as $screen)
                                        <option value="{{ $screen->id }}" @selected($ad->screens->contains('id', $screen->id))>
                                            {{ $screen->code }}
                                            @if ($screen->place)
                                                — {{ $screen->place->getTranslation('name', app()->getLocale()) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-gray-500">{{ __('Select multiple screens to link or unlink them from this ad.') }}</p>
                            </div>
                            <div class="rounded-lg border border-dashed border-gray-200 p-4">
                                <h3 class="text-sm font-semibold text-gray-700">{{ __('Play order guidance') }}</h3>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Use the table below to control playback priority for each assigned screen. Lower numbers play earlier within the loop.') }}</p>
                            </div>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Screen') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Location') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Play order') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($ad->screens as $screen)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900">{{ $screen->code }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $screen->place?->getTranslation('name', app()->getLocale()) ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                <input type="number" min="0" name="play_order[{{ $screen->id }}]" value="{{ old('play_order.' . $screen->id, $screen->pivot->play_order) }}"
                                                       class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($ad->screens->isEmpty())
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No screens are currently assigned.') }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                    @unless($assignAction) disabled @endunless>
                                {{ __('Save assignments') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
