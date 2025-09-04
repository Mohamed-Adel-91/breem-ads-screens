<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarExteriorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_model_id' => ['required', 'exists:car_models,id'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'hero_section' => ['nullable', 'boolean'],
        ];
    }
}
