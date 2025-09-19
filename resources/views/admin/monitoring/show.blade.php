<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Screen monitoring') }} — {{ $screen->code }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ data_get($screen->place?->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place_id]) }}</p>
            </div>
            <a href="{{ route('admin.monitoring.index', ['lang' => $lang]) }}"
               class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                {{ __('Back to monitoring') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @include('admin.layouts.alerts')

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ ucfirst($screen->status->value ?? '-') }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Last heartbeat') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Uptime (7 days)') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-emerald-600">{{ $uptime !== null ? $uptime . '%' : '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Active schedules') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ $screen->schedules->where('is_active', true)->count() }}</dd>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Device UID') }}</dt>
                        <dd class="mt-2 text-base text-gray-900">{{ $screen->device_uid ?? '—' }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total schedules') }}</dt>
                        <dd class="mt-2 text-base text-gray-900">{{ $screen->schedules->count() }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Attached ads') }}</dt>
                        <dd class="mt-2 text-base text-gray-900">{{ $screen->ads->count() }}</dd>
                    </div>
                </div>

                @can('monitoring.manage')
                    <div class="overflow-hidden rounded-lg bg-white shadow">
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Acknowledge alert') }}</h2>
                        </div>
                        <div class="px-6 py-6">
                            <form method="POST" action="{{ route('admin.monitoring.screens.acknowledge', ['lang' => $lang, 'screen' => $screen->id]) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                @csrf
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700">{{ __('Update status to') }}</label>
                                    <select id="status" name="status"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="online" @selected(old('status', 'online') === 'online')>{{ __('Online') }}</option>
                                        <option value="maintenance" @selected(old('status') === 'maintenance')>{{ __('Maintenance') }}</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-3">
                                    <label for="note" class="block text-sm font-medium text-gray-700">{{ __('Note (optional)') }}</label>
                                    <textarea id="note" name="note" rows="1"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                              placeholder="{{ __('Add details about the intervention') }}">{{ old('note') }}</textarea>
                                </div>
                                <div class="flex items-end justify-end">
                                    <button type="submit"
                                            class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                        {{ __('Confirm') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endcan

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white shadow">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Upcoming schedules') }}</h2>
                            <span class="text-sm text-gray-500">{{ __('Total:') }} {{ $screen->schedules->count() }}</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Ad') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Start time') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('End time') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Active') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($screen->schedules as $schedule)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($schedule->ad)->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $schedule->ad_id]) }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($schedule->start_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($schedule->end_time)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td class="px-4 py-3">
                                                @if ($schedule->is_active)
                                                    <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">{{ __('Yes') }}</span>
                                                @else
                                                    <span class="inline-flex rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ __('No') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No schedules configured for this screen.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white shadow">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Attached ads') }}</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Play order') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Ad title') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($screen->ads as $ad)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">{{ $ad->pivot->play_order }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ ucfirst($ad->status->value ?? '-') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No ads are attached to this screen.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white shadow">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Recent monitoring logs') }}</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Reported at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($recentLogs as $log)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">{{ ucfirst($log->status->value ?? '-') }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($log->reported_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No monitoring logs recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-gray-200 px-6 py-4">
                            @include('admin.partials.pagination', ['data' => $recentLogs])
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white shadow">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Recent playbacks') }}</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Ad') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Played at') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Duration (seconds)') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($recentPlaybacks as $playback)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($playback->ad)->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $playback->ad_id]) }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($playback->played_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $playback->duration }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No playback activity recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-gray-200 px-6 py-4">
                            @include('admin.partials.pagination', ['data' => $recentPlaybacks])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
