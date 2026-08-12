{{--
    Device pairing panel.

    Shows pairing state and the two administrator actions that govern it. It
    never renders stored credential material — the token hash and the signing
    secret are not passed to this view at all. A freshly generated pairing code
    is flashed through the session and shown exactly once.
--}}
@php
    $isPaired = (bool) $deviceCredential;
    $freshCode = session('pairing_code');
    $freshCodeExpiry = session('pairing_code_expires_at');
@endphp

<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
        <h2 class="card-title mb-0">{{ __('admin.screens.pairing.heading') }}</h2>
        <x-admin.badge :variant="$isPaired ? 'success' : 'secondary'">
            {{ $isPaired ? __('admin.screens.pairing.paired') : __('admin.screens.pairing.not_paired') }}
        </x-admin.badge>
    </div>

    <div class="card-body">
        @if ($freshCode)
            <div class="alert alert-success" role="alert">
                <strong class="d-block mb-1">{{ __('admin.screens.pairing.code_label') }}</strong>
                <code class="admin-pairing-code" dir="ltr">{{ $freshCode }}</code>
                <p class="mb-0 mt-2 small">
                    {{ __('admin.screens.pairing.code_once_notice', ['time' => $freshCodeExpiry]) }}
                </p>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 admin-detail-table">
                <tbody>
                    <tr>
                        <th scope="row">{{ __('admin.screens.pairing.status') }}</th>
                        <td>
                            {{ $isPaired ? __('admin.screens.pairing.paired') : __('admin.screens.pairing.not_paired') }}
                        </td>
                    </tr>
                    @if ($isPaired)
                        <tr>
                            <th scope="row">{{ __('admin.screens.pairing.paired_at') }}</th>
                            <td dir="ltr">{{ optional($deviceCredential->issued_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row">{{ __('admin.screens.pairing.last_seen') }}</th>
                            <td dir="ltr">
                                {{ optional($deviceCredential->last_used_at)->format('Y-m-d H:i')
                                    ?? __('admin.screens.pairing.never_used') }}
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <p class="text-muted small mt-3 mb-0">
            @if ($isPaired)
                {{ __('admin.screens.pairing.already_paired_hint') }}
            @elseif ($livePairingCode)
                {{ __('admin.screens.pairing.live_code_notice', [
                    'time' => $livePairingCode->expires_at->format('Y-m-d H:i'),
                ]) }}
            @else
                {{ __('admin.screens.pairing.no_live_code') }}
            @endif
        </p>

        <p class="text-muted small mb-0">{{ __('admin.screens.pairing.help') }}</p>
    </div>

    @can('screens.edit')
        <div class="card-footer bg-white">
            <x-admin.group-btn class="justify-content-end">
                @unless ($isPaired)
                    <x-admin.btn
                        :href="route('admin.screens.pairing.generate', ['lang' => $lang, 'screen' => $screen->id])"
                        method="POST"
                        variant="primary"
                        icon="key"
                        :confirm="$livePairingCode ? __('admin.screens.pairing.generate_confirm') : null">
                        {{ $livePairingCode
                            ? __('admin.screens.pairing.regenerate')
                            : __('admin.screens.pairing.generate') }}
                    </x-admin.btn>
                @endunless

                @if ($isPaired)
                    <x-admin.btn
                        :href="route('admin.screens.pairing.reset', ['lang' => $lang, 'screen' => $screen->id])"
                        method="DELETE"
                        variant="outline-danger"
                        icon="slash"
                        :confirm="__('admin.screens.pairing.reset_confirm')">
                        {{ __('admin.screens.pairing.reset') }}
                    </x-admin.btn>
                @endif
            </x-admin.group-btn>
        </div>
    @endcan
</div>
