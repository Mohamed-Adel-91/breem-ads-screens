<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Monitoring') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Track live screen status, spot alerts, and drill into problem devices.') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @include('admin.layouts.alerts')

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Filters') }}</h2>
                    </div>
                    <div class="px-6 py-6">
                        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <div class="lg:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                                <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="{{ __('Search by code or device UID') }}">
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                                <select id="status" name="status"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All statuses') }} --</option>
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ ucfirst(__($label)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="place_id" class="block text-sm font-medium text-gray-700">{{ __('Place') }}</label>
                                <select id="place_id" name="place_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All places') }} --</option>
                                    @foreach ($places as $place)
                                        <option value="{{ $place->id }}" @selected(($filters['place_id'] ?? '') == $place->id)>
                                            {{ data_get($place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-2 pt-6">
                                <input id="has_alerts" type="checkbox" name="has_alerts" value="1" @checked(($filters['has_alerts'] ?? false))
                                       class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <label for="has_alerts" class="text-sm font-medium text-gray-700">{{ __('Show screens with alerts only') }}</label>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-end gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                    {{ __('admin.buttons.filter') }}
                                </button>
                                <a href="{{ route('admin.monitoring.index', ['lang' => $lang]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                    {{ __('admin.buttons.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($summary as $status => $count)
                        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                            <dt class="text-sm font-medium text-gray-500">{{ ucfirst($status) }}</dt>
                            <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ $count }}</dd>
                        </div>
                    @endforeach
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">{{ __('Code') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Place') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Last report') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Offline logs (24h)') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Active schedules') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('admin.table.options') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($screens as $screen)
                                    @php $latestLog = $screen->logs->first(); @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500">{{ $loop->iteration + ($screens->currentPage() - 1) * $screens->perPage() }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $screen->code }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            @if ($screen->place)
                                                {{ data_get($screen->place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place->id]) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ ucfirst($screen->status->value ?? '-') }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            @if ($latestLog)
                                                <div class="text-sm font-medium text-gray-900">{{ $latestLog->status->value ?? '-' }}</div>
                                                <div class="text-xs text-gray-500">{{ optional($latestLog->reported_at)->format('Y-m-d H:i') }}</div>
                                            @else
                                                <span class="text-sm text-gray-500">{{ __('No logs') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $screen->offline_logs_count }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $screen->active_schedule_count }}</td>
                                        <td class="px-4 py-3">
                                            @can('monitoring.view')
                                                <a href="{{ route('admin.monitoring.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}"
                                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                    {{ __('View') }}
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No screens found for the selected filters.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">
                        @include('admin.partials.pagination', ['data' => $screens])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
