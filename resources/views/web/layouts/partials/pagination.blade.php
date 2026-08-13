@props(['paginator'])

@if ($paginator->hasPages())
    <div class="mt-6 space-y-3" dir="rtl">
        {{-- Summary: "عرض ١–١٢ من ٢٣٤" — the comment finally matches the output. --}}
        @if ($paginator->total() > 0)
            <p class="text-sm text-gray-600">
                عرض {{ localized_digits($paginator->firstItem()) }}–{{ localized_digits($paginator->lastItem()) }} من {{ localized_digits($paginator->total()) }}
            </p>
        @endif

        <nav role="navigation" aria-label="Pagination">
            <ul class="inline-flex items-center gap-1 flex-wrap">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <li>
                        <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                            السابق
                        </span>
                    </li>
                @else
                    <li>
                        <a class="px-3 py-1.5 rounded-lg bg-white border hover:bg-gray-50"
                            href="{{ $paginator->previousPageUrl() }}" rel="prev"
                            aria-label="الصفحة السابقة">السابق</a>
                    </li>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li>
                            <span class="px-3 py-1.5 text-gray-500">…</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            {{-- Labels localized; $url keeps its ASCII ?page= value. --}}
                            @if ($page == $paginator->currentPage())
                                <li>
                                    <span aria-current="page"
                                        class="px-3 py-1.5 rounded-lg text-white bg-[#41A8A6]">{{ localized_digits($page) }}</span>
                                </li>
                            @else
                                <li>
                                    <a class="px-3 py-1.5 rounded-lg bg-white border hover:bg-gray-50"
                                        href="{{ $url }}"
                                        aria-label="اذهب إلى الصفحة {{ localized_digits($page) }}">{{ localized_digits($page) }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <li>
                        <a class="px-3 py-1.5 rounded-lg bg-white border hover:bg-gray-50"
                            href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="الصفحة التالية">التالي</a>
                    </li>
                @else
                    <li>
                        <span class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                            التالي
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
