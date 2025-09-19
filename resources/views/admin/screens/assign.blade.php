<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Assign ads to screen') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ $screen->code }} — {{ $screen->place?->getTranslation('name', app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place_id]) }}</p>
            </div>
            <a href="{{ route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}"
               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                {{ __('Back to screen') }}
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
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Ad assignments') }}</h2>
                        @unless($assignAction)
                            <p class="mt-1 text-sm text-gray-500">{{ __('No assignment endpoint was provided. The form is displayed in read-only mode.') }}</p>
                        @endunless
                    </div>
                    <form method="POST" action="{{ $assignAction ?? '#' }}" class="px-6 py-6 {{ $assignAction ? '' : 'pointer-events-none opacity-60' }}">
                        @csrf
                        @if (strtoupper($assignMethod) !== 'POST')
                            @method($assignMethod)
                        @endif
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="ad_ids" class="block text-sm font-medium text-gray-700">{{ __('Available ads') }}</label>
                                <select id="ad_ids" name="ads[]" multiple size="8"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($availableAds as $ad)
                                        <option value="{{ $ad->id }}" @selected($screen->ads->contains('id', $ad->id))>
                                            {{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-xs text-gray-500">{{ __('Select the ads that should rotate on this screen.') }}</p>
                            </div>
                            <div class="rounded-lg border border-dashed border-gray-200 p-4">
                                <h3 class="text-sm font-semibold text-gray-700">{{ __('Playback priority') }}</h3>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Adjust the play order for each assigned ad. Lower numbers play first.') }}</p>
                            </div>
                        </div>

                        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Ad') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Play order') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach ($screen->ads as $ad)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-900">{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ ucfirst($ad->status->value ?? '-') }}</td>
                                            <td class="px-4 py-3">
                                                <input type="number" min="0" name="play_order[{{ $ad->id }}]" value="{{ old('play_order.' . $ad->id, $ad->pivot->play_order) }}"
                                                       class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            </td>
                                        </tr>
                                    @endforeach
                                    @if ($screen->ads->isEmpty())
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No ads are currently assigned to this screen.') }}</td>
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
