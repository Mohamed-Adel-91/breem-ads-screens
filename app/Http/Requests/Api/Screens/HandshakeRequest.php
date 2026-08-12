<?php

namespace App\Http\Requests\Api\Screens;

use App\Http\Requests\Api\ApiRequest;

/**
 * Pairing payload. This is the only unauthenticated Device API endpoint, so the
 * one-time `pairing_code` is what proves the device is entitled to claim the
 * screen — knowing the screen `code` alone is deliberately not enough.
 */
class HandshakeRequest extends ApiRequest
{
    protected function prepareForValidation(): void
    {
        $device = (array) $this->input('device', []);

        if (! isset($device['uid']) && $this->headers->has('X-Screen-Uid')) {
            $device['uid'] = $this->headers->get('X-Screen-Uid');
        }

        $this->merge(['device' => $device]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'pairing_code' => ['required', 'string', 'max:64'],
            'device' => ['required', 'array'],
            'device.uid' => ['required', 'string', 'max:255'],
            'device.model' => ['nullable', 'string', 'max:255'],
            'device.firmware' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
            'meta.timezone' => ['nullable', 'timezone'],
            'meta.locale' => ['nullable', 'string', 'max:10'],
        ];
    }
}
