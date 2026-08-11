@php
    /**
     * Flattens the stored payload into dotted keys, mirroring exactly what the
     * previous client-side viewer produced — only now rendered server-side and escaped
     * by Blade instead of being assembled as an HTML string in JavaScript.
     */
    $payload = $submission->payload;
    $rows = is_array($payload) ? \Illuminate\Support\Arr::dot($payload) : [];
    $modalId = 'submission_' . $submission->id;
    $contactRows = [
        __('admin.contact_submissions.table.type') => $submission->type,
        __('admin.contact_submissions.table.name') => $submission->name,
        __('admin.contact_submissions.table.phone') => $submission->phone,
        __('admin.contact_submissions.table.email') => $submission->email,
        __('admin.contact_submissions.table.created_at') => optional($submission->created_at)->format('Y-m-d H:i'),
    ];
@endphp

<div class="modal fade"
     id="{{ $modalId }}"
     tabindex="-1"
     role="dialog"
     aria-labelledby="{{ $modalId }}_label"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}_label">
                    {{ __('admin.contact_submissions.details_heading', ['id' => $submission->id]) }}
                </h5>
                <button type="button"
                        class="close"
                        data-dismiss="modal"
                        aria-label="{{ __('admin.buttons.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="admin-section-title">{{ __('admin.contact_submissions.contact_details') }}</p>

                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered mb-0 admin-detail-table">
                        <caption class="sr-only">
                            {{ __('admin.contact_submissions.contact_details') }}
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('admin.contact_submissions.table.field') }}</th>
                                <th scope="col">{{ __('admin.contact_submissions.table.value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contactRows as $label => $value)
                                <tr>
                                    <th scope="row">{{ $label }}</th>
                                    <td>{{ filled($value) ? $value : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="admin-section-title">{{ __('admin.contact_submissions.actions.payload') }}</p>

                @if (empty($rows))
                    <p class="text-muted mb-0">{{ __('admin.contact_submissions.messages.payload_empty') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-bordered mb-0 admin-detail-table">
                            <caption class="sr-only">
                                {{ __('admin.contact_submissions.actions.payload') }}
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col">{{ __('admin.contact_submissions.table.field') }}</th>
                                    <th scope="col">{{ __('admin.contact_submissions.table.value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $key => $value)
                                    <tr>
                                        <th scope="row"><code>{{ $key }}</code></th>
                                        <td>
                                            @if (is_bool($value))
                                                {{ $value ? __('admin.forms.yes') : __('admin.forms.no') }}
                                            @elseif (is_null($value) || $value === '')
                                                <span class="text-muted">-</span>
                                            @elseif (is_array($value))
                                                <code>{{ json_encode($value, JSON_UNESCAPED_UNICODE) }}</code>
                                            @else
                                                {{ $value }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">
                    {{ __('admin.buttons.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
