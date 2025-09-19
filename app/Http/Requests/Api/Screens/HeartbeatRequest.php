<?php

namespace App\Http\Requests\Api\Screens;

use App\Enums\ScreenStatus;
use App\Http\Requests\Api\ApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class HeartbeatRequest extends ApiRequest
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
     * Determine the canonical payload for signature validation.
     */
    protected function signaturePayload(): string
    {
        return parent::signaturePayload();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timestamp' => ['required', 'integer'],
            'device_uid' => ['nullable', 'string', 'required_without:code'],
            'code' => ['nullable', 'string', 'required_without:device_uid'],
            'status' => ['nullable', Rule::enum(ScreenStatus::class)],
            'current_ad_code' => ['nullable', 'string'],
            'reported_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
            'meta.signal' => ['nullable', 'numeric'],
            'meta.uptime' => ['nullable', 'numeric'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        if ($validator instanceof Validator) {
            $validator->after(function (Validator $validator): void {
                if ($validator->fails()) {
                    return;
                }

                if (! $this->input('device_uid') && ! $this->input('code')) {
                    $validator->errors()->add('device_uid', __('Either the device UID or code must be provided.'));
                }
            });
        }
    }
}
