<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Screens') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Monitor the deployment status of screens and access quick diagnostics.') }}</p>
            </div>
            <a href="{{ route('admin.screens.create', ['lang' => $lang]) }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('Create screen') }}
            </a>
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
                        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="lg:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                                <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="{{ __('Search code or device UID') }}">
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
                                            {{ $place->getTranslation('name', app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-4 flex flex-wrap items-center justify-end gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                    {{ __('admin.buttons.filter') }}
                                </button>
                                <a href="{{ route('admin.screens.index', ['lang' => $lang]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                    {{ __('admin.buttons.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Online') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['online'] ?? 0 }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Offline') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-rose-600">{{ $stats['offline'] ?? 0 }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Maintenance') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['maintenance'] ?? 0 }}</dd>
                    </div>
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
                                    <th class="px-4 py-3 text-left">{{ __('Active schedules') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Last heartbeat') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('admin.table.options') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($screens as $screen)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500">
                                            {{ $loop->iteration + ($screens->currentPage() - 1) * $screens->perPage() }}
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $screen->code }}</td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ $screen->place?->getTranslation('name', app()->getLocale()) ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">{{ ucfirst($screen->status->value) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $screen->active_schedule_count }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($screen->last_heartbeat)->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.screens.show', ['lang' => $lang, 'screen' => $screen->id]) }}"
                                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                    {{ __('View') }}
                                                </a>
                                                <a href="{{ route('admin.screens.edit', ['lang' => $lang, 'screen' => $screen->id]) }}"
                                                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                                    {{ __('Edit') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No screens match the selected filters.') }}</td>
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
