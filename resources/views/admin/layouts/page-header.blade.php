@php
    $title = $title ?? $pageName ?? '';
    $subtitle = $subtitle ?? null;
    $breadcrumbs = $breadcrumbs ?? [];
    $primaryAction = $primaryAction ?? null;
    $secondaryAction = $secondaryAction ?? null;
@endphp

<div class="admin-page-header">
    <div>
        @include('admin.partials.breadcrumbs', ['items' => $breadcrumbs])
        <h1 class="page-title mb-1">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        @endif
    </div>

    @if ($primaryAction || $secondaryAction)
        <div class="admin-page-actions">
            @if ($secondaryAction)
                <x-admin.btn
                    :href="$secondaryAction['href'] ?? null"
                    :variant="$secondaryAction['variant'] ?? 'light'"
                    :icon="$secondaryAction['icon'] ?? null">
                    {{ $secondaryAction['label'] ?? '' }}
                </x-admin.btn>
            @endif

            @if ($primaryAction)
                <x-admin.btn
                    :href="$primaryAction['href'] ?? null"
                    :variant="$primaryAction['variant'] ?? 'primary'"
                    :icon="$primaryAction['icon'] ?? null">
                    {{ $primaryAction['label'] ?? '' }}
                </x-admin.btn>
            @endif
        </div>
    @endif
</div>
