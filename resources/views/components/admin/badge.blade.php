@props([
    'variant' => 'secondary',
    'pill' => false,
])

<span {{ $attributes->class([
    'badge',
    'badge-' . $variant,
    'badge-pill' => $pill,
]) }}>
    {{ $slot }}
</span>
