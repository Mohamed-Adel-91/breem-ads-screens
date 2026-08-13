{{--
    Laravel's bootstrap-4 paginator, published for ONE reason: the page number a visitor
    reads should be in their locale's digits, while the URL behind it must stay ASCII.

    Arabic renders the label ٢ on a link whose href is still `?page=2`. Localizing $url
    would break paging outright, so `$page` — display text — goes through
    localized_digits() and `$url` is never touched. Same for the `…` separator and the
    ‹ › arrows, which carry no digits.

    Everything else is the framework's markup, unchanged, so the Bootstrap 4 admin theme
    keeps styling it. Re-publish from
    vendor/laravel/framework/src/Illuminate/Pagination/resources/views/bootstrap-4.blade.php
    if a Laravel upgrade changes it, and reapply only the two localized_digits() calls.

    Selected by admin/partials/pagination.blade.php (explicitly) and by
    Paginator::useBootstrap() in AppServiceProvider (as the default view).
--}}
@if ($paginator->hasPages())
    <nav>
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ localized_digits($page) }}</span></li>
                        @else
                            {{-- href keeps the ASCII page number; only the label is localized. --}}
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ localized_digits($page) }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
