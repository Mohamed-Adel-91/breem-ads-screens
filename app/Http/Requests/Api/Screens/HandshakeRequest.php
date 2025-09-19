<?php

namespace App\Http\Requests\Api\Screens;

use App\Http\Requests\Api\ApiRequest;

class HandshakeRequest extends ApiRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $device = (array) $this->input('device', []);

        if (! isset($device['uid']) && $this->headers->has('X-Screen-Uid')) {
            $device['uid'] = $this->headers->get('X-Screen-Uid');
        }

        $this->merge([
            'device' => $device,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'timestamp' => ['required', 'integer'],
            'device' => ['required', 'array'],
            'device.uid' => ['required', 'string'],
            'device.model' => ['nullable', 'string'],
            'device.firmware' => ['nullable', 'string'],
            'meta' => ['nullable', 'array'],
            'meta.timezone' => ['nullable', 'timezone'],
            'meta.locale' => ['nullable', 'string'],
        ];
    }
}
