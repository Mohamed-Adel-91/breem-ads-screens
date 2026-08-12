{{--
    Ad status presentation. The view (not the badge component) maps a stored status
    value to a semantic Bootstrap variant. No transition or approval logic happens
    here, and an unknown/missing status still renders safely.

    Usage: @include('admin.ads.partials.status-badge', ['status' => $ad->status])
--}}
@php
    $statusValue = $status instanceof \App\Enums\AdStatus
        ? $status->value
        : (is_string($status) ? $status : null);

    $statusVariant = match ($statusValue) {
        'active' => 'success',
        'approved' => 'info',
        'pending' => 'warning',
        'rejected' => 'danger',
        'expired' => 'secondary',
        default => 'secondary',
    };
@endphp

@if ($statusValue)
    <x-admin.badge :variant="$statusVariant">
        {{ \App\Support\Lang::t('admin.ads.statuses.' . $statusValue, ucfirst($statusValue)) }}
    </x-admin.badge>
@else
    <span class="text-muted">—</span>
@endif
