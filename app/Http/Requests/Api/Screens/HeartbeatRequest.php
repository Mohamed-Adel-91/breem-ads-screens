<?php

namespace App\Http\Requests\Api\Screens;

use App\Enums\ScreenStatus;
use App\Http\Requests\Api\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * The screen is resolved from the authenticated credential, so no device_uid or
 * code is accepted from the body — a device can only ever report for itself.
 */
class HeartbeatRequest extends ApiRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(ScreenStatus::class)],
            'current_ad_code' => ['nullable', 'string', 'max:255'],
            'reported_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
            'meta.signal' => ['nullable', 'numeric'],
            'meta.uptime' => ['nullable', 'numeric'],
        ];
    }
}
