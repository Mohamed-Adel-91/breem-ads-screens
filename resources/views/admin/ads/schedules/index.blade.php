<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Manage schedules for') }} {{ $ad->getTranslation('title', app()->getLocale()) ?? '#' . $ad->id }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Review active slots, add new windows, and resolve timing conflicts across screens.') }}</p>
            </div>
            @can('ads.view')
                <a href="{{ route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]) }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('Back to ad') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @include('admin.layouts.alerts')

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Filter schedules') }}</h2>
                    </div>
                    <div class="px-6 py-6">
                        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <div class="lg:col-span-2">
                                <label for="screen_id" class="block text-sm font-medium text-gray-700">{{ __('Screen') }}</label>
                                <select id="screen_id" name="screen_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All screens') }} --</option>
                                    @foreach ($availableScreens as $screen)
                                        <option value="{{ $screen->id }}" @selected(($filters['screen_id'] ?? '') == $screen->id)>{{ $screen->code }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="is_active" class="block text-sm font-medium text-gray-700">{{ __('Active') }}</label>
                                <select id="is_active" name="is_active"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All') }} --</option>
                                    <option value="1" @selected(($filters['is_active'] ?? '') === '1')>{{ __('Yes') }}</option>
                                    <option value="0" @selected(($filters['is_active'] ?? '') === '0')>{{ __('No') }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">{{ __('From date') }}</label>
                                <input id="from_date" type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">{{ __('To date') }}</label>
                                <input id="to_date" type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-end gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                    {{ __('admin.buttons.filter') }}
                                </button>
                                <a href="{{ route('admin.ads.schedules.index', ['lang' => $lang, 'ad' => $ad->id]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                    {{ __('admin.buttons.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total schedules') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Active') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['active'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Inactive') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['inactive'] }}</dd>
                    </div>
                </div>

                @can('ads.schedule')
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Create new schedule') }}</h2>
                        </div>
                        <div class="px-6 py-6">
                            <form method="POST" action="{{ route('admin.ads.schedules.store', ['lang' => $lang, 'ad' => $ad->id]) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                @csrf
                                <div class="lg:col-span-2">
                                    <label for="create_screen_id" class="block text-sm font-medium text-gray-700">{{ __('Screen') }}</label>
                                    <select id="create_screen_id" name="screen_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach ($availableScreens as $screen)
                                            <option value="{{ $screen->id }}">{{ $screen->code }}</option>
                                        @endforeach
                                    </select>
                                    @error('screen_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="create_start_time" class="block text-sm font-medium text-gray-700">{{ __('Start time') }}</label>
                                    <input id="create_start_time" type="datetime-local" name="start_time" value="{{ old('start_time') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('start_time')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="create_end_time" class="block text-sm font-medium text-gray-700">{{ __('End time') }}</label>
                                    <input id="create_end_time" type="datetime-local" name="end_time" value="{{ old('end_time') }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @error('end_time')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex items-center gap-2 pt-6">
                                    <input id="create_is_active" type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" checked>
                                    <label for="create_is_active" class="text-sm font-medium text-gray-700">{{ __('Active') }}</label>
                                </div>
                                <div class="sm:col-span-2 lg:col-span-5 flex justify-end">
                                    <button type="submit"
                                            class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                                        {{ __('Add schedule') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">{{ __('Screen') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Start time') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('End time') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Active') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($schedules as $schedule)
                                    <tr x-data="{ open: false }" class="align-top">
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">{{ $schedule->screen?->code ?? __('Screen removed') }}</div>
                                            @if ($schedule->screen?->place)
                                                <div class="mt-1 text-xs text-gray-500">{{ $schedule->screen->place->getTranslation('name', app()->getLocale()) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $schedule->start_time->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $schedule->end_time->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3">
                                            @if ($schedule->is_active)
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('Yes') }}</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ __('No') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                @can('ads.schedule')
                                                    <button type="button" @click="open = !open"
                                                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                        {{ __('Edit') }}
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.ads.schedules.destroy', ['lang' => $lang, 'ad' => $ad->id, 'schedule' => $schedule->id]) }}"
                                                          onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="inline-flex items-center rounded-md bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-rose-500">
                                                            {{ __('Delete') }}
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                    @can('ads.schedule')
                                        <tr x-show="open" x-cloak>
                                            <td colspan="5" class="bg-gray-50 px-4 py-4">
                                                <form method="POST" action="{{ route('admin.ads.schedules.update', ['lang' => $lang, 'ad' => $ad->id, 'schedule' => $schedule->id]) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="lg:col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700" for="schedule_screen_{{ $schedule->id }}">{{ __('Screen') }}</label>
                                                        <select id="schedule_screen_{{ $schedule->id }}" name="screen_id"
                                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                            @foreach ($availableScreens as $screen)
                                                                <option value="{{ $screen->id }}" @selected($screen->id == $schedule->screen_id)>{{ $screen->code }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700" for="schedule_start_{{ $schedule->id }}">{{ __('Start time') }}</label>
                                                        <input id="schedule_start_{{ $schedule->id }}" type="datetime-local" name="start_time"
                                                               value="{{ $schedule->start_time->format('Y-m-d\TH:i') }}"
                                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700" for="schedule_end_{{ $schedule->id }}">{{ __('End time') }}</label>
                                                        <input id="schedule_end_{{ $schedule->id }}" type="datetime-local" name="end_time"
                                                               value="{{ $schedule->end_time->format('Y-m-d\TH:i') }}"
                                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </div>
                                                    <div class="flex items-center gap-2 pt-6">
                                                        <input id="schedule_active_{{ $schedule->id }}" type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($schedule->is_active)>
                                                        <label for="schedule_active_{{ $schedule->id }}" class="text-sm font-medium text-gray-700">{{ __('Active') }}</label>
                                                    </div>
                                                    <div class="sm:col-span-2 lg:col-span-5 flex justify-end">
                                                        <button type="submit"
                                                                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                                            {{ __('Save changes') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    @endcan
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No schedules found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">
                        @include('admin.partials.pagination', ['data' => $schedules])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
