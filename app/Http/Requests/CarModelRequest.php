<?php

namespace App\Http\Requests;

use App\Enums\EngineTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class CarModelRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('car_model');
        $unique = 'unique:car_models,name' . ($id ? ',' . $id : '');
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'shape_id' => ['required', 'exists:shapes,id'],
            'name' => ['required', 'string', 'max:255', $unique],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'torque' => ['nullable', 'string', 'max:255'],
            'gear_box' => ['nullable', 'string', 'max:255'],
            'banner_tablet' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'banner_mobile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'is_home' => ['nullable', 'boolean'],
            'start_price' => ['nullable', 'numeric'],
            'year' => ['nullable', 'integer'],
            'engine' => ['nullable', 'string', 'max:255'],
            'engine_type' => ['nullable', 'in:' . implode(',', EngineTypeEnum::getValues())],
            'catalog' => ['nullable', 'file', 'mimes:pdf', 'max:50000'],
            'maintenance_schedule_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:50000'],
            'view_360_degree' => ['nullable', 'string', 'max:255'],
            'horse_power' => ['nullable', 'integer'],
            'show_status' => ['nullable', 'boolean'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['exists:colors,id'],
            'color_images' => ['nullable', 'array'],
            'color_images.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
        ];
    }
}
