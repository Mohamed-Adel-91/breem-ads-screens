<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Edit ad') }}</h1>
                <p class="mt-1 text-sm text-gray-500">{{ __('Update metadata, creatives, and default scheduling information for this advertisement.') }}</p>
            </div>
            @can('ads.view')
                <a href="{{ route('admin.ads.show', ['lang' => $lang, 'ad' => $ad->id]) }}"
                   class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    {{ __('Back to details') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-6">
                @include('admin.layouts.alerts')

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <form method="POST" action="{{ route('admin.ads.update', ['lang' => $lang, 'ad' => $ad->id]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="space-y-8 px-6 py-8">
                            @include('admin.ads.partials.form')
                        </div>
                        <div class="flex justify-end border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <button type="submit"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                {{ __('Update ad') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
