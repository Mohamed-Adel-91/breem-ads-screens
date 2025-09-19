<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Places') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Manage venues and see which screens are attached to each location.') }}</p>
            </div>
            <a href="{{ route('admin.places.create', ['lang' => $lang]) }}"
               class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                {{ __('Create place') }}
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-8">
                @include('admin.layouts.alerts')

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Filters') }}</h2>
                    </div>
                    <div class="px-6 py-6">
                        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="lg:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                                <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="{{ __('Search by name or address') }}">
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                                <select id="type" name="type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All types') }} --</option>
                                    @foreach ($types as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ ucfirst(__($label)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center justify-end gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                    {{ __('admin.buttons.filter') }}
                                </button>
                                <a href="{{ route('admin.places.index', ['lang' => $lang]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                    {{ __('admin.buttons.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total places') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('With screens attached') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-indigo-600">{{ $stats['with_screens'] }}</dd>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Type') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Address') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Screens attached') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('admin.table.options') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($places as $place)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500">{{ $loop->iteration + ($places->currentPage() - 1) * $places->perPage() }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $place->getTranslation('name', app()->getLocale()) ?? __('Place #:id', ['id' => $place->id]) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ ucfirst($place->type->value) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $place->getTranslation('address', app()->getLocale()) ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $place->screens_count }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.places.show', ['lang' => $lang, 'place' => $place->id]) }}"
                                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                    {{ __('View') }}
                                                </a>
                                                <a href="{{ route('admin.places.edit', ['lang' => $lang, 'place' => $place->id]) }}"
                                                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                                    {{ __('Edit') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No places found for the selected filters.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">
                        @include('admin.partials.pagination', ['data' => $places])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
