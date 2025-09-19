<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Reports') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Generate insights from playback logs and uptime records.') }}</p>
            </div>
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
                                       placeholder="{{ __('Search by name') }}">
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                                <select id="type" name="type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All types') }} --</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst(str_replace('-', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center justify-end gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                    {{ __('admin.buttons.filter') }}
                                </button>
                                <a href="{{ route('admin.reports.index', ['lang' => $lang]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                                    {{ __('admin.buttons.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Generate new report') }}</h2>
                    </div>
                    <div class="px-6 py-6">
                        <form method="POST" action="{{ route('admin.reports.generate', ['lang' => $lang]) }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            @csrf
                            <div class="lg:col-span-2">
                                <label for="name" class="block text-sm font-medium text-gray-700">{{ __('Report name') }}</label>
                                <input id="name" type="text" name="name" value="{{ old('name') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="{{ __('Enter a descriptive name') }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="type_select" class="block text-sm font-medium text-gray-700">{{ __('Report type') }}</label>
                                <select id="type_select" name="type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    @foreach ($types as $type)
                                        <option value="{{ $type }}" @selected(old('type', $types[0] ?? '') === $type)>{{ ucfirst(str_replace('-', ' ', $type)) }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="screen_id" class="block text-sm font-medium text-gray-700">{{ __('Screen (optional)') }}</label>
                                <select id="screen_id" name="screen_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All screens') }} --</option>
                                    @foreach ($screens as $screen)
                                        <option value="{{ $screen->id }}" @selected(old('screen_id') == $screen->id)>
                                            {{ $screen->code }}
                                            @if ($screen->place)
                                                — {{ data_get($screen->place->getTranslations('name'), app()->getLocale()) ?? __('Place #:id', ['id' => $screen->place->id]) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('screen_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="ad_id" class="block text-sm font-medium text-gray-700">{{ __('Ad (optional)') }}</label>
                                <select id="ad_id" name="ad_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All ads') }} --</option>
                                    @foreach ($ads as $ad)
                                        <option value="{{ $ad->id }}" @selected(old('ad_id') == $ad->id)>{{ data_get($ad->getTranslations('title'), app()->getLocale()) ?? __('Ad #:id', ['id' => $ad->id]) }}</option>
                                    @endforeach
                                </select>
                                @error('ad_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">{{ __('From date') }}</label>
                                <input id="from_date" type="date" name="from_date" value="{{ old('from_date') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('from_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">{{ __('To date') }}</label>
                                <input id="to_date" type="date" name="to_date" value="{{ old('to_date') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('to_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                                    {{ __('Generate report') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ __('Existing reports') }}</h2>
                        <span class="text-sm text-gray-500">{{ $reports->total() }} {{ __('entries') }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">#</th>
                                    <th class="px-4 py-3 text-left">{{ __('Name') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Type') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Generated by') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('Created at') }}</th>
                                    <th class="px-4 py-3 text-left">{{ __('admin.table.options') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($reports as $report)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-500">{{ $loop->iteration + ($reports->currentPage() - 1) * $reports->perPage() }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $report->name }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ ucfirst(str_replace('-', ' ', $report->type)) }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($report->generator)->name ?? __('System') }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($report->created_at)->format('Y-m-d H:i') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                <a href="{{ route('admin.reports.show', ['lang' => $lang, 'report' => $report->id]) }}"
                                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                    {{ __('View') }}
                                                </a>
                                                <a href="{{ route('admin.reports.download', ['lang' => $lang, 'report' => $report->id]) }}"
                                                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                                    {{ __('Download') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No reports generated yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">
                        @include('admin.partials.pagination', ['data' => $reports])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
