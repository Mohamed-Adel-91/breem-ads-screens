@if ($data->hasPages())
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-500">
            {{ __('admin.pagination.showing') }}
            <span class="font-semibold text-gray-700">{{ $data->firstItem() }}</span>
            {{ __('admin.pagination.to') }}
            <span class="font-semibold text-gray-700">{{ $data->lastItem() }}</span>
            {{ __('admin.pagination.of') }}
            <span class="font-semibold text-gray-700">{{ $data->total() }}</span>
            {{ __('admin.pagination.results') }}
        </div>
        <div class="self-start sm:self-auto">
            {{ $data->onEachSide(1)->links() }}
        </div>
    </div>
@endif
