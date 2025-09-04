<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InstallmentProgramRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('installment_program');
        $unique = 'unique:installment_programs,name' . ($id ? ',' . $id : '');
        return [
            'bank_id' => ['required', 'exists:banks,id'],
            'name' => ['required', 'string', 'max:255', $unique],
            'interest_rate_per_year' => ['nullable', 'numeric'],
            'applicable_to' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'card_image' => ['sometimes', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'features' => ['nullable', 'array'],
            'features.*.name' => ['nullable', 'string', 'max:255'],
            'features.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
