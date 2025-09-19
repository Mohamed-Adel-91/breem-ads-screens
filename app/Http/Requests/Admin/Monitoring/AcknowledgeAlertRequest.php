<?php

namespace App\Http\Requests\Admin\Monitoring;

use App\Enums\ScreenStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcknowledgeAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowed = [ScreenStatus::Online->value, ScreenStatus::Maintenance->value];

        return [
            'status' => ['required', Rule::in($allowed)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
