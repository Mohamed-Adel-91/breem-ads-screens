<?php

namespace App\Http\Requests\Admin\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateReportRequest extends FormRequest
{
    public const TYPES = ['playback', 'screen-uptime'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(self::TYPES)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'screen_id' => ['nullable', 'integer', 'exists:screens,id'],
            'ad_id' => ['nullable', 'integer', 'exists:ads,id'],
        ];
    }
}
