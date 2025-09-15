@if ($data->hasPages())
    <div>
        <div class="d-flex p-4 justify-content-center">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    {{-- Previous Page Link --}}
                    @if ($data->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">@lang('pagination.previous')</span></li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $data->previousPageUrl() }}" rel="prev">@lang('pagination.previous')</a>
                        </li>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $data->currentPage())
                                    <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($data->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $data->nextPageUrl() }}" rel="next">@lang('pagination.next')</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">@lang('pagination.next')</span></li>
                    @endif
                </ul>
            </nav>
        </div>
        <div class="d-flex justify-content-center">
            <p class="small text-muted">
                {{ __('admin.pagination.showing') }} <span class="fw-semibold">{{ $data->firstItem() }}</span>
                {{ __('admin.pagination.to') }} <span class="fw-semibold">{{ $data->lastItem() }}</span>
                {{ __('admin.pagination.of') }} <span class="fw-semibold">{{ $data->total() }}</span>
                {{ __('admin.pagination.results') }}
            </p>
        </div>
    </div>
@endif

