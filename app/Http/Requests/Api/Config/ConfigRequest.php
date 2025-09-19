<?php

namespace App\Http\Requests\Api\Config;

use App\Http\Requests\Api\ApiRequest;

class ConfigRequest extends ApiRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $payload = $this->all();

        if (! isset($payload['device_uid']) && $this->headers->has('X-Screen-Uid')) {
            $payload['device_uid'] = $this->headers->get('X-Screen-Uid');
        }

        $this->replace($payload);
    }

    /**
     * Determine whether the request should validate a timestamp.
     */
    protected function expectsTimestamp(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'device_uid' => ['nullable', 'string'],
            'code' => ['nullable', 'string'],
        ];
    }
}
