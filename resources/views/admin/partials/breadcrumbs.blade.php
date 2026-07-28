@php
    $items = $items ?? [];
@endphp

<nav aria-label="{{ __('admin.header.home') }}">
    <ol class="breadcrumb admin-breadcrumb">
        <li class="breadcrumb-item">
            <a href="{{ route('admin.dashboard', ['lang' => app()->getLocale()]) }}">
                {{ __('admin.header.home') }}
            </a>
        </li>
        @foreach ($items as $item)
            @php
                $isLast = $loop->last;
                $itemUrl = $item['url'] ?? null;
            @endphp
            <li @class(['breadcrumb-item', 'active' => $isLast]) @if ($isLast) aria-current="page" @endif>
                @if ($itemUrl && !$isLast)
                    <a href="{{ $itemUrl }}">{{ $item['label'] ?? '' }}</a>
                @else
                    {{ $item['label'] ?? '' }}
                @endif
            </li>
        @endforeach
    </ol>
</nav>
