<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarTermRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'car_model_id' => ['required', 'exists:car_models,id'],
            'term_name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'inventory' => ['required', 'integer'],
            'reservation_amount' => ['required', 'numeric'],
            'specs' => ['nullable', 'array'],
            'specs.*' => ['nullable', 'string', 'max:255'],
            'delete_specs' => ['nullable', 'array'],
            'delete_specs.*' => ['integer', 'exists:specs,id'],
            'features' => ['nullable', 'array'],
            'features.*' => ['exists:features,id'],
            'feature_values' => ['nullable', 'array'],
            'feature_values.*' => ['nullable', 'string', 'max:255'],
            'feature_priorities' => ['nullable', 'array'],
            'feature_priorities.*' => ['nullable', 'integer'],
            'feature_statuses' => ['nullable', 'array'],
            'feature_statuses.*' => ['nullable', 'boolean'],
        ];
    }
}
