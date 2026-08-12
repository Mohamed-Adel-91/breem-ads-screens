{{--
    Screen status presentation. The view (not the badge component) maps a stored
    status value to a semantic Bootstrap variant. No domain transition happens here
    and an unknown/missing status still renders safely.

    Usage: @include('admin.screens.partials.status-badge', ['status' => $screen->status])
--}}
@php
    $statusValue = $status instanceof \App\Enums\ScreenStatus
        ? $status->value
        : (is_string($status) ? $status : null);

    $statusVariant = match ($statusValue) {
        'online' => 'success',
        'offline' => 'danger',
        'maintenance' => 'warning',
        default => 'secondary',
    };
@endphp

@if ($statusValue)
    <x-admin.badge :variant="$statusVariant">
        {{ \App\Support\Lang::t('admin.screens.statuses.' . $statusValue, ucfirst($statusValue)) }}
    </x-admin.badge>
@else
    <span class="text-muted">—</span>
@endif
