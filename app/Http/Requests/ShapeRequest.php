<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShapeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = $this->route('shape');
        $unique = 'unique:shapes,name' . ($id ? ',' . $id : '');
        return [
            'name' => ['required', 'string', 'max:255', $unique],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5000'],
        ];
    }
}
