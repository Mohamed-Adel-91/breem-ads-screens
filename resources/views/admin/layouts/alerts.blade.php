@php
    $flashTypes = [
        'success' => 'success',
        'error' => 'danger',
        'status' => 'info',
    ];
    $containerClass = $containerClass ?? '';
@endphp

@if (session()->has('success') || session()->has('error') || session()->has('status') || $errors->any())
    <div class="{{ $containerClass }} breem-alerts" aria-live="polite">
        @foreach ($flashTypes as $flashKey => $alertType)
            @if (session()->has($flashKey))
                @foreach ((array) session($flashKey) as $message)
                    <div class="alert alert-{{ $alertType }} alert-dismissible fade show rounded p-3"
                         role="alert"
                         data-auto-dismiss="{{ $flashKey === 'success' ? 'true' : 'false' }}">
                        {{ $message }}
                        <button type="button"
                                class="close"
                                data-dismiss="alert"
                                aria-label="{{ __('admin.sweet_alert.cancel_button') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endforeach
            @endif
        @endforeach

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show rounded p-3" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button"
                        class="close"
                        data-dismiss="alert"
                        aria-label="{{ __('admin.sweet_alert.cancel_button') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
    </div>
@endif
