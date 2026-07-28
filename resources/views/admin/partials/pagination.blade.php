@php
    $variant = $variant ?? 'legacy';
    $paginator = $data;
@endphp

@if ($paginator->hasPages())
    @if ($variant === 'static')
        <div class="admin-pagination">
            <p class="admin-pagination-summary mb-0">
                {{ __('admin.pagination.showing') }}
                <strong>{{ $paginator->firstItem() }}</strong>
                {{ __('admin.pagination.to') }}
                <strong>{{ $paginator->lastItem() }}</strong>
                {{ __('admin.pagination.of') }}
                <strong>{{ $paginator->total() }}</strong>
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
                <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
                {{ __('admin.pagination.to') }}
                <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
                {{ __('admin.pagination.of') }}
                <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
                {{ __('admin.pagination.results') }}
            </div>
            <div class="self-start sm:self-auto">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        </div>
    @endif
@endif
