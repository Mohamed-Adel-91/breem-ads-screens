<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InsuranceFeatureRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'insurance_id' => ['required','exists:insurance,id'],
            'feature_name' => ['required','string','max:255'],
            'feature_value' => ['nullable','string'],
        ];
    }
}
