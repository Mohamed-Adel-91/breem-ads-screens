<?php

namespace App\Http\Requests\Admin\Cms;

use Illuminate\Foundation\Http\FormRequest;

class WhoWeArePageUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'banner_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'who_title_ar' => ['required', 'string', 'max:255'],
            'who_title_en' => ['required', 'string', 'max:255'],
            'who_description_ar' => ['required', 'string'],
            'who_description_en' => ['required', 'string'],

            'features' => ['nullable', 'array'],
            'features.*.title_ar' => ['required', 'string', 'max:255'],
            'features.*.title_en' => ['required', 'string', 'max:255'],
            'features.*.text_ar' => ['required', 'string'],
            'features.*.text_en' => ['required', 'string'],
            'features.*.bullets_ar' => ['nullable', 'string'],
            'features.*.bullets_en' => ['nullable', 'string'],

            'port_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}

