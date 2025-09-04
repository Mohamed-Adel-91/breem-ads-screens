<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'insurance_company' => ['required', 'string', 'max:255'],
            'program_name' => ['required', 'string', 'max:255'],
            'coverage_rate' => ['nullable', 'numeric'],
            'company_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'annual_price' => ['nullable', 'numeric'],
            'monthly_payment' => ['nullable', 'numeric'],
            'applicable_to' => ['nullable', 'integer'],
            'features' => ['nullable', 'array'],
            'features.*.name' => ['nullable', 'string', 'max:255'],
            'features.*.value' => ['nullable', 'string', 'max:255'],
        ];
    }
}
