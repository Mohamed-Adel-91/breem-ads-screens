<?php

namespace App\Http\Requests\Admin\Ads;

use App\Enums\AdStatus;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = array_map(fn(AdStatus $status) => $status->value, AdStatus::cases());

        return [
            'title' => ['nullable', 'array'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'title.ar' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.en' => ['nullable', 'string'],
            'description.ar' => ['nullable', 'string'],
            'creative' => ['required', 'file', 'mimetypes:video/mp4,video/x-m4v,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/mpeg,video/webm,image/jpeg,image/png,image/gif'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in($statusValues)],
            'created_by' => ['required', 'exists:users,id'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
            'play_order' => ['nullable', 'array'],
            'play_order.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function failDurationProbe(): never
    {
        $validator = $this->getValidatorInstance();

        $validator->after(function (Validator $validator): void {
            $validator->errors()->add('duration_seconds', __('duration_seconds required when probe unavailable'));
        });

        $validator->fails();

        throw new ValidationException($validator);
    }
}
