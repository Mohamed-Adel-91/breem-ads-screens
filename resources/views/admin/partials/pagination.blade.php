@php
    $variant = $variant ?? 'legacy';
    $paginator = $data;
@endphp

@if ($paginator->hasPages())
    @if ($variant === 'static')
        <div class="admin-pagination">
            {{--
                Visible counts are localized; the paginator's own query parameter is not
                touched — `?page=2` stays ASCII, which is why only the summary text and
                the link labels go through localized_digits().
            --}}
            <p class="admin-pagination-summary mb-0">
                {{ __('admin.pagination.showing') }}
                <strong>{{ localized_digits($paginator->firstItem()) }}</strong>
                {{ __('admin.pagination.to') }}
                <strong>{{ localized_digits($paginator->lastItem()) }}</strong>
                {{ __('admin.pagination.of') }}
                <strong>{{ localized_digits($paginator->total()) }}</strong>
                {{ __('admin.pagination.results') }}
            </p>
            <div class="admin-pagination-links">
                {{ $paginator
                    ->appends(request()->except($paginator->getPageName()))
                    ->onEachSide(1)
                    ->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @else
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                {{ __('admin.pagination.showing') }}
                <span class="font-semibold text-gray-700">{{ localized_digits($paginator->firstItem()) }}</span>
                {{ __('admin.pagination.to') }}
                <span class="font-semibold text-gray-700">{{ localized_digits($paginator->lastItem()) }}</span>
                {{ __('admin.pagination.of') }}
                <span class="font-semibold text-gray-700">{{ localized_digits($paginator->total()) }}</span>
                {{ __('admin.pagination.results') }}
            </div>
            <div class="self-start sm:self-auto">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        </div>
    @endif
@endif
