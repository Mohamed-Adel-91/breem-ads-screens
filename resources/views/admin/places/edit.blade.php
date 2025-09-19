<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Edit place') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Update translations, addresses, or type classifications.') }}</p>
            </div>
            @can('places.view')
                <a href="{{ route('admin.places.show', ['lang' => $lang, 'place' => $place->id]) }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('Back to details') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-6">
                @include('admin.layouts.alerts')

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <form method="POST" action="{{ route('admin.places.update', ['lang' => $lang, 'place' => $place->id]) }}">
                        @csrf
                        @method('PUT')
                        <div class="px-6 py-8">
                            @include('admin.places.partials.form')
                        </div>
                        <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <button type="submit"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                                {{ __('Update place') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
