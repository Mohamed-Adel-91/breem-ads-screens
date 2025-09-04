<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        if ($this->has('slider_images') && !is_array($this->slider_images)) {
            $this->merge([
                'slider_images' => (array) $this->slider_images,
            ]);
        }
        if ($this->has('delete_slider_images') && !is_array($this->delete_slider_images)) {
            $this->merge([
                'delete_slider_images' => (array) $this->delete_slider_images,
            ]);
        }
    }

    public function rules()
    {
        $id = $this->route('brand');
        $unique = 'unique:brands,name' . ($id ? ',' . $id : '');
        return [
            'name' => ['required', 'string', 'max:255', $unique],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'banner_tablet' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'banner_mobile' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'slider_images' => ['nullable', 'array'],
            'slider_images.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
            'delete_slider_images' => ['nullable', 'array'],
            'delete_slider_images.*' => ['string'],
            'show_status' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
