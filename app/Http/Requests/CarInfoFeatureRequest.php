<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarInfoFeatureRequest extends FormRequest
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
            'title' => ['required', 'string'],
            'description' => ['required', 'string'],
            'rank' => ['required', 'integer', 'unique:car_info_features,rank,NULL,id,car_model_id,' . $this->car_model_id],
        ];
    }
}
