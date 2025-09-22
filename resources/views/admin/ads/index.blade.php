<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Ads') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Review, filter, and manage advertising creatives across all screens.') }}</p>
            </div>
            @can('ads.create')
                <a href="{{ route('admin.ads.create', ['lang' => $lang]) }}"
                   class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    {{ __('admin.buttons.new') }}
                </a>
            @endcan
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
                        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                            <div class="lg:col-span-2">
                                <label for="search" class="block text-sm font-medium text-gray-700">{{ __('Search') }}</label>
                                <input id="search" name="search" type="text" value="{{ $filters['search'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                       placeholder="{{ __('Search by title or ID') }}">
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
                            <div class="lg:col-span-2">
                                <label for="screen_id" class="block text-sm font-medium text-gray-700">{{ __('Screen') }}</label>
                                <select id="screen_id" name="screen_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- {{ __('All screens') }} --</option>
                                    @foreach ($screens as $screen)
                                        <option value="{{ $screen->id }}" @selected(($filters['screen_id'] ?? '') == $screen->id)>
                                            {{ $screen->code }}
                                            @if ($screen->place)
                                                — {{ $screen->place->getTranslation('name', app()->getLocale()) }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">{{ __('From date') }}</label>
                                <input id="from_date" name="from_date" type="date" value="{{ $filters['from_date'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">{{ __('To date') }}</label>
                                <input id="to_date" name="to_date" type="date" value="{{ $filters['to_date'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div class="sm:col-span-2 lg:col-span-6 flex flex-wrap items-center justify-end gap-3 pt-2">
                                <button type="submit"
                                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                    {{ __('admin.buttons.filter') }}
                                </button>
                                <a href="{{ route('admin.ads.index', ['lang' => $lang]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                    {{ __('admin.buttons.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Total ads') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-gray-900">{{ $stats['total'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Active') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-emerald-600">{{ $stats['active'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Pending') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-amber-600">{{ $stats['pending'] }}</dd>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Expired') }}</dt>
                        <dd class="mt-2 text-2xl font-semibold text-rose-600">{{ $stats['expired'] }}</dd>
                    </div>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left">#</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('Title') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('Status') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('Screens') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('Schedules') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('Start date') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('End date') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('Owner') }}</th>
                                    <th scope="col" class="px-4 py-3 text-left">{{ __('admin.table.options') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse ($ads as $ad)
                                    <tr>
                                        <td class="px-4 py-3 align-top text-gray-500">
                                            {{ $loop->iteration + ($ads->currentPage() - 1) * $ads->perPage() }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-gray-900">
                                                {{ $ad->getTranslation('title', app()->getLocale()) ?? __('(No title)') }}
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">{{ __('ID:') }} {{ $ad->id }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                                {{ ucfirst($ad->status->value) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">{{ $ad->screens->count() }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $ad->schedules->count() }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($ad->start_date)->format('Y-m-d') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($ad->end_date)->format('Y-m-d') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ optional($ad->creator)->name ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-2">
                                                @can('ads.view')
                                                    <a href="{{ route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]) }}"
                                                       class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                                        {{ __('View') }}
                                                    </a>
                                                @endcan
                                                @can('ads.edit')
                                                    <a href="{{ route('admin.ads.edit', ['lang' => $lang, 'ad' => $ad->id]) }}"
                                                       class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                                        {{ __('Edit') }}
                                                    </a>
                                                @endcan
                                                @can('ads.delete')
                                                    <form method="POST" action="{{ route('admin.ads.destroy', ['lang' => $lang, 'ad' => $ad->id]) }}"
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
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No ads found for the current filters.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-gray-200 px-6 py-4">
                        @include('admin.partials.pagination', ['data' => $ads])
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
