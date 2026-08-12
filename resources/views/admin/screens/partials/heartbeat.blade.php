{{--
    Last-heartbeat presentation only. This partial NEVER decides whether a screen is
    online or offline — the stored `screens.status` column remains the single source
    of truth. It simply renders the stored `last_heartbeat` timestamp.

    Usage: @include('admin.screens.partials.heartbeat', ['heartbeat' => $screen->last_heartbeat])
--}}
@if ($heartbeat)
    <span class="d-block">{{ $heartbeat->format('Y-m-d H:i') }}</span>
    <small class="text-muted">{{ $heartbeat->diffForHumans() }}</small>
@else
    <span class="text-muted">{{ __('admin.screens.never_connected') }}</span>
@endif
