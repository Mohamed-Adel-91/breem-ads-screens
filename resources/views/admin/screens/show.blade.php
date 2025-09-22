<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $screen->code }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Detailed telemetry and assignments for this screen.') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can('screens.edit')
                    <a href="{{ route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id]) }}"
                       class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                        {{ __('Edit screen') }}
                    </a>
                @endcan
                @can('screens.view')
                    <a href="{{ route('admin.screens.index', ['lang' => $lang]) }}"
                       class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        {{ __('Back to list') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @include('admin.layouts.alerts')

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Overview') }}</h2>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Place') }}</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $screen->place?->getTranslation('name', app()->getLocale()) ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ ucfirst($screen->status->value) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Device UID') }}</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $screen->device_uid ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Last heartbeat') }}</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Active schedules') }}</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $screen->schedules->where('is_active', true)->count() }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">{{ __('Linked ads') }}</dt>
                                <dd class="mt-1 text-base text-gray-900">{{ $screen->ads->count() }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Uptime (last 7 days)') }}</h2>
                        <div class="mt-4">
                            @if (!is_null($uptime))
                                <div class="text-4xl font-semibold text-emerald-600">{{ $uptime }}%</div>
                                <p class="mt-2 text-sm text-gray-500">{{ __('Percentage of logs reporting this screen online in the last week.') }}</p>
                            @else
                                <p class="text-sm text-gray-500">{{ __('Not enough data to calculate uptime.') }}</p>
                            @endif
                        </div>
                        <dl class="mt-6 space-y-3 text-sm text-gray-700">
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Online events') }}</dt>
                                <dd class="font-semibold">{{ $logSummary['online'] ?? 0 }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt>{{ __('Offline events') }}</dt>
                                <dd class="font-semibold">{{ $logSummary['offline'] ?? 0 }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Linked ads') }}</h2>
                            <span class="text-sm text-gray-500">{{ __('Total:') }} {{ $screen->ads->count() }}</span>
                        </div>
                        <ul class="mt-4 divide-y divide-gray-200">
                            @forelse ($screen->ads as $ad)
                                <li class="py-3">
                                    <div class="font-medium text-gray-900">{{ $ad->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ __('Play order') }}: {{ $ad->pivot->play_order }}</div>
                                </li>
                            @empty
                                <li class="py-3 text-sm text-gray-500">{{ __('No ads linked to this screen.') }}</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Active schedules') }}</h2>
                        <ul class="mt-4 divide-y divide-gray-200">
                            @forelse ($screen->schedules->sortBy('start_time') as $schedule)
                                <li class="py-3">
                                    <div class="font-medium text-gray-900">{{ $schedule->ad?->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $schedule->ad_id]) }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ $schedule->start_time->format('Y-m-d H:i') }} → {{ $schedule->end_time->format('Y-m-d H:i') }}</div>
                                    <div class="mt-1 text-xs font-medium {{ $schedule->is_active ? 'text-emerald-600' : 'text-gray-500' }}">
                                        {{ $schedule->is_active ? __('Active') : __('Inactive') }}
                                    </div>
                                </li>
                            @empty
                                <li class="py-3 text-sm text-gray-500">{{ __('No schedules configured.') }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white shadow">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Recent logs') }}</h2>
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
                                            <td class="px-4 py-3 text-gray-700">{{ ucfirst($log->status->value) }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $log->reported_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No logs recorded yet.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-gray-200 px-6 py-4">
                            @include('admin.partials.pagination', ['data' => $recentLogs])
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white shadow lg:col-span-1">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">{{ __('Recent playbacks') }}</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <tr>
                                        <th class="px-4 py-3 text-left">{{ __('Ad') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Played at') }}</th>
                                        <th class="px-4 py-3 text-left">{{ __('Duration') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($recentPlaybacks as $log)
                                        <tr>
                                            <td class="px-4 py-3 text-gray-700">{{ $log->ad?->getTranslation('title', app()->getLocale()) ?? __('Ad #:id', ['id' => $log->ad_id]) }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ optional($log->played_at)->format('Y-m-d H:i') }}</td>
                                            <td class="px-4 py-3 text-gray-700">{{ $log->duration }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No playback events recorded yet.') }}</td>
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
