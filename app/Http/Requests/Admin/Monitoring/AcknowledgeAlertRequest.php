<?php

namespace App\Http\Requests\Admin\Monitoring;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Acknowledging an alert records that an administrator has seen it. Nothing more.
 *
 * `status` was previously accepted and written straight onto the screen, which
 * let the Monitoring page manufacture connectivity. It is gone: a screen's
 * online/offline state comes from device heartbeats and the offline sweep, and
 * maintenance is set explicitly on the Screen edit form.
 */
class AcknowledgeAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
