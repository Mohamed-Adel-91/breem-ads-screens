@props([
    'href' => null,
    'type' => 'button',
    'method' => 'GET',
    'variant' => 'primary',
    'size' => null,
    'icon' => null,
    'confirm' => null,
    'formClass' => 'd-inline-block',
    'ariaLabel' => null,
])

@php
    $normalizedMethod = strtoupper($method);
    $buttonClasses = trim('btn btn-' . $variant . ($size ? ' btn-' . $size : ''));
    $label = trim((string) $slot);
    $accessibleLabel = $ariaLabel ?: $label;
@endphp

@if ($href && $normalizedMethod === 'GET')
    <a href="{{ $href }}"
       aria-label="{{ $accessibleLabel }}"
       {{ $attributes->class([$buttonClasses]) }}>
        @if ($icon)
            <i class="fe fe-{{ $icon }}" aria-hidden="true"></i>
        @endif
        <span>{{ $slot }}</span>
    </a>
@elseif ($href)
    <form action="{{ $href }}"
          method="POST"
          class="{{ $formClass }}"
          @if ($confirm) data-confirm-message="{{ $confirm }}" @endif>
        @csrf
        @if (!in_array($normalizedMethod, ['GET', 'POST'], true))
            @method($normalizedMethod)
        @endif
        <button type="submit"
                aria-label="{{ $accessibleLabel }}"
                {{ $attributes->class([$buttonClasses]) }}>
            @if ($icon)
                <i class="fe fe-{{ $icon }}" aria-hidden="true"></i>
            @endif
            <span>{{ $slot }}</span>
        </button>
    </form>
@else
    <button type="{{ $type }}"
            aria-label="{{ $accessibleLabel }}"
            @if ($confirm) data-confirm-message="{{ $confirm }}" @endif
            {{ $attributes->class([$buttonClasses]) }}>
        @if ($icon)
            <i class="fe fe-{{ $icon }}" aria-hidden="true"></i>
        @endif
        <span>{{ $slot }}</span>
    </button>
@endif
