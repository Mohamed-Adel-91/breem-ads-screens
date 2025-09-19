<?php

namespace App\Http\Requests\Api\Playback;

use App\Http\Requests\Api\ApiRequest;
use Illuminate\Validation\Validator;

class StorePlaybackRequest extends ApiRequest
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
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.ad_id' => ['nullable', 'integer', 'exists:ads,id'],
            'entries.*.played_at' => ['required', 'date'],
            'entries.*.duration' => ['nullable', 'integer', 'min:0'],
            'entries.*.extra' => ['nullable', 'array'],
            'entries.*.extra.*' => ['nullable'],
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
